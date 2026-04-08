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
        Schema::table('certifications', function (Blueprint $table) {
            // Caducidad
            $table->string('expiry_mode')->default('indefinite')->after('description'); // 'indefinite' or 'fixed'
            $table->unsignedInteger('expiry_days')->nullable()->after('expiry_mode');

            // Descarga tras desactivación
            $table->boolean('allow_certificate_download_after_deactivation')->default(true)->after('expiry_days');

            // Retención y purga manual
            $table->boolean('manual_user_data_purge_enabled')->default(true)->after('allow_certificate_download_after_deactivation');

            // Requisito de banco de preguntas
            $table->boolean('require_question_bank_for_activation')->default(true)->after('manual_user_data_purge_enabled');

            // Randomización
            $table->boolean('shuffle_questions')->default(true)->after('require_question_bank_for_activation');
            $table->boolean('shuffle_options')->default(true)->after('shuffle_questions');

            // Reglas automáticas
            $table->string('auto_result_rule_mode')->default('none')->after('shuffle_options'); // 'none', 'name_rule'
            $table->json('auto_result_rule_config')->nullable()->after('auto_result_rule_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certifications', function (Blueprint $table) {
            $table->dropColumn([
                'expiry_mode',
                'expiry_days',
                'allow_certificate_download_after_deactivation',
                'manual_user_data_purge_enabled',
                'require_question_bank_for_activation',
                'shuffle_questions',
                'shuffle_options',
                'auto_result_rule_mode',
                'auto_result_rule_config',
            ]);
        });
    }
};
