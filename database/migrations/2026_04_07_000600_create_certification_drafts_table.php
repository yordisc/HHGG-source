<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('certification_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('questions_required')->default(30);
            $table->float('pass_score_percentage')->default(70);
            $table->integer('cooldown_days')->default(0);
            $table->string('result_mode')->default('binary_threshold');
            $table->string('pdf_view')->nullable();
            $table->integer('home_order')->default(999);
            $table->json('settings')->nullable();
            $table->integer('current_step')->default(1);
            $table->timestamps();
            $table->datetime('expires_at')->nullable();

            $table->index('user_id');
            $table->index('created_at');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certification_drafts');
    }
};
