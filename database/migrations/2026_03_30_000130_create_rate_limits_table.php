<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rate_limits', function (Blueprint $table): void {
            $table->id();
            $table->char('identifier_hash', 64)->index();
            $table->string('scope', 40)->default('quiz_start');
            $table->timestamp('attempted_at')->index();
            $table->timestamps();

            $table->index(['identifier_hash', 'scope', 'attempted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_limits');
    }
};
