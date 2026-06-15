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
        Schema::create('stats_custom_properties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('project_id');
            $table->string('propertyable_type');
            $table->string('propertyable_id', 36);
            $table->unsignedBigInteger('stats_custom_property_name_id');
            $table->unsignedBigInteger('stats_custom_property_value_id');
            $table->timestamp('time');
            $table->timestamps();

            $table->index(['project_id', 'stats_custom_property_name_id', 'stats_custom_property_value_id'], 'stats_custom_properties_group_index');
            $table->index(['propertyable_type', 'propertyable_id']);
            $table->unique(['project_id', 'propertyable_type', 'propertyable_id', 'stats_custom_property_name_id'], 'stats_custom_properties_owner_name_unique');

            $table->index('team_id');
            $table->index('stats_custom_property_name_id');
            $table->index('stats_custom_property_value_id');

            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->onDelete('cascade');

            $table->foreign('stats_custom_property_name_id')
                ->references('id')
                ->on('stats_custom_property_names')
                ->onDelete('cascade');

            $table->foreign('stats_custom_property_value_id')
                ->references('id')
                ->on('stats_custom_property_values')
                ->onDelete('cascade');

            $table->foreign('team_id')
                ->references('id')
                ->on('teams')
                ->onDelete('cascade');
        });
    }
};
