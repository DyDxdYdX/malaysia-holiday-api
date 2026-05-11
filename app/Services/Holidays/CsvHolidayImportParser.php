<?php

namespace App\Services\Holidays;

class CsvHolidayImportParser
{
    /**
     * @return list<array{
     *     row_number:int,
     *     raw_payload:array<string, mixed>,
     *     normalized_payload:array<string, mixed>,
     *     errors:list<string>,
     *     warnings:list<string>,
     *     confidence:null
     * }>
     */
    public function parse(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return [[
                'row_number' => 1,
                'raw_payload' => [],
                'normalized_payload' => [],
                'errors' => ['Unable to read the uploaded CSV file.'],
                'warnings' => [],
                'confidence' => null,
            ]];
        }

        $headers = fgetcsv($handle);

        if ($headers === false) {
            fclose($handle);

            return [[
                'row_number' => 1,
                'raw_payload' => [],
                'normalized_payload' => [],
                'errors' => ['The CSV file is empty.'],
                'warnings' => [],
                'confidence' => null,
            ]];
        }

        $headers = array_map(fn (string $header): string => trim($header), $headers);
        $headerErrors = $this->headerErrors($headers);

        if ($headerErrors !== []) {
            fclose($handle);

            return [[
                'row_number' => 1,
                'raw_payload' => ['headers' => $headers],
                'normalized_payload' => [],
                'errors' => $headerErrors,
                'warnings' => [],
                'confidence' => null,
            ]];
        }

        $rows = [];
        $rowNumber = 1;

        while (($values = fgetcsv($handle)) !== false) {
            $rowNumber++;
            $rawPayload = [];

            foreach ($headers as $index => $header) {
                $rawPayload[$header] = isset($values[$index]) ? trim($values[$index]) : null;
            }

            $rows[] = [
                'row_number' => $rowNumber,
                'raw_payload' => $rawPayload,
                'normalized_payload' => $rawPayload,
                'errors' => [],
                'warnings' => [],
                'confidence' => null,
            ];
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @param  list<string>  $headers
     * @return list<string>
     */
    private function headerErrors(array $headers): array
    {
        $missingHeaders = array_values(array_diff(HolidayImportTemplate::HEADERS, $headers));
        $extraHeaders = array_values(array_diff($headers, HolidayImportTemplate::HEADERS));
        $errors = [];

        if ($missingHeaders !== []) {
            $errors[] = 'Missing required CSV headers: '.implode(', ', $missingHeaders).'.';
        }

        if ($extraHeaders !== []) {
            $errors[] = 'Unexpected CSV headers: '.implode(', ', $extraHeaders).'.';
        }

        return $errors;
    }
}
