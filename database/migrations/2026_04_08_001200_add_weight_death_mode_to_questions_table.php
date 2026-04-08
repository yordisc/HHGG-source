<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            // Peso de la pregunta para scoring ponderado
            $table->decimal('weight', 7, 4)->default(1.0000)->after('correct_option');

            // Modo de muerte súbita
            $table->string('sudden_death_mode')->default('none')->after('weight'); // 'none', 'fail_if_wrong', 'pass_if_correct'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn([
                'weight',
                'sudden_death_mode',
            ]);
        });
    }
};
