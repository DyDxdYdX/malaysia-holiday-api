<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class HolidayPdfExtractionAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * @var list<string>
     */
    public const STATE_CODES = [
        'KUL',
        'LBN',
        'PJY',
        'JHR',
        'KDH',
        'KTN',
        'MLK',
        'NSN',
        'PHG',
        'PRK',
        'PLS',
        'PNG',
        'SBH',
        'SWK',
        'SGR',
        'TRG',
    ];

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
You extract Malaysian public holiday rows from official PDF source documents.

Critical extraction rules:
- Extract only rows visibly present in the attached PDF.
- Do not infer state applicability from the word "federal".
- State applicability MUST be read from the visible checkmark cells under each state/federal-territory column.
- A grey cell, blank cell, dash, or empty cell means NOT applicable.
- A visible tick/checkmark means applicable.
- If a row says (P), it means federal holiday category, not automatically all states.
- If a row says (N), it means state holiday category.
- If a row says (P) / (N), classify it as federal_and_state or federal with state-specific applicability depending on the visible row.
- For every row, list ONLY the state codes with visible checkmarks.
- Never fill all 16 state codes unless all 16 columns visibly contain checkmarks.
- Preserve official Malay holiday names as written, except trimming whitespace.
- Normalize every date to YYYY-MM-DD.
- Use only these state/federal territory codes:
  KUL, LBN, PJY, JHR, KDH, KTN, MLK, NSN, PHG, PRK, PLS, PNG, SBH, SWK, SGR, TRG.
- Use scope values only:
  federal, state, federal_and_state, custom.
- Use type values only: federal, state, replacement, additional, custom.
- Rows marked with * must have is_subject_to_change = true.
- Do not include school holidays unless the PDF explicitly marks them as public holidays.
- Do not guess. If checkmarks are uncertain, include warning and lower confidence.

Column order in the PDF:
KUL, LBN, PJY, JHR, KDH, KTN, MLK, NSN, PHG, PRK, PLS, PNG, SBH, SWK, SGR, TRG.

