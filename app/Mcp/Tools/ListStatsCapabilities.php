<?php

namespace App\Mcp\Tools;

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangePreset;
use App\Models\Project;
use App\Services\StatsService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Throwable;

#[Description('List the current team\'s projects, the available breakdown dimensions, date range presets, and a legend of all KPI keys. Call this first when unsure which project, dimension, or preset to use.')]
class ListStatsCapabilities extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        try {
            return Response::structured([
                'projects' => [
                    ['id' => StatsFilterDto::TRACK_ALL, 'name' => 'All projects'],
                    ...Project::query()->get(['id', 'name', 'usage'])->map(fn (Project $project) => [
                        'id' => $project->id,
                        'name' => $project->name,
                        'tracks_registrations' => $project->hasUsages([Project::USAGE_USERS]),
                        'tracks_subscriptions' => $project->hasUsages([Project::USAGE_SUBSCRIPTION]),
                    ])->all(),
                ],
                'dimensions' => StatsService::getStatsTypesFlatMap(['model', 'icon'])
                    ->map(fn (array $type) => ['dimension' => $type['type'], 'title' => $type['title']])
                    ->values()
                    ->all(),
                'presets' => collect(StatsRangePreset::cases())
                    ->reject(fn (StatsRangePreset $preset) => $preset === StatsRangePreset::CUSTOM)
                    ->map(fn (StatsRangePreset $preset) => ['preset' => $preset->value, 'title' => $preset->title()])
                    ->values()
                    ->all(),
                'kpis' => [
                    'pd_visitor' => 'Unique visitors in the bucket',
                    'pd_reg' => 'New registrations in the bucket',
                    'pd_cr' => 'Visitor-to-registration conversion rate in % for the bucket',
                    'pd_net' => 'Net revenue in the bucket',
                    'pd_gross' => 'Gross revenue in the bucket',
                    'pd_fee' => 'Fees (gross minus net) in the bucket',
                    'pd_dau' => 'Active users in the bucket',
                    'pd_arpdau' => 'Average net revenue per active user in the bucket',
                    'lt_visitor' => 'Lifetime unique visitors up to the bucket',
                    'lt_reg' => 'Lifetime registrations up to the bucket',
                    'lt_pu' => 'Lifetime paying users up to the bucket',
                    'lt_net' => 'Lifetime net revenue up to the bucket',
                    'lt_arpu' => 'Average lifetime net revenue per registered user',
                    'lt_arppu' => 'Average lifetime net revenue per paying user',
                    'lt_arpv' => 'Average lifetime net revenue per visitor',
                    'lt_reg2pu' => 'Registration-to-paying-user conversion in %',
                ],
            ]);
        } catch (Throwable $e) {
            report($e);

            return Response::error('Could not list stats capabilities: '.$e->getMessage());
        }
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
