<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table): void {
            $table->boolean('is_test_question')->default(false);
            $table->index(['certification_id', 'active', 'is_test_question'], 'questions_certification_active_test_index');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table): void {
            $table->dropIndex('questions_certification_active_test_index');
            $table->dropColumn('is_test_question');
        });
    }
};