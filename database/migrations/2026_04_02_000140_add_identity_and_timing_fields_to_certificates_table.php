<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table): void {
            $table->char('country_code', 2)->nullable()->after('country')->index();
            $table->string('document_type', 30)->nullable()->after('country_code')->index();
            $table->char('identity_lookup_hash', 64)->nullable()->after('doc_lookup_hash')->index();
            $table->decimal('score_numeric', 5, 2)->nullable()->after('total_questions');
            $table->timestamp('completed_at')->nullable()->after('issued_at')->index();
            $table->timestamp('next_available_at')->nullable()->after('completed_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table): void {
            $table->dropColumn([
                'country_code',
                'document_type',
                'identity_lookup_hash',
                'score_numeric',
                'completed_at',
                'next_available_at',
            ]);
        });
    }
};