Return one row per holiday date.
PROMPT;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'rows' => $schema->array()
                ->items($schema->object([
                    'row_number' => $schema->integer()->required(),
                    'year' => $schema->integer()->required(),
                    'name' => $schema->string()->required(),
                    'date' => $schema->string()->required(),
                    'day_name' => $schema->string()->nullable(),
                    'scope' => $schema->string()->enum(['federal', 'state', 'federal_and_state', 'custom'])->required(),
                    'type' => $schema->string()->enum(['federal', 'state', 'replacement', 'additional', 'custom'])->required(),
                    'state_codes' => $schema->array()
                        ->items($schema->string()->enum(self::STATE_CODES))
                        ->description('Only states/federal territories with visible checkmarks.')
                        ->required(),
                    'state_detection' => $schema->object([
                        'column_order' => $schema->array()
                            ->items($schema->string()->enum(self::STATE_CODES))
                            ->description('Must be exactly: KUL, LBN, PJY, JHR, KDH, KTN, MLK, NSN, PHG, PRK, PLS, PNG, SBH, SWK, SGR, TRG.')
                            ->required(),
                        'checked_columns' => $schema->array()
                            ->items($schema->string()->enum(self::STATE_CODES))
                            ->description('Columns where visible checkmark was detected.')
                            ->required(),
                        'unchecked_columns' => $schema->array()
                            ->items($schema->string()->enum(self::STATE_CODES))
                            ->description('Columns with grey/blank/dash/empty cells.')
                            ->required(),
                        'uncertain_columns' => $schema->array()
                            ->items($schema->string()->enum(self::STATE_CODES))
                            ->description('Columns where OCR/vision is unsure.')
                            ->required(),
                    ])->withoutAdditionalProperties()->required(),
                    'is_subject_to_change' => $schema->boolean()->required(),
                    'source' => $schema->object([
                        'page_number' => $schema->integer()->nullable(),
                        'table_title' => $schema->string()->nullable(),
                        'raw_row_text' => $schema->string()->nullable(),
                        'raw_marker' => $schema->string()->nullable(),
                    ])->withoutAdditionalProperties()->required(),
                    'warnings' => $schema->array()->items($schema->string())->required(),
                    'confidence' => $schema->number()->min(0)->max(1)->required(),
                ])->withoutAdditionalProperties())
                ->required(),
            'extraction_notes' => $schema->string()->nullable(),
        ];
    }

    /**
     * Enforce post-extraction rules that must not depend on model judgment.
     *
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    public function validateExtraction(array $response): array
    {
        $rows = is_array($response['rows'] ?? null) ? $response['rows'] : [];

        $response['rows'] = array_map(function (mixed $row): array {
            $row = is_array($row) ? $row : [];
            $row['warnings'] = $this->stringList($row['warnings'] ?? []);
            $row['confidence'] = $this->confidence($row['confidence'] ?? null);

            if (! is_array($row['state_detection'] ?? null)) {
                $row['state_detection'] = [
                    'column_order' => self::STATE_CODES,
                    'checked_columns' => $this->stateCodeList($row['state_codes'] ?? []),
                    'unchecked_columns' => [],
                    'uncertain_columns' => [],
                ];
                $row['warnings'][] = 'State detection evidence was missing. Treat state_codes as unverified.';
                $row['confidence'] = min($row['confidence'], 0.75);
            }

            $row['state_detection']['column_order'] = self::STATE_CODES;
            $row['state_detection']['checked_columns'] = $this->stateCodeList($row['state_detection']['checked_columns'] ?? []);
            $row['state_detection']['unchecked_columns'] = $this->stateCodeList($row['state_detection']['unchecked_columns'] ?? []);
            $row['state_detection']['uncertain_columns'] = $this->stateCodeList($row['state_detection']['uncertain_columns'] ?? []);

            $checked = $row['state_detection']['checked_columns'];
            $stateCodes = $this->stateCodeList($row['state_codes'] ?? []);

            if ($this->sorted($stateCodes) !== $this->sorted($checked)) {
                $row['warnings'][] = 'state_codes does not match checked_columns. Use checked_columns as source of truth.';
                $row['state_codes'] = $checked;
                $row['confidence'] = min($row['confidence'], 0.75);
            } else {
                $row['state_codes'] = $stateCodes;
            }

            foreach ($row['state_detection']['unchecked_columns'] as $code) {
                if (in_array($code, $row['state_codes'], true)) {
                    $row['warnings'][] = "Unchecked column {$code} was incorrectly included in state_codes.";
                    $row['state_codes'] = array_values(array_diff($row['state_codes'], [$code]));
                    $row['state_detection']['checked_columns'] = array_values(array_diff($row['state_detection']['checked_columns'], [$code]));
                    $row['confidence'] = min($row['confidence'], 0.7);
                }
            }

            if ($row['state_detection']['uncertain_columns'] !== []) {
                $row['warnings'][] = 'Some state columns are uncertain: '.implode(', ', $row['state_detection']['uncertain_columns']);
                $row['confidence'] = min($row['confidence'], 0.85);
            }

            if (
                ($row['scope'] ?? null) === 'federal'
                && count($row['state_codes']) === count(self::STATE_CODES)
                && $row['state_detection']['uncertain_columns'] === []
            ) {
                $row['warnings'][] = 'Federal row has all states. Verify all 16 visible checkmarks exist in the source table.';
            }

            if ($this->hasSubjectToChangeMarker($row)) {
                $row['is_subject_to_change'] = true;
            }

            return $row;
        }, $rows);

        return $response;
    }

    /**
     * @return list<string>
     */
    private function stateCodeList(mixed $value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            fn (mixed $code): string => strtoupper(trim((string) $code)),
            $value,
        ), fn (string $code): bool => in_array($code, self::STATE_CODES, true))));
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (mixed $warning): string => trim((string) $warning),
            $value,
        )));
    }

    /**
     * @param  list<string>  $codes
     * @return list<string>
     */
    private function sorted(array $codes): array
    {
        sort($codes);

        return $codes;
    }

    private function confidence(mixed $confidence): float
    {
        if (! is_numeric($confidence)) {
            return 0.0;
        }

        return max(0.0, min(1.0, (float) $confidence));
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function hasSubjectToChangeMarker(array $row): bool
    {
        $source = is_array($row['source'] ?? null) ? $row['source'] : [];

        return str_contains((string) ($row['name'] ?? ''), '*')
            || str_contains((string) ($source['raw_row_text'] ?? ''), '*');
    }
}
