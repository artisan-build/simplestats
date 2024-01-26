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
        Schema::create('stats_logins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stats_user_id')->index();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instance_id')->constrained()->cascadeOnDelete();
            $table->date('time')->index();
            $table->integer('counter')->index();

            $table->foreign(['stats_user_id', 'instance_id'])
                ->references(['id', 'instance_id'])
                ->on('stats_users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stats_logins');
    }
};
