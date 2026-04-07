<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('csv_import_logs', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->integer('file_size_bytes');
            $table->integer('total_rows');
            $table->integer('created_count')->default(0);
            $table->integer('updated_count')->default(0);
            $table->integer('translation_count')->default(0);
            $table->integer('skipped_count')->default(0);
            $table->json('errors')->nullable();
            $table->string('status')->default('completed'); // pending, completed, failed
            $table->json('preview_rows')->nullable(); // Primeras 5 filas para preview
            $table->timestamps();

            $table->index('created_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('csv_import_logs');
    }
};
