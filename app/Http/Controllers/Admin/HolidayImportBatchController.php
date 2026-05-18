<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HolidayImportBatch;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class HolidayImportBatchController extends Controller
{
    public function index(): View
    {
        return view('admin.batches.index', [
            'batches' => HolidayImportBatch::query()
                ->with(['source', 'importer'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function show(HolidayImportBatch $batch): Response
    {
        $batch->load([
            'source',
            'holidays' => fn ($query) => $query->orderBy('date'),
            'importRows' => fn ($query) => $query->orderBy('row_number'),
        ]);

        $isPdfExtractionPending = $batch->import_method === 'pdf_ai'
            && $batch->status === 'draft'
            && $batch->completed_at === null
            && $batch->failed_at === null;

        $response = response()->view('admin.batches.show', [
            'batch' => $batch,
            'isPdfExtractionPending' => $isPdfExtractionPending,
        ]);

        if ($isPdfExtractionPending) {
            $response->headers->set('Refresh', '5');
        }

        return $response;
    }

    public function publish(Request $request, HolidayImportBatch $batch, AuditLogger $auditLogger): RedirectResponse
    {
        abort_if($batch->invalid_rows > 0, 422, 'Batch still has unresolved invalid rows.');

        $oldBatchValues = $batch->toArray();
        $batch->holidays()
            ->whereIn('status', ['draft', 'confirmed'])
            ->update(['status' => 'published']);

        $batch->update([
            'status' => 'published',
            'published_by' => $request->user()->id,
            'published_at' => now(),
        ]);

        $batch->source()->update(['status' => 'active']);
        $auditLogger->logFromRequest(
            request: $request,
            action: 'holiday_published',
            entityType: 'holiday_import_batch',
            entityId: $batch->id,
            oldValues: $oldBatchValues,
            newValues: $batch->fresh()?->toArray(),
        );
        $auditLogger->logFromRequest(
            request: $request,
            action: 'source_updated',
            entityType: 'holiday_source',
            entityId: $batch->holiday_source_id,
            newValues: ['status' => 'active'],
        );

        return redirect()
            ->route('admin.batches.show', $batch)
            ->with('status', 'Batch published.');
    }
}
