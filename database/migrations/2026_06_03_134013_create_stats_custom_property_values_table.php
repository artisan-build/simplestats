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
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            // Explicit FK name: the auto-generated one exceeds the 64-char
            // identifier limit of MySQL (63 on PostgreSQL).
            $table->foreignId('stats_custom_property_name_id')->constrained(indexName: 'stats_custom_property_values_name_id_foreign')->cascadeOnDelete();
            $table->string('value');
            $table->timestamps();

            // Project-scoped via the name; the "All projects" view buckets
            // same-named values across projects at read time.
            $table->unique(['team_id', 'project_id', 'stats_custom_property_name_id', 'value'], 'stats_custom_property_values_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stats_custom_property_values');
    }
};
