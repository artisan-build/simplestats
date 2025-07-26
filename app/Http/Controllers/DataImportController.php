<?php

namespace App\Http\Controllers;

use App\Enums\DataImportType;
use App\Helpers\ImportHelper;
use App\Http\Requests\DataImportRequest;
use App\Jobs\ProcessDataImportJob;
use App\Models\DataImport;
use App\Models\Instance;
use App\Models\Project;
use App\Validators\BaseStatsValidator;
use Cache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Junges\TrackableJobs\Models\TrackedJob;

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
            'trackedJob',
        ])->orderBy('created_at', 'desc')->paginate(10);

        // if for some reason the import has no tracked job (shouldn't happen) and one, so that FE/Vue parses...
        $items = collect($imports->items());
        if ($items->isNotEmpty() && ! $items->first()->getRelation('trackedJob')) {
            TrackedJob::unguarded(function () use ($items) {
                $items->first()->setRelation('trackedJob', new TrackedJob([
                    'status' => 'unknown',
                    'created_at' => $items->first()->created_at,
                ]));
            });

            $imports->setCollection($items);
        }
        unset($items);

        $projects = Project::orderBy('created_at', 'desc')->get()->prepend(['id' => 0, 'name' => 'Please Select']);
        $instances = Instance::orderBy('created_at', 'desc')->get();

        $importTypes = collect(DataImportType::values())->map(fn ($item) => ['id' => $item, 'name' => ucfirst($item)])->prepend(['id' => null, 'name' => 'Please Select']);
        // csv geht irgendwie nur mit txt, aber das soll im FE nicht angezeigt werden...
        $mimeTypes = collect(DataImportRequest::MIMETYPES)->filter(fn ($type) => $type !== 'txt');

        return Inertia::render('DataImports/Index', [
            'dataImports' => $imports,
            'projects' => $projects,
            'instances' => $instances,
            'importTypes' => $importTypes,
            'mimeTypes' => $mimeTypes,
            'filters' => $request->all(),
            'permissions' => [
                'canCreateDataImport' => Gate::check('create', DataImport::class),
            ],
        ]);
    }

    public function store(DataImportRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $file = $validated['file'];
        unset($validated['file']);

        $dataImport = new DataImport(array_merge([
            'created_by' => $request->user()->id,
            'rows_max' => ImportHelper::countImportRows($file),
        ], $validated));

        $baseValidator = app(BaseStatsValidator::class);
        $baseValidator->validateImportFields($dataImport, ImportHelper::getImportFields($file));

        // only additional validation is done, we start the import...
        $dataImport->save();

        $filepath = $file->store('imports', 'local');
        dispatch(new ProcessDataImportJob($dataImport, $filepath));

        // w8 till the dispatched job is tracked
        sleep(1);

        return redirect()->route('data-import.index')->banner('Import started');
    }

    public function cancel(DataImport $dataImport): RedirectResponse
    {
        $this->authorize('cancel', $dataImport);

        Cache::put('cancel_data_import_'.$dataImport->id, true);

        // set rough defaults (the job is actually canceld in the queue)
        $dataImport->trackedJob->update([
            'status' => TrackedJob::STATUS_FAILED,
            'finished_at' => now(),
        ]);

        return redirect()->route('data-import.index')->banner('Import canceled');
    }

    public function rollback(DataImport $dataImport): RedirectResponse
    {
        $this->authorize('rollback', $dataImport);

        $model = $dataImport->type->getImporter($dataImport)->getModelClass();

        $model::whereBetween('id', [$dataImport->id_from, $dataImport->id_to])->delete();

        $dataImport->trackedJob->update([
            'status' => 'rolled back',
            'finished_at' => now(),
        ]);

        return redirect()->route('data-import.index')->banner('Import rolled back');
    }
}
