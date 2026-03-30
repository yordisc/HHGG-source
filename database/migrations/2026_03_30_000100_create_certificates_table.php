<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table): void {
            $table->id();
            $table->string('serial', 30)->unique();
            $table->string('cert_type', 40)->index();
            $table->string('result_key', 80);

            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('country', 80);

            $table->string('document_hash', 255);
            $table->char('doc_lookup_hash', 64)->index();
            $table->string('doc_partial', 4)->nullable()->index();

            $table->unsignedTinyInteger('score_correct');
            $table->unsignedTinyInteger('score_incorrect');
            $table->unsignedTinyInteger('total_questions')->default(30);

            $table->timestamp('issued_at');
            $table->timestamp('expires_at')->index();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
