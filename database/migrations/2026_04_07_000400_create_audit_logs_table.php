<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('action', 100); // create, update, delete, import, export
            $table->string('entity', 100); // Certification, Question, User, CertificateTemplate
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('entity_name', 255)->nullable(); // nombre/slug del objeto
            $table->longText('changes')->nullable(); // JSON diferencias
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index('action');
            $table->index('entity');
            $table->index('entity_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
