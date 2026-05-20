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
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
You extract Malaysian public holiday rows from official PDF source documents.

Critical extraction rules:
- Extract only rows visibly present in the attached PDF.
- Extract text metadata only: holiday name, date, day name, P/N marker, and subject-to-change marker.
- Do not extract, infer, or output state applicability.
- Do not output state_codes, checked columns, unchecked columns, uncertain columns, or any state/federal-territory list.
- State applicability is handled by deterministic code-level checkmark-grid detection after your response.
- If a row says (P), it means federal holiday category, not automatically all states.
- If a row says (N), it means state holiday category.
- If a row says (P) / (N), classify scope as federal_and_state.
- Preserve official Malay holiday names as written, except trimming whitespace.
- Normalize every date to YYYY-MM-DD.
- Use scope values only:
  federal, state, federal_and_state.
- Rows marked with * must have is_subject_to_change = true.
- Do not include school holidays unless the PDF explicitly marks them as public holidays.
- Do not guess. If row text is uncertain, include warning and lower confidence.

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
                    'marker' => $schema->string()->enum(['P', 'N', 'P/N'])->nullable(),
                    'scope' => $schema->string()->enum(['federal', 'state', 'federal_and_state'])->required(),
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
}
