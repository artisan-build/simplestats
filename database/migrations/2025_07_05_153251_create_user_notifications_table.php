<?php

use App\Enums\UserNotificationFrequency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('notifiable');
            $table->string('frequency')->default(UserNotificationFrequency::DAILY->value);
            $table->json('settings');
            $table->timestamps();

            $table->index('user_id');

            $table->unique(['user_id', 'notifiable_type', 'notifiable_id']);
        });
    }
};
