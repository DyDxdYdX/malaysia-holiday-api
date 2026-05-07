<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\HolidayImportBatch;
use App\Models\HolidaySource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;

class HolidayImportController extends Controller
{
    public function create(HolidaySource $source): View
    {
        return view('admin.imports.create', [
            'source' => $source,
        ]);
    }

    public function store(Request $request, HolidaySource $source): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', File::types(['csv', 'txt'])->max(10 * 1024)],
        ]);

        $rows = $this->readCsvRows($validated['file']->getRealPath());

        $batch = HolidayImportBatch::create([
            'holiday_source_id' => $source->id,
            'year' => $source->year,
            'status' => 'review_required',
            'total_rows' => count($rows),
            'valid_rows' => 0,
            'invalid_rows' => 0,
            'warning_rows' => 0,
            'imported_by' => $request->user()->id,
            'imported_at' => now(),
        ]);

        $validRows = 0;
        $warningRows = 0;

        foreach ($rows as $row) {
            if (! $this->hasRequiredHolidayData($row) || ! $this->hasValidDate($row['date'])) {
                $batch->increment('invalid_rows');

                continue;
            }

            $date = Carbon::parse($row['date']);

            Holiday::create([
                'holiday_source_id' => $source->id,
                'holiday_import_batch_id' => $batch->id,
                'year' => (int) $row['year'],
                'state_code' => strtoupper($row['state_code']),
                'name' => $row['name'],
                'date' => $date->toDateString(),
                'day_name' => $date->format('l'),
                'scope' => $row['scope'],
                'type' => $row['type'],
                'is_subject_to_change' => filter_var($row['is_subject_to_change'] ?? false, FILTER_VALIDATE_BOOL),
                'status' => 'draft',
                'source_note' => $row['source_note'] ?? null,
            ]);

            $validRows++;

            if (filter_var($row['is_subject_to_change'] ?? false, FILTER_VALIDATE_BOOL)) {
                $warningRows++;
            }
        }

        $batch->update([
            'valid_rows' => $validRows,
            'warning_rows' => $warningRows,
        ]);

        return redirect()
            ->route('admin.batches.show', $batch)
            ->with('status', 'CSV import completed.');
    }

    /**
     * @return list<array<string, string|null>>
     */
    private function readCsvRows(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return [];
        }

        $headers = fgetcsv($handle);
        $rows = [];

        if ($headers === false) {
            fclose($handle);

            return [];
        }

        $headers = array_map(fn (string $header): string => trim($header), $headers);

        while (($values = fgetcsv($handle)) !== false) {
            $row = [];

            foreach ($headers as $index => $header) {
                $row[$header] = isset($values[$index]) ? trim($values[$index]) : null;
            }

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function hasRequiredHolidayData(array $row): bool
    {
        foreach (['year', 'state_code', 'name', 'date', 'scope', 'type'] as $column) {
            if (! isset($row[$column]) || $row[$column] === '') {
                return false;
            }
        }

        return true;
    }

    private function hasValidDate(?string $date): bool
    {
        if ($date === null) {
            return false;
        }

        try {
            Carbon::parse($date);
        } catch (\Throwable) {
            return false;
        }

        return true;
    }
}
