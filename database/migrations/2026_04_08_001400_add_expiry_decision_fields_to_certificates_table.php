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
        Schema::table('certificates', function (Blueprint $table) {
            // Fechas de expiración
            $table->dateTime('certification_expires_at')->nullable()->after('completed_at');
            $table->dateTime('download_expires_at')->nullable()->after('certification_expires_at');

            // Fuente y razón de decisión del resultado
            $table->string('result_decision_source')->default('scoring')->after('download_expires_at'); // 'scoring', 'sudden_death', 'auto_name_rule'
            $table->text('result_decision_reason')->nullable()->after('result_decision_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn([
                'certification_expires_at',
                'download_expires_at',
                'result_decision_source',
                'result_decision_reason',
            ]);
        });
    }
};
