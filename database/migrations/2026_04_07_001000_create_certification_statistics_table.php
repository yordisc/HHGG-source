<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('certification_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certification_id')->constrained('certifications')->onDelete('cascade');
            $table->date('date');
            $table->integer('attempts_count')->default(0);
            $table->integer('completions_count')->default(0);
            $table->integer('passes_count')->default(0);
            $table->integer('failures_count')->default(0);
            $table->float('average_score')->nullable();
            $table->float('average_time_seconds')->nullable();
            $table->integer('unique_users')->default(0);
            $table->integer('abandonment_count')->default(0);
            $table->timestamps();

            $table->unique(['certification_id', 'date']);
            $table->index(['certification_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certification_statistics');
    }
};
