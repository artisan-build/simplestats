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
        Schema::create('stats_custom_property_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('stats_custom_property_name_id');
            $table->string('value');
            $table->timestamps();

            $table->unique(['team_id', 'project_id', 'stats_custom_property_name_id', 'value'], 'stats_custom_property_values_unique');

            $table->foreign('stats_custom_property_name_id', 'stats_custom_property_values_name_id_foreign')
                ->references('id')
                ->on('stats_custom_property_names')
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
