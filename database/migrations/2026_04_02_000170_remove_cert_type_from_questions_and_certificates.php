<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('UPDATE questions q JOIN certifications c ON c.id = q.certification_id SET q.cert_type = c.slug WHERE q.certification_id IS NOT NULL');
            DB::statement('UPDATE certificates cert JOIN certifications c ON c.id = cert.certification_id SET cert.cert_type = c.slug WHERE cert.certification_id IS NOT NULL');

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('UPDATE questions q SET cert_type = c.slug FROM certifications c WHERE c.id = q.certification_id');
            DB::statement('UPDATE certificates cert SET cert_type = c.slug FROM certifications c WHERE c.id = cert.certification_id');

            return;
        }

        // SQLite u otros motores: backfill con subquery correlacionada.
        DB::table('questions')->update([
            'cert_type' => DB::raw('(SELECT slug FROM certifications WHERE certifications.id = questions.certification_id)'),
        ]);

        DB::table('certificates')->update([
            'cert_type' => DB::raw('(SELECT slug FROM certifications WHERE certifications.id = certificates.certification_id)'),
        ]);
    }
};
