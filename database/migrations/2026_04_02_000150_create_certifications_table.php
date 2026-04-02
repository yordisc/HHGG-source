<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('certifications', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->tinyInteger('questions_required')->default(30);
            $table->decimal('pass_score_percentage', 5, 2)->default(66.67);
            $table->smallInteger('cooldown_days')->default(30);
            $table->string('result_mode', 60)->default('binary_threshold');
            $table->string('pdf_view', 120)->default('pdf.certificate');
            $table->smallInteger('home_order')->default(100);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['active', 'home_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certifications');
    }
};
