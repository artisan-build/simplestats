<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Managed manually (no SoftDeletes trait): the Laravel SoftDeletes
            // trait collides with KeepsDeletedModels (duplicate restore()/
            // restoreQuietly(), and the deleted event would archive on soft
            // delete). A plain marker keeps the existing hard-delete + archival
            // path untouched and only adds a grace window.
            $table->timestamp('deleted_at')->nullable();
        });
    }
};
