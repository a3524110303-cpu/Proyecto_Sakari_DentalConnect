<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctores', function (Blueprint $table) {
            $table->string('foto_perfil', 255)->nullable()->after('horario_default');
        });
    }

    public function down(): void
    {
        Schema::table('doctores', function (Blueprint $table) {
            $table->dropColumn('foto_perfil');
        });
    }
};
