<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HolidaySource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;

class HolidaySourceController extends Controller
{
    public function index(): View
    {
        return view('admin.sources.index', [
            'sources' => HolidaySource::query()
                ->withCount(['importBatches', 'holidays'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.sources.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'between:2000,2100'],
            'source_name' => ['required', 'string', 'max:255'],
            'source_type' => ['required', Rule::in(['federal_pdf', 'state_page', 'gazette', 'admin_csv', 'manual_entry', 'third_party_reference'])],
            'source_url' => ['nullable', 'url', 'max:2048'],
            'file' => ['nullable', File::types(['pdf', 'csv', 'txt'])->max(10 * 1024)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $file = $request->file('file');

        if ($file !== null) {
            $validated['checksum'] = hash_file('sha256', $file->getRealPath());
            $validated['file_path'] = $file->store('sources');
        }

        $validated['uploaded_by'] = $request->user()->id;
        $validated['uploaded_at'] = now();
        $validated['status'] = 'draft';

        $source = HolidaySource::create($validated);

        return redirect()
            ->route('admin.sources.show', $source)
            ->with('status', 'Holiday source uploaded.');
    }

    public function show(HolidaySource $source): View
    {
        return view('admin.sources.show', [
            'source' => $source->load(['uploader', 'importBatches' => fn ($query) => $query->latest()]),
        ]);
    }

    public function destroy(HolidaySource $source): RedirectResponse
    {
        abort_if($source->status !== 'draft', 422, 'Only draft sources may be removed.');

        if ($source->file_path !== null) {
            Storage::delete($source->file_path);
        }

        $source->delete();

        return redirect()
            ->route('admin.sources.index')
            ->with('status', 'Draft source removed.');
    }
}
