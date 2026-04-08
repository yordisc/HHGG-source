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
        Schema::table('question_translations', function (Blueprint $table) {
            // Make options 3 and 4 nullable to support mcq_2 (2-option questions)
            $table->string('option_3')->nullable()->change();
            $table->string('option_4')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('question_translations', function (Blueprint $table) {
            // Revert to non-nullable (assuming they were previously non-nullable)
            $table->string('option_3')->change();
            $table->string('option_4')->change();
        });
    }
};
