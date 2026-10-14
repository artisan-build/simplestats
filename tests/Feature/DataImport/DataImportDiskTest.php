<?php

use App\Enums\DataImportType;
use App\Jobs\ProcessDataImportJob;
use App\Models\DataImport;
use App\Models\Project;
use App\Models\StatsUser;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;

beforeEach(function (): void {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->currentTeam;
    $this->team->update(['timezone' => 'UTC']);
    $this->project = Project::factory()->create(['team_id' => $this->team->id]);
    $this->actingAs($this->user);

    Sleep::fake();
});

function usersCsv(string $time = '2026-10-01 10:00:00 +00:00'): string
{
    return "id,time\nimported-user-1,{$time}\n";
}

test('the upload lands on the shared disk, not on the local one', function (): void {
    Storage::fake('local');
    Storage::fake('bucket');
    config(['filesystems.shared' => 'bucket']);
    Queue::fake([ProcessDataImportJob::class]);

    $this->post(route('data-import.store'), [
        'project_id' => $this->project->uuid,
        'type' => DataImportType::USERS->value,
        'file' => UploadedFile::fake()->createWithContent('users.csv', usersCsv()),
    ])->assertRedirect(route('data-import.index'));

    $onBucket = Storage::disk('bucket')->files('imports');

    expect($onBucket)->toHaveCount(1)
        ->and(Storage::disk('local')->files('imports'))->toBe([]);

    Queue::assertPushed(ProcessDataImportJob::class, fn (ProcessDataImportJob $job): bool => $job->filepath === $onBucket[0]);
});

test('the shared disk is the local disk by default', function (): void {
    Storage::fake('local');
    Queue::fake([ProcessDataImportJob::class]);

    $this->post(route('data-import.store'), [
        'project_id' => $this->project->uuid,
        'type' => DataImportType::USERS->value,
        'file' => UploadedFile::fake()->createWithContent('users.csv', usersCsv()),
    ])->assertRedirect(route('data-import.index'));

    expect(config('filesystems.shared'))->toBe('local')
        ->and(Storage::disk('local')->files('imports'))->toHaveCount(1);
});

test('the job imports the rows from the shared disk and deletes the file there', function (): void {
    Storage::fake('bucket');
    config(['filesystems.shared' => 'bucket']);

    Storage::disk('bucket')->put('imports/users.csv', usersCsv());

    $dataImport = DataImport::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by' => $this->user->id,
        'type' => DataImportType::USERS,
        'rows_max' => 1,
    ]);

    dispatch_sync(new ProcessDataImportJob($dataImport, 'imports/users.csv'));

    expect(StatsUser::where('project_id', $this->project->id)->pluck('id')->all())->toBe(['imported-user-1'])
        ->and(Storage::disk('bucket')->exists('imports/users.csv'))->toBeFalse();
});
