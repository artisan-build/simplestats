<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stats_aggregations', function (Blueprint $table) {
            $table->id();
            // ensures all records gets deleted if a team/project/instance gets deleted
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('instance_id')->nullable()->constrained()->cascadeOnDelete();
            // various combinations of the applied filter: project, instance, tracking sources (stored as a md5 hash, without time/date)
            // as we have a unique index on filter_hash together with time, these both columns do not need an extra index
            $table->string('filter_hash', 32);
            $table->date('time');
            // it's also part of the filter_hash, but will leave it here so that the data is more visualizable
            $table->string('type');
            $table->json('data');
            // TODO update this field when the record gets selected and remove all which are not selected since a month
            $table->timestamp('selected_at')->nullable();

            $table->unique(['filter_hash', 'time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stats_aggregations');
    }
};
