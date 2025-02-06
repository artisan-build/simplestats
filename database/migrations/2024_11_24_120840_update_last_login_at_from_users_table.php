<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (config('database.default') === 'mysql') {
            DB::statement('UPDATE users u
                JOIN (
                    SELECT stats_user_id, MAX(time) AS max_time
                    FROM stats_logins
                    WHERE instance_id = 1
                    GROUP BY stats_user_id
                ) sl ON u.id = sl.stats_user_id
                SET u.last_login_at = sl.max_time;
            ');
        }
    }
};
