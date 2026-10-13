<?php

namespace App\Mcp\Tools;

use App\DTOs\StatsFilterDto;
use App\Mcp\Tools\Concerns\ResolvesStatsFilters;
use App\Models\Project;
use App\Services\StatsDetailsService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Throwable;

#[Description('Get KPI time series (visitors, registrations, conversion rate, revenue, active users, ARPU, ...) for a date range, bucketed per hour/day/week/month. Also returns range totals so you never have to add numbers yourself. Keys prefixed pd_ are per bucket, lt_ are lifetime values up to that bucket. When called for all projects (project_id 0) the response additionally contains per_project range totals for breaking the answer down by project.')]
class GetKpiStats extends Tool
{
    use ResolvesStatsFilters;

    private const array TOTAL_KEYS = ['pd_visitor', 'pd_reg', 'pd_net', 'pd_gross', 'pd_fee'];

    private const array MONEY_KEYS = ['pd_net', 'pd_gross', 'pd_fee', 'pd_arpdau', 'lt_net', 'lt_arpu', 'lt_arppu', 'lt_arpv'];

    public function __construct(protected StatsDetailsService $statsDetailsService) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        try {
            $request->validate($this->rangeRules());

            $filters = $this->resolveFilters($request);
            $rows = $this->centsToCurrency($this->statsDetailsService->getStats($filters), self::MONEY_KEYS);

            return Response::structured(array_filter([
                'scope' => $this->describeScope($filters),
                'totals' => $this->totals($rows),
                'per_project' => $this->perProjectTotals($request, $filters),
                'latest_lifetime' => $this->latestLifetime($rows),
                'rows' => $rows,
                'note' => $rows === []
                    ? 'No data for this range/filter.'
                    : 'Rows are ordered newest first (date_clear). pd_* = value within the bucket, lt_* = lifetime value up to and including the bucket. Monetary values are in the scope currency.',
            ], fn (array|null|string $value): bool => $value !== null));
        } catch (ValidationException $e) {
            return Response::error('Invalid arguments: '.collect($e->errors())->flatten()->implode(' '));
        } catch (Throwable $e) {
            report($e);

            return Response::error('Could not load KPI stats. The error was reported, tell the user to try again or rephrase.');
        }
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->rangeSchema($schema);
    }

    /**
     * @return array<int, array{project: string, totals: array<string, float|int>}>|null
     */
    private function perProjectTotals(Request $request, StatsFilterDto $filters): ?array
    {
        return $this->perProjectBreakdown($filters, function (int $projectId) use ($request): array {
            $rows = $this->centsToCurrency(
                $this->statsDetailsService->getStats($this->resolveFilters($request, $projectId)),
                self::MONEY_KEYS,
            );

            return ['totals' => $this->totals($rows)];
        });
    }

    /**
     * @param  array<string, array<string, mixed>>  $rows  keyed by date_clear
     * @return array<string, float|int>
     */
    private function totals(array $rows): array
    {
        $first = collect($rows)->first() ?? [];
        $totals = [];

        foreach (self::TOTAL_KEYS as $key) {
            if (isset($first[$key])) {
                $totals[$key] = round(array_sum(array_column($rows, $key)), 2);
            }
        }

        return $totals;
    }

    /**
     * Lifetime values from the most recent bucket (rows are ordered newest first).
     *
     * @param  array<string, array<string, mixed>>  $rows  keyed by date_clear
     * @return array<string, mixed>
     */
    private function latestLifetime(array $rows): array
    {
        $latest = collect($rows)->first() ?? [];

        return array_filter($latest, fn ($key): bool => str_starts_with($key, 'lt_'), ARRAY_FILTER_USE_KEY);
    }
}
