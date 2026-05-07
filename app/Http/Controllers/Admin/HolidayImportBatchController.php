<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HolidayImportBatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function show(HolidayImportBatch $batch): View
    {
        return view('admin.batches.show', [
            'batch' => $batch->load(['source', 'holidays' => fn ($query) => $query->orderBy('date')]),
        ]);
    }

    public function publish(Request $request, HolidayImportBatch $batch): RedirectResponse
    {
        abort_if($batch->invalid_rows > 0, 422, 'Batch still has unresolved invalid rows.');

        $batch->holidays()
            ->whereIn('status', ['draft', 'confirmed'])
            ->update(['status' => 'published']);

        $batch->update([
            'status' => 'published',
            'published_by' => $request->user()->id,
            'published_at' => now(),
        ]);

        $batch->source()->update(['status' => 'active']);

        return redirect()
            ->route('admin.batches.show', $batch)
            ->with('status', 'Batch published.');
    }
}
