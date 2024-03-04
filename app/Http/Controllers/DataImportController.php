<?php

namespace App\Http\Controllers;

use App\Enums\DataImportType;
use App\Http\Requests\DataImportRequest;
use App\Jobs\ProcessDataImportJob;
use App\Models\DataImport;
use App\Models\Instance;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DataImportController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(DataImport::class, 'data_import');
    }

    public function index(Request $request): Response
    {
        $imports = DataImport::with([
            'project',
            'instance',
            'createdBy',
            'trackedJob'
        ])->orderBy('created_at', 'desc')->paginate(10);

        $projects = Project::orderBy('created_at', 'desc')->get()->prepend(['id' => 0, 'name' => 'Please Select']);
        $instances = Instance::orderBy('created_at', 'desc')->get();

        $importTypes = collect(DataImportType::values())->map(fn($item) => ['id' => $item, 'name' => ucfirst($item)])->prepend(['id' => null, 'name' => 'Please Select']);

        return Inertia::render('DataImports/Index', [
            'dataImports' => $imports,
            'projects' => $projects,
            'instances' => $instances,
            'importTypes' => $importTypes,
            'filters' => $request->all(),
            'permissions' => [
                'canCreateDataImport' => Gate::check('create', DataImport::class),
            ]
        ]);
    }

    public function store(DataImportRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $file = $validated['file'];
        unset($validated['file']);

        $import = DataImport::create(array_merge([
            'created_by' => $request->user()->id,
        ], $validated));

        if (! empty($file)) {
            dispatch(new ProcessDataImportJob($import));
        }

        return redirect()->route('data-import.index')->banner('Import started');
    }

    public function destroy(Request $request, DataImport $dataImport): RedirectResponse
    {
        $dataImport->delete();

        return redirect()->route('data-import.index')->banner('Import aborted');
    }
}
