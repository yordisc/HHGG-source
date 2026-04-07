<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('type')->default('mcq_4')->after('active'); // mcq_4, mcq_3, true_false, matching, fill_blank
            $table->text('explanation')->nullable()->after('correct_option'); // Explicación de la respuesta correcta
            $table->text('image_path')->nullable()->after('explanation'); // URL de imagen para la pregunta
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['type', 'explanation', 'image_path']);
        });
    }
};
