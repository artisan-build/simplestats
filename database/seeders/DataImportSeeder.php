<?php

namespace Database\Seeders;

use App\Enums\DataImportType;
use App\Jobs\ProcessDataImportJob;
use App\Models\DataImport;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Junges\TrackableJobs\Enums\TrackedJobStatus;

class DataImportSeeder extends Seeder
{
    public function run(): void
    {
        $firstUser = User::first();
        auth()->setUser($firstUser);

        $firstProject = Project::first();

        DataImport::unguard();

        DataImport::create([
            'project_id' => $firstProject->id,
            'created_by' => $firstUser->id,
            'type' => DataImportType::USERS,
            'rows_max' => 8921,
            'rows_imported' => 8921,
            'created_at' => now()->subHours(10),
        ])->trackedJob()->create([
            'name' => ProcessDataImportJob::class,
            'status' => TrackedJobStatus::Finished->value,
            'started_at' => now()->subHours(10),
            'finished_at' => now()->subHours(8),
        ]);

        DataImport::create([
            'project_id' => $firstProject->id,
            'created_by' => $firstUser->id,
            'type' => DataImportType::PAYMENTS,
            'rows_max' => 2132,
            'rows_imported' => 698,
            'created_at' => now()->subHours(7),
        ])->trackedJob()->create([
            'name' => ProcessDataImportJob::class,
            'status' => TrackedJobStatus::Failed->value,
            'started_at' => now()->subHours(7),
            'finished_at' => now()->subHours(6),
        ]);

        DataImport::create([
            'project_id' => $firstProject->id,
            'created_by' => $firstUser->id,
            'type' => DataImportType::PAYMENTS,
            'rows_max' => 2132,
            'rows_imported' => 1587,
            'created_at' => now()->subHours(5),
        ])->trackedJob()->create([
            'name' => ProcessDataImportJob::class,
            'status' => TrackedJobStatus::Started->value,
            'started_at' => now()->subHours(5),
        ]);
    }
}
