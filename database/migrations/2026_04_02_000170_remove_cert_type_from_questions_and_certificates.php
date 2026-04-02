<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table): void {
            $table->dropIndex('questions_cert_type_index');
            $table->dropIndex('questions_cert_type_active_index');
            $table->dropColumn('cert_type');
        });

        Schema::table('certificates', function (Blueprint $table): void {
            $table->dropIndex('certificates_cert_type_index');
            $table->dropColumn('cert_type');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table): void {
            $table->string('cert_type', 40)->nullable()->after('id')->index();
            $table->index(['cert_type', 'active']);
        });

        Schema::table('certificates', function (Blueprint $table): void {
            $table->string('cert_type', 40)->nullable()->after('serial')->index();
        });
    }
};
