<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // SQLite tiene limitaciones para alterar tipos existentes sin recrear tabla.
            return;
        }

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE certification_drafts MODIFY pass_score_percentage DECIMAL(5,2) NOT NULL DEFAULT 70.00');
            DB::statement('ALTER TABLE certification_statistics MODIFY average_score DECIMAL(5,2) NULL');
            DB::statement('ALTER TABLE certification_statistics MODIFY average_time_seconds DECIMAL(10,2) NULL');

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE certification_drafts ALTER COLUMN pass_score_percentage TYPE DECIMAL(5,2) USING pass_score_percentage::DECIMAL(5,2)');
            DB::statement('ALTER TABLE certification_drafts ALTER COLUMN pass_score_percentage SET DEFAULT 70.00');

            DB::statement('ALTER TABLE certification_statistics ALTER COLUMN average_score TYPE DECIMAL(5,2) USING average_score::DECIMAL(5,2)');
            DB::statement('ALTER TABLE certification_statistics ALTER COLUMN average_time_seconds TYPE DECIMAL(10,2) USING average_time_seconds::DECIMAL(10,2)');
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE certification_drafts MODIFY pass_score_percentage FLOAT NOT NULL DEFAULT 70');
            DB::statement('ALTER TABLE certification_statistics MODIFY average_score FLOAT NULL');
            DB::statement('ALTER TABLE certification_statistics MODIFY average_time_seconds FLOAT NULL');

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE certification_drafts ALTER COLUMN pass_score_percentage TYPE REAL USING pass_score_percentage::REAL');
            DB::statement('ALTER TABLE certification_drafts ALTER COLUMN pass_score_percentage SET DEFAULT 70');

            DB::statement('ALTER TABLE certification_statistics ALTER COLUMN average_score TYPE REAL USING average_score::REAL');
            DB::statement('ALTER TABLE certification_statistics ALTER COLUMN average_time_seconds TYPE REAL USING average_time_seconds::REAL');
        }
    }
};
