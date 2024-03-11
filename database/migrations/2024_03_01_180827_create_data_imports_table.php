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
            $table->foreignId('team_id')->constrained();
            $table->foreignId('project_id')->constrained();
            $table->foreignId('instance_id')->constrained();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('type');
            $table->integer('rows_max');
            $table->integer('rows_imported')->nullable();
            $table->integer('id_from')->nullable();
            $table->integer('id_to')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_imports');
    }
};
