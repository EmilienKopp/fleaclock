<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS daily_project_durations');
        DB::statement('DROP VIEW IF EXISTS daily_logs;');

        // PGSQL
        DB::statement('CREATE OR REPLACE VIEW daily_logs AS
            select
                tl.user_id,
                u.name as user_name,
                tl.project_id,
                p.name as project_name,
                tl.date,
                sum(
                    extract(
                    epoch
                    from
                        coalesce(tl.out_time, current_timestamp) - tl.in_time
                    )
                ) as total_seconds,
                sum(
                    extract(
                    epoch
                    from
                        coalesce(tl.out_time, current_timestamp) - tl.in_time
                    ) / 60::numeric
                ) as total_minutes
                from
                time_logs tl
                join users u on u.id = tl.user_id
                join projects p on p.id = tl.project_id
                group by
                tl.user_id,
                tl.project_id,
                u.name,
                p.name,
                tl.date;');
        
        // MYSQL
        // DB::statement('CREATE VIEW daily_logs AS
        // SELECT
        //     tl.user_id,
        //     u.name AS user_name,
        //     tl.project_id,
        //     p.name AS project_name,
        //     tl.date,
        //     SUM(
        //         TIMESTAMPDIFF(
        //             SECOND,
        //             tl.in_time,
        //             COALESCE(tl.out_time, NOW())
        //         )
        //     ) AS total_seconds,
        //     SUM(
        //         TIMESTAMPDIFF(
        //             SECOND,
        //             tl.in_time,
        //             COALESCE(tl.out_time, NOW())
        //         ) / 60
        //     ) AS total_minutes
        // FROM
        //     time_logs tl
        // JOIN users u ON u.id = tl.user_id
        // JOIN projects p ON p.id = tl.project_id
        // GROUP BY
        //     tl.user_id,
        //     tl.project_id,
        //     u.name,
        //     p.name,
        //     tl.date;'
        // );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS daily_logs');
    }
};
