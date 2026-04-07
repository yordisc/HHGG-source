<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('certification_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certification_id')->constrained('certifications')->onDelete('cascade');
            $table->integer('version_number')->default(1);
            $table->json('snapshot'); // Snapshot de la certificación en este momento
            $table->json('questions_snapshot')->nullable(); // Snapshot de preguntas
            $table->string('change_reason')->nullable();
            $table->json('changes')->nullable(); // Diferencias con versión anterior
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['certification_id', 'version_number']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certification_versions');
    }
};
