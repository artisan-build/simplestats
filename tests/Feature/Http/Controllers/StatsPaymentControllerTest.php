<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Instance;
use App\Models\Project;
use App\Models\StatsPayment;
use App\Models\StatsUser;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Api\StatsPaymentController
 */
final class StatsPaymentControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $statsPayments = StatsPayment::factory()->count(3)->create();

        $response = $this->get(route('stats-payment.index'));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\Api\StatsPaymentController::class,
            'store',
            \App\Http\Requests\Api\StatsPaymentRequest::class
        );
    }

    #[Test]
    public function store_saves(): void
    {
        $stats_user = StatsUser::factory()->create();
        $team = Team::factory()->create();
        $project = Project::factory()->create();
        $instance = Instance::factory()->create();
        $gross = $this->faker->numberBetween(-10000, 10000);
        $net = $this->faker->numberBetween(-10000, 10000);
        $time = $this->faker->date();

        $response = $this->post(route('stats-payment.store'), [
            'stats_user_id' => $stats_user->id,
            'team_id' => $team->id,
            'project_id' => $project->id,
            'instance_id' => $instance->id,
            'gross' => $gross,
            'net' => $net,
            'time' => $time,
        ]);

        $statsPayments = StatsPayment::query()
            ->where('stats_user_id', $stats_user->id)
            ->where('team_id', $team->id)
            ->where('project_id', $project->id)
            ->where('instance_id', $instance->id)
            ->where('gross', $gross)
            ->where('net', $net)
            ->where('time', $time)
            ->get();
        $this->assertCount(1, $statsPayments);
        $statsPayment = $statsPayments->first();

        $response->assertCreated();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $statsPayment = StatsPayment::factory()->create();

        $response = $this->get(route('stats-payment.show', $statsPayment));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\Api\StatsPaymentController::class,
            'update',
            \App\Http\Requests\StatsPaymentUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $statsPayment = StatsPayment::factory()->create();
        $stats_user = StatsUser::factory()->create();
        $team = Team::factory()->create();
        $project = Project::factory()->create();
        $instance = Instance::factory()->create();
        $gross = $this->faker->numberBetween(-10000, 10000);
        $net = $this->faker->numberBetween(-10000, 10000);
        $time = $this->faker->date();

        $response = $this->put(route('stats-payment.update', $statsPayment), [
            'stats_user_id' => $stats_user->id,
            'team_id' => $team->id,
            'project_id' => $project->id,
            'instance_id' => $instance->id,
            'gross' => $gross,
            'net' => $net,
            'time' => $time,
        ]);

        $statsPayment->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($stats_user->id, $statsPayment->stats_user_id);
        $this->assertEquals($team->id, $statsPayment->team_id);
        $this->assertEquals($project->id, $statsPayment->project_id);
        $this->assertEquals($instance->id, $statsPayment->instance_id);
        $this->assertEquals($gross, $statsPayment->gross);
        $this->assertEquals($net, $statsPayment->net);
        $this->assertEquals(Carbon::parse($time), $statsPayment->time);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $statsPayment = StatsPayment::factory()->create();

        $response = $this->delete(route('stats-payment.destroy', $statsPayment));

        $response->assertNoContent();

        $this->assertModelMissing($statsPayment);
    }
}
