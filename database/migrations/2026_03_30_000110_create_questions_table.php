<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table): void {
            $table->id();
            $table->string('cert_type', 40)->index();
            $table->text('prompt');
            $table->string('option_1');
            $table->string('option_2');
            $table->string('option_3');
            $table->string('option_4');
            $table->unsignedTinyInteger('correct_option');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['cert_type', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
