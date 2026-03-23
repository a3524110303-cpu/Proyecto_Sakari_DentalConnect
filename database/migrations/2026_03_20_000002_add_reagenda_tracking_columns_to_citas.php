<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('citas')) {
            Schema::table('citas', function (Blueprint $table) {
                if (!Schema::hasColumn('citas', 'reagenda_solicitada_at')) {
                    $table->dateTime('reagenda_solicitada_at')->nullable()->after('notas');
                }
                if (!Schema::hasColumn('citas', 'reagenda_fecha_solicitada')) {
                    $table->date('reagenda_fecha_solicitada')->nullable()->after('reagenda_solicitada_at');
                }
                if (!Schema::hasColumn('citas', 'reagenda_hora_solicitada')) {
                    $table->time('reagenda_hora_solicitada')->nullable()->after('reagenda_fecha_solicitada');
                }
                if (!Schema::hasColumn('citas', 'reagenda_motivo')) {
                    $table->text('reagenda_motivo')->nullable()->after('reagenda_hora_solicitada');
                }
                if (!Schema::hasColumn('citas', 'reagenda_estatus')) {
                    $table->enum('reagenda_estatus', ['pendiente', 'aplicada', 'expirada', 'rechazada'])
                        ->nullable()
                        ->after('reagenda_motivo');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('citas')) {
            Schema::table('citas', function (Blueprint $table) {
                $columnas = [
                    'reagenda_solicitada_at',
                    'reagenda_fecha_solicitada',
                    'reagenda_hora_solicitada',
                    'reagenda_motivo',
                    'reagenda_estatus',
                ];

                foreach ($columnas as $columna) {
                    if (Schema::hasColumn('citas', $columna)) {
                        $table->dropColumn($columna);
                    }
                }
            });
        }
    }
};
