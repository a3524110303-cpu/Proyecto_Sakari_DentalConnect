<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            if (!Schema::hasColumn('citas', 'cuidados_pdf')) {
                $table->string('cuidados_pdf')->nullable()->after('notas');
            }

            if (!Schema::hasColumn('citas', 'tips_pdf')) {
                $table->string('tips_pdf')->nullable()->after('cuidados_pdf');
            }
        });
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $columnas = [];

            if (Schema::hasColumn('citas', 'tips_pdf')) {
                $columnas[] = 'tips_pdf';
            }

            if (Schema::hasColumn('citas', 'cuidados_pdf')) {
                $columnas[] = 'cuidados_pdf';
            }

            if (!empty($columnas)) {
                $table->dropColumn($columnas);
            }
        });
    }
};
