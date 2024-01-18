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
        Schema::create('stats_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stats_user_id')->index();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('instance_id')->constrained()->onDelete('cascade');
            $table->float('gross');
            $table->float('net');
            $table->dateTime('time');
            $table->integer('counter');

            $table->foreign(['stats_user_id', 'instance_id'])
                ->references(['id', 'instance_id'])
                ->on('stats_users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stats_payments');
    }
};
