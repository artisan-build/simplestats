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
        Schema::create('tracked_jobs', function (Blueprint $table) {
            $table->id();
            $table->integer('trackable_id');
            $table->string('trackable_type');
            $table->string('name');
            $table->string('status')->default('queued');
            $table->text('output')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->string('job_id')->nullable();
            $table->integer('attempts')->default(0);

            $table->index('trackable_id');
            $table->index('trackable_type');
        });
    }
};
