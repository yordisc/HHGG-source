<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('certificate_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('certification_id')->nullable()->constrained('certifications')->cascadeOnDelete();
            $table->string('slug', 255)->unique();
            $table->string('name', 255);
            $table->longText('html_template');
            $table->longText('css_template')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('certification_id');
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_templates');
    }
};
