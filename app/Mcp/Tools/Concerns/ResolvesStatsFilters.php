<?php

namespace App\Mcp\Tools\Concerns;

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangePreset;
use App\Services\Ai\AiStatsFilterFactory;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;

trait ResolvesStatsFilters
{
    /**
     * @return array<string, JsonSchema>
     */
    protected function rangeSchema(JsonSchema $schema): array
    {
        return [
            'preset' => $schema->string()
                ->enum(collect(StatsRangePreset::cases())->reject(fn (StatsRangePreset $preset) => $preset === StatsRangePreset::CUSTOM)->pluck('value')->all())
                ->description('Named date range preset. Prefer this for relative ranges like "last month". Either preset or start_date/end_date must be given.'),
            'start_date' => $schema->string()
                ->description('Range start date (YYYY-MM-DD), only when no preset fits.'),
            'end_date' => $schema->string()
                ->description('Range end date (YYYY-MM-DD), required with start_date.'),
            'project_id' => $schema->integer()
                ->description('Project ID to scope to. Use 0 for all projects (default).'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rangeRules(): array
    {
        return [
            'preset' => ['required_without:start_date', 'nullable', Rule::enum(StatsRangePreset::class)->except([StatsRangePreset::CUSTOM])],
            'start_date' => ['required_without:preset', 'nullable', 'date_format:Y-m-d'],
            'end_date' => ['required_with:start_date', 'nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'project_id' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function resolveFilters(Request $request): StatsFilterDto
    {
        return app(AiStatsFilterFactory::class)->make(
            preset: $request->get('preset'),
            startDate: $request->get('start_date'),
            endDate: $request->get('end_date'),
            projectId: (int) ($request->get('project_id') ?? 0),
        );
    }

    /**
     * @return array{project: string, range: string, currency: string}
     */
    protected function describeScope(StatsFilterDto $filters): array
    {
        return [
            'project' => $filters->projectId === StatsFilterDto::TRACK_ALL
                ? 'All projects'
                : "Project {$filters->projectId}",
            'range' => sprintf(
                '%d %s back from %s (%s)',
                $filters->rangeAmount,
                $filters->rangeType->value,
                $filters->rangeStart->format('Y-m-d'),
                config('app.timezone'),
            ),
            'currency' => auth()->user()?->team?->currency ?? 'EUR',
        ];
    }

    /**
     * The stats pipeline stores and returns all monetary values in cents
     * (mirrors cent2Currency() in NumberHelpers.js). Convert before handing
     * numbers to the model, so it never reasons in cents.
     *
     * @param  array<array-key, array<string, mixed>>  $rows
     * @param  list<string>  $moneyKeys
     * @return array<array-key, array<string, mixed>>
     */
    protected function centsToCurrency(array $rows, array $moneyKeys): array
    {
        foreach ($rows as &$row) {
            foreach ($moneyKeys as $key) {
                if (isset($row[$key]) && is_numeric($row[$key])) {
                    $row[$key] = round($row[$key] / 100, 2);
                }
            }
        } unset($row);

        return $rows;
    }
}
