<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            // Increase serial column from 30 to 50 to accommodate UUIDs (36 chars) and custom formats
            $table->string('serial', 50)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tooLongCount = DB::table('certificates')
            ->whereRaw('CHAR_LENGTH(serial) > 30')
            ->count();

        if ($tooLongCount > 0) {
            throw new RuntimeException('Rollback bloqueado: existen seriales con longitud mayor a 30.');
        }

        Schema::table('certificates', function (Blueprint $table) {
            $table->string('serial', 30)->change();
        });
    }
};
