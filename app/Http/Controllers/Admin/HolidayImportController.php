<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ExtractHolidayPdf;
use App\Models\HolidaySource;
use App\Services\Holidays\CsvHolidayImportParser;
use App\Services\Holidays\HolidayImportService;
use App\Services\Holidays\HolidayImportTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HolidayImportController extends Controller
{
    public function create(HolidaySource $source): View
    {
        return view('admin.imports.create', [
            'source' => $source,
        ]);
    }

    public function template(HolidaySource $source, HolidayImportTemplate $template): StreamedResponse
    {
        $filename = Str::slug($source->source_name).'-holiday-import-template.csv';

        return response()->streamDownload(function () use ($source, $template): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, HolidayImportTemplate::HEADERS);

            foreach ($template->sampleRows($source->year) as $row) {
                fputcsv($handle, array_map(
                    fn (string $header): string => $row[$header] ?? '',
                    HolidayImportTemplate::HEADERS
                ));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function store(
        Request $request,
        HolidaySource $source,
        CsvHolidayImportParser $parser,
        HolidayImportService $imports,
    ): RedirectResponse {
        $validated = $request->validate([
            'file' => ['required', File::types(['csv', 'txt'])->max(10 * 1024)],
        ]);

        $batch = $imports->importRows(
            source: $source,
            rows: $parser->parse($validated['file']->getRealPath()),
            importedBy: $request->user()->id,
            importMethod: 'csv',
        );

        return redirect()
            ->route('admin.batches.show', $batch)
            ->with('status', 'CSV import completed.');
    }

    public function extractPdf(Request $request, HolidaySource $source, HolidayImportService $imports): RedirectResponse
    {
        abort_unless($this->sourceHasPdf($source), Response::HTTP_UNPROCESSABLE_ENTITY, 'The source must have a stored PDF file.');

        $batch = $imports->createPendingBatch(
            source: $source,
            importedBy: $request->user()->id,
            importMethod: 'pdf_ai',
            provider: 'gemini',
            model: config('ai.holiday_pdf_extraction_model', 'gemini-2.5-flash-lite'),
        );

        ExtractHolidayPdf::dispatch($batch->id);

        return redirect()
            ->route('admin.batches.show', $batch)
            ->with('status', 'PDF extraction queued.');
    }

    private function sourceHasPdf(HolidaySource $source): bool
    {
        return $source->file_path !== null
            && strtolower(pathinfo($source->file_path, PATHINFO_EXTENSION)) === 'pdf';
    }
}
