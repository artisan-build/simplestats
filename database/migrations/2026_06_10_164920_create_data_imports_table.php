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
        Schema::create('data_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('created_by');
            $table->string('type');
            $table->integer('rows_max');
            $table->integer('rows_imported')->nullable();
            $table->integer('id_from')->nullable();
            $table->integer('id_to')->nullable();
            $table->timestamps();
            $table->timestamp('rolled_back_at')->nullable();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->onDelete('cascade');

            $table->foreign('team_id')
                ->references('id')
                ->on('teams')
                ->onDelete('cascade');
        });
    }
};
