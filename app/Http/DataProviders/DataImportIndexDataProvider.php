<?php

namespace App\Http\DataProviders;

use App\Enums\DataImportType;
use App\Http\Requests\DataImportRequest;
use App\Models\DataImport;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Junges\TrackableJobs\Enums\TrackedJobStatus;
use Junges\TrackableJobs\Models\TrackedJob;

class DataImportIndexDataProvider extends AbstractDataProvider
{
    public function __construct(
        private Request $request,
    ) {}

    public function toArray(): array
    {
        return [
            'dataImports' => $this->loadImports(),
            'projects' => $this->loadProjects(),
            'importTypes' => $this->buildImportTypes(),
            'mimeTypes' => $this->buildMimeTypes(),
            'filters' => $this->request->all(),
            'permissions' => $this->buildPermissions(),
        ];
    }

    private function loadImports(): LengthAwarePaginator
    {
        $imports = DataImport::with([
            'project',
            'createdBy',
            'trackedJob',
        ])->orderBy('created_at', 'desc')->paginate(10);

        return $this->ensureTrackedJobFallback($imports);
    }

    private function ensureTrackedJobFallback(LengthAwarePaginator $imports): LengthAwarePaginator
    {
        // if for some reason the import has no tracked job (shouldn't happen) and one, so that FE/Vue parses...
        $items = collect($imports->items());
        if ($items->isNotEmpty() && ! $items->first()->getRelation('trackedJob')) {
            TrackedJob::unguarded(function () use ($items) {
                $items->first()->setRelation('trackedJob', new TrackedJob([
                    'status' => TrackedJobStatus::Created,
                    'created_at' => $items->first()->created_at,
                ]));
            });

            $imports->setCollection($items);
        }

        return $imports;
    }

    private function loadProjects(): Collection
    {
        return Project::orderBy('created_at', 'desc')->get()->prepend(['uuid' => 0, 'name' => 'Please Select']);
    }

    private function buildImportTypes(): Collection
    {
        return collect(DataImportType::values())
            ->map(fn ($item) => ['id' => $item, 'name' => ucfirst($item)])
            ->prepend(['id' => null, 'name' => 'Please Select']);
    }

    private function buildMimeTypes(): Collection
    {
        // csv geht irgendwie nur mit txt, aber das soll im FE nicht angezeigt werden...
        return collect(DataImportRequest::MIMETYPES)->filter(fn ($type) => $type !== 'txt');
    }

    private function buildPermissions(): array
    {
        return [
            'canCreateDataImport' => Gate::check('create', DataImport::class),
        ];
    }
}
