<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certifications', function (Blueprint $table): void {
            $table->string('featured_image_path')->nullable()->after('pdf_view');
        });
    }

    public function down(): void
    {
        Schema::table('certifications', function (Blueprint $table): void {
            $table->dropColumn('featured_image_path');
        });
    }
};
