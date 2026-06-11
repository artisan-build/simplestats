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
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();

            // Polymorphic owner: a StatsUser OR a StatsVisitor (never both).
            // stats_users.id is a varchar(36) (it carries the customer's user id,
            // which may be a uuid) while stats_visitors.id is a bigint, so the id is
            // stored as a string(36) and visitor ids are cast to text when joining.
            // No DB foreign key is possible on a morph, so cascade cleanup on
            // entity/project deletion is handled at the application level.
            $table->string('propertyable_type');
            $table->string('propertyable_id', 36);
            $table->index(['propertyable_type', 'propertyable_id']);

            $table->foreignId('stats_custom_property_name_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stats_custom_property_value_id')->constrained()->cascadeOnDelete();

            // The time the value was current on the client (UTC). Only used to
            // drop out-of-order updates (see HasCustomProperties), never for
            // aggregations, so a single UTC timestamp without index suffices.
            $table->timestamp('time');

            $table->timestamps();

            // Guarantees exactly one current value per entity + property name. All
            // columns are NOT NULL, so this works identically on MySQL, PostgreSQL
            $table->unique(
                ['project_id', 'propertyable_type', 'propertyable_id', 'stats_custom_property_name_id'],
                'stats_custom_properties_owner_name_unique',
            );

            // Grouping path: aggregate stats by a property name, bucketed by value.
            $table->index(['project_id', 'stats_custom_property_name_id', 'stats_custom_property_value_id'], 'stats_custom_properties_group_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stats_custom_properties');
    }
};
