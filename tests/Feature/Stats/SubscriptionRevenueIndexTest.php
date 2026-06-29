<?php

use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->user->currentTeam->update(['currency' => 'USD']);
    Project::factory()->create(['team_id' => $this->user->currentTeam->id]);
    $this->actingAs($this->user);
});

test('subscription revenue route returns 200', function () {
    $this->get(route('revenue'))->assertStatus(200);
});

test('subscription revenue renders Revenue/Index with the shared filter props', function () {
    $this->get(route('revenue'))
        ->assertInertia(fn ($page) => $page
            ->component('Revenue/Index')
            ->has('teamStats')
            ->has('presets')
            ->has('filters')
            ->has('rangeTypes')
            ->has('usageBits')
            ->has('comparisonTypes')
        );
});

test('subscription revenue exposes mrr, movements and retention when the project tracks subscriptions', function () {
    Project::where('team_id', $this->user->currentTeam->id)->delete();

    $project = Project::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'usage' => Project::USAGE_VISITORS
            | Project::USAGE_USERS
            | Project::USAGE_PAYMENTS
            | Project::USAGE_SUBSCRIPTION,
    ]);

    $this->get(route('revenue', ['project_id' => $project->id]))
        ->assertInertia(fn ($page) => $page
            ->component('Revenue/Index')
            ->has('mrr', fn ($mrr) => $mrr
                ->has('mrr')
                ->has('arr')
                ->has('mrr_prev')
            )
            ->has('mrr_movements', fn ($movements) => $movements
                ->each(fn ($item) => $item
                    ->has('date')
                    ->has('date_clear')
                    ->has('starting_mrr')
                    ->has('new')
                    ->has('reactivation')
                    ->has('expansion')
                    ->has('contraction')
                    ->has('churn')
                    ->has('ending_mrr')
                    ->has('net_change')
                )
            )
            ->has('mrr_retention', fn ($retention) => $retention
                ->has('nrr')
                ->has('grr')
                ->has('quick_ratio')
                ->has('starting_mrr')
                ->has('ending_mrr')
                ->has('new')
                ->has('reactivation')
                ->has('expansion')
                ->has('contraction')
                ->has('churn')
            )
            ->has('revenue_churn', fn ($churn) => $churn
                ->each(fn ($item) => $item
                    ->has('date')
                    ->has('date_clear')
                    ->has('mrr')
                    ->has('gross_churn_rate')
                    ->has('net_churn_rate')
                    ->has('quick_ratio')
                )
            )
            ->has('mrr_series', fn ($series) => $series
                ->each(fn ($item) => $item
                    ->has('date')
                    ->has('date_clear')
                    ->has('mrr')
                    ->has('subscribers')
                )
            )
            ->has('ltv')
            ->has('time_to_pay')
        );
});

test('subscription revenue mrr props are null when the project has no payments tracking', function () {
    Project::where('team_id', $this->user->currentTeam->id)->delete();

    $project = Project::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'usage' => Project::USAGE_VISITORS,
    ]);

    $this->get(route('revenue', ['project_id' => $project->id]))
        ->assertInertia(fn ($page) => $page
            ->component('Revenue/Index')
            ->where('mrr', null)
            ->where('mrr_series', null)
            ->where('mrr_movements', null)
            ->where('mrr_retention', null)
            ->where('revenue_churn', null)
            ->where('ltv', null)
            ->where('time_to_pay', null)
        );
});

test('subscription revenue mrr props are null when the project tracks payments but not users', function () {
    Project::where('team_id', $this->user->currentTeam->id)->delete();

    $project = Project::factory()->create([
        'team_id' => $this->user->currentTeam->id,
        'usage' => Project::USAGE_VISITORS
            | Project::USAGE_PAYMENTS,
    ]);

    $this->get(route('revenue', ['project_id' => $project->id]))
        ->assertInertia(fn ($page) => $page
            ->component('Revenue/Index')
            ->where('mrr', null)
            ->where('mrr_series', null)
            ->where('mrr_movements', null)
            ->where('mrr_retention', null)
            ->where('revenue_churn', null)
            ->where('ltv', null)
            ->where('time_to_pay', null)
        );
});
