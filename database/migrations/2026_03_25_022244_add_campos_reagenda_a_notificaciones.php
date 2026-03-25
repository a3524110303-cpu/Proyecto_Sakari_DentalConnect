<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notificaciones', function (Blueprint $table) {
            if (!Schema::hasColumn('notificaciones', 'id_cita')) {
                $table->unsignedBigInteger('id_cita')->nullable()->after('id_usuario');
            }
            if (!Schema::hasColumn('notificaciones', 'datos')) {
                $table->json('datos')->nullable()->after('mensaje');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notificaciones', function (Blueprint $table) {
            $columnas = [];
            if (Schema::hasColumn('notificaciones', 'id_cita')) {
                $columnas[] = 'id_cita';
            }
            if (Schema::hasColumn('notificaciones', 'datos')) {
                $columnas[] = 'datos';
            }

            if (!empty($columnas)) {
                $table->dropColumn($columnas);
            }
        });
    }
};
