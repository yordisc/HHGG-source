<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->char('content_hash', 64)->nullable()->after('image_updated_at')->index();
            $table->char('verification_token_hash', 64)->nullable()->after('content_hash')->index();
            $table->timestamp('revoked_at')->nullable()->after('verification_token_hash')->index();
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn([
                'content_hash',
                'verification_token_hash',
                'revoked_at',
            ]);
        });
    }
};
