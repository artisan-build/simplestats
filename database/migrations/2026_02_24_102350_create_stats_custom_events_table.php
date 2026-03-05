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
        Schema::create('stats_custom_events', function (Blueprint $table) {
            $table->string('id', 36);
            $table->string('stats_user_id', 36)->nullable()->index();
            $table->unsignedBigInteger('stats_visitor_id')->nullable();
            $table->foreignId('stats_custom_event_name_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->timestamp('time');
            $table->timestamp('time_utc');
            $table->integer('counter')->default(1);

            $table->primary(['id', 'project_id']);

            $table->foreign(['stats_user_id', 'project_id'])
                ->references(['id', 'project_id'])
                ->on('stats_users')
                ->cascadeOnDelete();

            $table->foreign('stats_visitor_id')
                ->references('id')
                ->on('stats_visitors')
                ->cascadeOnDelete();

            $table->index(['team_id', 'time']);
            $table->index(['team_id', 'project_id', 'time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stats_custom_events');
    }
};
