<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table): void {
            $table->dropForeign(['certification_id']);
            $table->foreign('certification_id')
                ->references('id')
                ->on('certifications')
                ->restrictOnDelete();
        });

        Schema::table('certificates', function (Blueprint $table): void {
            $table->dropForeign(['certification_id']);
            $table->foreign('certification_id')
                ->references('id')
                ->on('certifications')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table): void {
            $table->dropForeign(['certification_id']);
            $table->foreign('certification_id')
                ->references('id')
                ->on('certifications')
                ->nullOnDelete();
        });

        Schema::table('certificates', function (Blueprint $table): void {
            $table->dropForeign(['certification_id']);
            $table->foreign('certification_id')
                ->references('id')
                ->on('certifications')
                ->nullOnDelete();
        });
    }
};
