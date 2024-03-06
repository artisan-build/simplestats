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
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;

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
        // csv geht irgendwie nur mit txt, aber das soll im FE nicht angezeigt werden...
        $mimeTypes = collect(DataImportRequest::MIMETYPES)->filter(fn($type) => $type !== 'txt');

        return Inertia::render('DataImports/Index', [
            'dataImports' => $imports,
            'projects' => $projects,
            'instances' => $instances,
            'importTypes' => $importTypes,
            'mimeTypes' => $mimeTypes,
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

        $sheet = Excel::toArray(new HeadingRowImport(), $file);
        $headings = $sheet[0][0];

        $import = new DataImport(array_merge([
            'created_by' => $request->user()->id,
        ], $validated));

        $importer = DataImportType::from($request->type)->getImporter($import);
        $allowedFields = array_keys($importer->rules());

        if (!empty(array_diff($headings, $allowedFields))) {
            throw ValidationException::withMessages([
                'file' => ['Only the following fields are allowed: '.implode(', ', $allowedFields)],
            ]);
        }

        // only after header validation is done, we start the import...
        $import->save();

        $filepath = $file->store('imports', 'local');
        dispatch(new ProcessDataImportJob($import, $filepath));

        return redirect()->route('data-import.index')->banner('Import started');
    }

    public function destroy(Request $request, DataImport $dataImport): RedirectResponse
    {
        $dataImport->delete();

        return redirect()->route('data-import.index')->banner('Import aborted');
    }
}
