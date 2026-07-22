<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->text('body');
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();

            $table->index('subject');
            $table->index('last_sent_at');
        });
    }
};
