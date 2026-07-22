<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCatalogueImportRequest;
use App\Models\CatalogueImport;
use App\PermissionName;
use App\Services\ActivityLogger;
use App\Services\CatalogueImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CatalogueImportController extends Controller
{
    public function __construct(private CatalogueImportService $imports, private ActivityLogger $activityLogger) {}

    public function index(): Response
    {
        Gate::authorize(PermissionName::ImportPaceCatalogue->value);

        return Inertia::render('admin/catalogue-imports/Index', [
            'imports' => CatalogueImport::query()->with('uploader:id,name')->latest()->paginate(15),
        ]);
    }

    public function store(StoreCatalogueImportRequest $request): RedirectResponse
    {
        $file = $request->file('workbook');
        $path = $file->store('catalogue-imports', 'local');
        $import = CatalogueImport::query()->create([
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'checksum' => hash_file('sha256', $file->getRealPath()),
            'uploaded_by' => $request->user()->id,
        ]);
        $this->imports->parse($import);
        $this->activityLogger->record($request->user(), 'catalogue-import.uploaded', $import, newValues: $import->only(['original_name', 'checksum', 'status']));
        Inertia::flash('toast', ['type' => $import->fresh()->status === 'ready' ? 'success' : 'error', 'message' => 'Workbook validation completed.']);

        return redirect()->route('admin.catalogue-imports.show', $import);
    }

    public function show(CatalogueImport $catalogueImport): Response
    {
        Gate::authorize(PermissionName::ImportPaceCatalogue->value);
        $catalogueImport->load(['uploader:id,name', 'committer:id,name', 'rows']);

        return Inertia::render('admin/catalogue-imports/Show', ['catalogueImport' => $catalogueImport]);
    }

    public function commit(Request $request, CatalogueImport $catalogueImport): RedirectResponse
    {
        Gate::authorize(PermissionName::ImportPaceCatalogue->value);
        $counts = $this->imports->commit($catalogueImport, $request->user());
        $this->activityLogger->record($request->user(), 'catalogue-import.committed', $catalogueImport, newValues: $counts);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Catalogue import committed.']);

        return back();
    }

    public function cancel(Request $request, CatalogueImport $catalogueImport): RedirectResponse
    {
        Gate::authorize(PermissionName::ImportPaceCatalogue->value);
        abort_if($catalogueImport->status === 'committed', 422, 'Committed imports cannot be cancelled.');
        $catalogueImport->update(['status' => 'cancelled']);
        $this->activityLogger->record($request->user(), 'catalogue-import.cancelled', $catalogueImport);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Staged import cancelled; catalogue data was unchanged.']);

        return redirect()->route('admin.catalogue-imports.index');
    }
}
