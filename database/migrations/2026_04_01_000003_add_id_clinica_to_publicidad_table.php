<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega id_clinica a la tabla publicidad para aislar correctamente
 * las publicidades por clínica (multi-tenant) y eliminar el filtro
 * indirecto por id_usuario que era frágil y propenso a data leakage.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('publicidad') && ! Schema::hasColumn('publicidad', 'id_clinica')) {
            Schema::table('publicidad', function (Blueprint $table) {
                $table->unsignedBigInteger('id_clinica')->nullable()->after('id_publicidad');
                $table->foreign('id_clinica')->references('id_clinica')->on('clinicas')->onDelete('cascade');
            });

            // Poblar id_clinica en registros existentes usando la relación usuario→clínica
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                DB::statement('
                    UPDATE publicidad p
                    INNER JOIN usuarios_sistema u ON u.id_usuario = p.id_usuario
                    SET p.id_clinica = u.id_clinica
                    WHERE p.id_clinica IS NULL
                ');
            } else {
                // Fallback genérico para SQLite y otros drivers
                $registros = DB::table('publicidad')
                    ->whereNull('id_clinica')
                    ->get(['id_publicidad', 'id_usuario']);

                foreach ($registros as $registro) {
                    $idClinica = DB::table('usuarios_sistema')
                        ->where('id_usuario', $registro->id_usuario)
                        ->value('id_clinica');

                    if ($idClinica) {
                        DB::table('publicidad')
                            ->where('id_publicidad', $registro->id_publicidad)
                            ->update(['id_clinica' => $idClinica]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('publicidad') && Schema::hasColumn('publicidad', 'id_clinica')) {
            Schema::table('publicidad', function (Blueprint $table) {
                $table->dropForeign(['id_clinica']);
                $table->dropColumn('id_clinica');
            });
        }
    }
};
