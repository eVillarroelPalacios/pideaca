<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Sync every Postgres sequence with the actual MAX(id) of its table.
     *
     * Rows inserted with an explicit id (seeders, pgAdmin, imported dumps)
     * do not advance the sequence, so the next insert reuses an existing id
     * and fails with a duplicate key violation.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        $columns = DB::select(<<<SQL
            SELECT table_name, column_name
            FROM information_schema.columns
            WHERE table_schema = 'public'
              AND (is_identity = 'YES' OR column_default LIKE 'nextval(%')
            ORDER BY table_name, ordinal_position
        SQL);

        foreach ($columns as $column) {
            $sequence = DB::selectOne(
                'SELECT pg_get_serial_sequence(?, ?) AS seq',
                [$column->table_name, $column->column_name]
            );

            if (!$sequence || $sequence->seq === null) {
                continue;
            }

            $max = (int) DB::selectOne(
                sprintf('SELECT COALESCE(MAX("%s"), 0) AS max_id FROM "%s"', $column->column_name, $column->table_name)
            )->max_id;

            DB::select(
                'SELECT setval(?::regclass, ?, ?) AS synced',
                [$sequence->seq, max($max, 1), $max > 0]
            );
        }
    }

    public function down(): void
    {
        //
    }
};
