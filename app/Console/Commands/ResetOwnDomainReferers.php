<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\StatsAggregation;
use App\Models\StatsTrackReferer;
use App\Models\StatsTrackSource;
use App\Models\StatsUser;
use App\Models\StatsVisitor;
use App\Services\RefererService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ResetOwnDomainReferers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset-own-domain-referers {--dry-run : Report how many records would be reset without writing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset referer/source values that point at a project\'s own domain back to Direct (null). Cleans up historical data where in-app navigation or a stale tracking session recorded the app\'s own domain.';

    /**
     * Execute the console command.
     */
    public function handle(RefererService $refererService): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $totalReferers = 0;
        $totalSources = 0;

        // Domains are project-scoped while the referer/source lookups are only
        // team-scoped, so a visitor of project A may legitimately have project B's
        // domain as a referer. We therefore match per project and only null the
        // foreign keys of the owning project's rows; the lookup rows stay intact.
        Project::query()
            ->whereNotNull('domain')
            ->where('domain', '!=', '')
            ->orderBy('id')
            ->each(function (Project $project) use ($refererService, $dryRun, &$totalReferers, &$totalSources) {
                $canonical = $refererService->getTopReferer($project->domain);

                if ($canonical === '') {
                    return;
                }

                $refererIds = $this->ownDomainLookupIds(new StatsTrackReferer, $project->team_id, $canonical, $refererService);
                $sourceIds = $this->ownDomainLookupIds(new StatsTrackSource, $project->team_id, $canonical, $refererService);

                if ($refererIds->isEmpty() && $sourceIds->isEmpty()) {
                    return;
                }

                if ($dryRun) {
                    $resetReferers = $this->countAffected($project->id, 'stats_track_referer_id', $refererIds);
                    $resetSources = $this->countAffected($project->id, 'stats_track_source_id', $sourceIds);
                } else {
                    [$resetReferers, $resetSources] = DB::transaction(function () use ($project, $refererIds, $sourceIds) {
                        $resetReferers = $this->resetColumn($project->id, 'stats_track_referer_id', $refererIds);
                        $resetSources = $this->resetColumn($project->id, 'stats_track_source_id', $sourceIds);

                        // Invalidate only the affected project's pre-computed aggregations;
                        // they are rebuilt lazily on the next read.
                        if ($resetReferers > 0 || $resetSources > 0) {
                            StatsAggregation::where('project_id', $project->id)->delete();
                        }

                        return [$resetReferers, $resetSources];
                    });
                }

                if ($resetReferers > 0 || $resetSources > 0) {
                    $this->line(sprintf(
                        ' - %s (#%d, %s): %d referers, %d sources',
                        $project->name,
                        $project->id,
                        $canonical,
                        $resetReferers,
                        $resetSources,
                    ));
                }

                $totalReferers += $resetReferers;
                $totalSources += $resetSources;
            });

        $verb = $dryRun ? 'Would reset' : 'Reset';
        $this->info(sprintf('%s %d own-domain referers and %d own-domain sources to Direct.', $verb, $totalReferers, $totalSources));

        return self::SUCCESS;
    }

    /**
     * Null the given lookup column on the project's visitor and user rows that
     * reference one of the own-domain lookup ids. Returns the number of rows reset.
     *
     * @param  Collection<int, int>  $lookupIds
     */
    private function resetColumn(int $projectId, string $column, Collection $lookupIds): int
    {
        if ($lookupIds->isEmpty()) {
            return 0;
        }

        return collect([new StatsVisitor, new StatsUser])->sum(
            fn ($model) => $this->affectedQuery($model, $projectId, $column, $lookupIds)->update([$column => null]),
        );
    }

    /**
     * Count the project's visitor and user rows that would be reset (dry-run).
     *
     * @param  Collection<int, int>  $lookupIds
     */
    private function countAffected(int $projectId, string $column, Collection $lookupIds): int
    {
        if ($lookupIds->isEmpty()) {
            return 0;
        }

        return collect([new StatsVisitor, new StatsUser])->sum(
            fn ($model) => $this->affectedQuery($model, $projectId, $column, $lookupIds)->count(),
        );
    }

    /**
     * @param  Collection<int, int>  $lookupIds
     */
    private function affectedQuery(StatsVisitor|StatsUser $model, int $projectId, string $column, Collection $lookupIds): Builder
    {
        return $model->newQuery()
            ->where('project_id', $projectId)
            ->whereIn($column, $lookupIds);
    }

    /**
     * Team-scoped lookup ids whose normalised name equals the project's own domain.
     *
     * @return Collection<int, int>
     */
    private function ownDomainLookupIds(StatsTrackReferer|StatsTrackSource $lookup, int $teamId, string $canonical, RefererService $refererService): Collection
    {
        return $lookup->newQuery()
            ->where('team_id', $teamId)
            ->get(['id', 'name'])
            ->filter(fn ($entry) => $refererService->getTopReferer($entry->name) === $canonical)
            ->pluck('id');
    }
}
