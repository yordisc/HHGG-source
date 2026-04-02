<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table): void {
            $table->foreignId('certification_id')
                ->nullable()
                ->after('cert_type')
                ->constrained('certifications')
                ->nullOnDelete();

            $table->index(['certification_id', 'active']);
        });

        Schema::table('certificates', function (Blueprint $table): void {
            $table->foreignId('certification_id')
                ->nullable()
                ->after('cert_type')
                ->constrained('certifications')
                ->nullOnDelete();

            $table->index(['certification_id', 'issued_at']);
        });

        $certificationMap = DB::table('certifications')->pluck('id', 'slug');

        foreach ($certificationMap as $slug => $id) {
            DB::table('questions')
                ->where('cert_type', $slug)
                ->update(['certification_id' => $id]);

            DB::table('certificates')
                ->where('cert_type', $slug)
                ->update(['certification_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table): void {
            $table->dropIndex('certificates_certification_id_issued_at_index');
            $table->dropConstrainedForeignId('certification_id');
        });

        Schema::table('questions', function (Blueprint $table): void {
            $table->dropIndex('questions_certification_id_active_index');
            $table->dropConstrainedForeignId('certification_id');
        });
    }
};
