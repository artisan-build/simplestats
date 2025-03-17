<?php

use App\Helpers\AppHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (AppHelper::getDBDriver() !== 'sqlite') {
            Schema::table('stats_payments', function (Blueprint $table) {
                $table->dropForeign('stats_payments_stats_user_id_instance_id_foreign');
            });
        }

        Schema::table('stats_payments', function (Blueprint $table) {
            $table->string('stats_user_id', 36)->nullable()->change();
            $table->foreign(['stats_user_id', 'instance_id'])
                ->references(['id', 'instance_id'])
                ->on('stats_users')
                ->cascadeOnDelete();
        });
    }
};
