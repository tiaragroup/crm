<?php

namespace Webkul\Admin\Http\Controllers\DocumentImport;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webkul\Admin\DataGrids\DocumentImport\DocumentImportDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\DocumentImport\ReviewDocumentImportRequest;
use Webkul\Admin\Http\Requests\DocumentImport\StoreDocumentImportRequest;
use Webkul\Lead\Jobs\ProcessDocumentImport;
use Webkul\Lead\Models\DocumentImport;
use Webkul\Lead\Services\DocumentDuplicateService;
use Webkul\Lead\Services\DocumentImportService;

class DocumentImportController extends Controller
{
    public function index(): View
    {
        return view('admin::document-imports.index');
    }

    public function get(): JsonResponse
    {
        return datagrid(DocumentImportDataGrid::class)->process();
    }

    public function create(): View
    {
        return view('admin::document-imports.create');
    }

    public function store(StoreDocumentImportRequest $request): RedirectResponse
    {
        $file = $request->file('document');
        $extension = strtolower($file->getClientOriginalExtension());
        $storedPath = $file->storeAs('document-imports/'.now()->format('Y/m'), Str::uuid().'.'.$extension, config('document_imports.disk'));
        $import = DocumentImport::create([
            'user_id'     => auth()->guard('user')->id(), 'original_filename' => basename($file->getClientOriginalName()),
            'stored_path' => $storedPath, 'mime_type' => $file->getMimeType(), 'extension' => $extension,
            'file_size'   => $file->getSize(), 'status' => DocumentImport::STATUS_UPLOADED,
        ]);

        config('document_imports.queue') ? ProcessDocumentImport::dispatch($import->id) : ProcessDocumentImport::dispatchSync($import->id);
        $import->refresh();
        session()->flash($import->status === DocumentImport::STATUS_FAILED ? 'error' : 'success', trans('admin::app.document-imports.messages.uploaded'));

        return redirect()->route($import->status === DocumentImport::STATUS_REVIEW ? 'admin.document_imports.review' : 'admin.document_imports.index', $import->status === DocumentImport::STATUS_REVIEW ? $import->id : []);
    }

    public function review(int $id, DocumentDuplicateService $duplicates): View
    {
        $import = $this->authorized($id);
        $data = $import->reviewed_data ?: $import->extracted_data ?: ['mode' => 'single'];
        $rows = ($data['mode'] ?? 'single') === 'bulk' ? ($data['rows'] ?? []) : [$data];
        $duplicateRows = array_map(fn ($row) => $duplicates->suggestions($row), $rows);

        return view('admin::document-imports.review', compact('import', 'rows', 'duplicateRows'));
    }

    public function update(ReviewDocumentImportRequest $request, int $id): RedirectResponse
    {
        $import = $this->authorized($id);
        abort_unless(in_array($import->status, [DocumentImport::STATUS_REVIEW, DocumentImport::STATUS_EXTRACTED], true), 409);
        $import->update(['reviewed_data' => $request->reviewedData(), 'status' => DocumentImport::STATUS_REVIEW]);
        session()->flash('success', trans('admin::app.document-imports.messages.saved'));

        return back();
    }

    public function import(ReviewDocumentImportRequest $request, int $id, DocumentImportService $service): RedirectResponse
    {
        $import = $this->authorized($id);
        abort_unless($import->status === DocumentImport::STATUS_REVIEW, 409);
        $reviewed = $request->reviewedData();
        $import->update(['reviewed_data' => $reviewed]);
        $owner = (int) ($request->validated('owner_id') ?: auth()->guard('user')->id());
        if (($authorizedIds = bouncer()->getAuthorizedUserIds()) && ! in_array($owner, $authorizedIds, true)) {
            abort(403);
        }
        $service->import($import, $reviewed, $owner);
        session()->flash('success', trans('admin::app.document-imports.messages.imported'));

        return redirect()->route('admin.document_imports.index');
    }

    public function download(int $id): StreamedResponse
    {
        $import = $this->authorized($id);
        abort_unless(Storage::disk(config('document_imports.disk'))->exists($import->stored_path), 404);

        return Storage::disk(config('document_imports.disk'))->download($import->stored_path, $import->original_filename);
    }

    public function destroy(int $id): JsonResponse|RedirectResponse
    {
        $import = $this->authorized($id);
        Storage::disk(config('document_imports.disk'))->delete($import->stored_path);
        $import->delete();
        if (request()->ajax()) {
            return response()->json(['message' => trans('admin::app.document-imports.messages.deleted')]);
        }

        return redirect()->route('admin.document_imports.index');
    }

    private function authorized(int $id): DocumentImport
    {
        $query = DocumentImport::query();
        if ($ids = bouncer()->getAuthorizedUserIds()) {
            $query->whereIn('user_id', $ids);
        }

        return $query->findOrFail($id);
    }
}
