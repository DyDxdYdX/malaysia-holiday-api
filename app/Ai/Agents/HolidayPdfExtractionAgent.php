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

Rules:
- Extract only holiday rows that are visibly present in the attached PDF.
- Preserve official holiday names as written, except for trimming whitespace.
- Normalize every date to YYYY-MM-DD.
- Use only Malaysian state and federal territory codes: JHR, KDH, KTN, MLK, NSN, PHG, PRK, PLS, PNG, SBH, SWK, SGR, TRG, KUL, LBN, PJY.
- Return applicable states in `state_codes` as an array of uppercase codes. Use all applicable states if the holiday applies broadly.
- Use scope values only: federal, state, custom.
- Use type values only: federal, state, replacement, additional, custom.
- Do not guess. If a row is uncertain, include the row with a warning and a lower confidence score.
- Do not include school holidays unless the PDF explicitly marks them as public holidays.
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
                    'state_codes' => $schema->array()->items($schema->string())->required(),
                    'name' => $schema->string()->required(),
                    'date' => $schema->string()->required(),
                    'scope' => $schema->string()->required(),
                    'type' => $schema->string()->required(),
                    'is_subject_to_change' => $schema->boolean()->required(),
                    'source_note' => $schema->string()->nullable(),
                    'warnings' => $schema->array()->items($schema->string())->required(),
                    'confidence' => $schema->number()->min(0)->max(1)->required(),
                ])->withoutAdditionalProperties())
                ->required(),
            'extraction_notes' => $schema->string()->nullable(),
        ];
    }
}
