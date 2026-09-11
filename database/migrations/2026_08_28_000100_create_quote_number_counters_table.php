<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_number_counters', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedBigInteger('last_number');
            $table->timestamps();
        });

        DB::statement(<<<'SQL'
            INSERT INTO quote_number_counters (year, last_number, created_at, updated_at)
            SELECT
                CAST(SUBSTRING(number FROM '^AF-([0-9]{4})-') AS SMALLINT),
                MAX(CAST(SUBSTRING(number FROM '([0-9]+)$') AS BIGINT)),
                CURRENT_TIMESTAMP,
                CURRENT_TIMESTAMP
            FROM quotes
            WHERE number ~ '^AF-[0-9]{4}-[0-9]+$'
            GROUP BY CAST(SUBSTRING(number FROM '^AF-([0-9]{4})-') AS SMALLINT)
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_number_counters');
    }
};
