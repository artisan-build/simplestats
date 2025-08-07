<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->date('stats_starting_at')->nullable();
            $table->string('client_version', 32)->nullable();
        });

        DB::table('instances')
            ->whereNotNull('project_id')
            ->select(['id', 'project_id', 'stats_starting_at', 'client_version'])
            ->orderBy('id')
            ->each(function ($instance) {
                DB::table('projects')
                    ->where('id', $instance->project_id)
                    ->update([
                        'stats_starting_at' => $instance->stats_starting_at,
                        'client_version' => $instance->client_version,
                    ]);
            });

        DB::table('personal_access_tokens')
            ->where('tokenable_type', 'App\\Models\\Instance')
            ->orderBy('id')
            ->each(function ($token) {
                $instance = DB::table('instances')
                    ->where('id', $token->tokenable_id)
                    ->first();

                if ($instance && $instance->project_id) {
                    DB::table('personal_access_tokens')
                        ->where('id', $token->id)
                        ->update([
                            'tokenable_type' => 'App\\Models\\Project',
                            'tokenable_id' => $instance->project_id,
                        ]);
                }
            });

        DB::table('personal_access_tokens')
            ->where('tokenable_type', 'App\\Models\\Instance')
            ->orderBy('id')
            ->delete();

        collect([
            'data_imports',
            'stats_aggregations',
            'stats_logins',
            'stats_payments',
            'stats_users',
            'stats_visitors',
        ])->each(function ($table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('instance_id');
            });
        });

        Schema::dropIfExists('instances');

        DB::table('stats_aggregations')->truncate();

        Schema::table('stats_users', function (Blueprint $table) {
            $table->primary(['id', 'project_id']);
        });

        Schema::table('stats_payments', function (Blueprint $table) {
            $table->primary(['id', 'project_id']);

            $table->foreign(['stats_user_id', 'project_id'])
                ->references(['id', 'project_id'])
                ->on('stats_users')
                ->cascadeOnDelete();
        });

        Schema::table('stats_logins', function (Blueprint $table) {
            $table->foreign(['stats_user_id', 'project_id'])
                ->references(['id', 'project_id'])
                ->on('stats_users')
                ->cascadeOnDelete();
        });

        Schema::table('stats_visitors', function (Blueprint $table) {
            $table->unique(['team_id', 'project_id', 'visitor_hash']);
        });
    }
};
