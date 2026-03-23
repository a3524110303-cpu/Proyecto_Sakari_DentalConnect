<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Adds missing columns that were referenced in models/controllers
 * but never included in the original migration.
 *
 * Changes:
 *  1. citas             → adds `notas` TEXT NULL
 *  2. notificaciones    → adds timestamps, expands `tipo` enum (adds 'reagenda'),
 *                         expands `estado` enum (adds 'no_leida')
 *  3. clinicas          → adds normalised address fields + GPS coordinates
 */
return new class extends Migration {
    public function up(): void
    {
        // 1. CITAS: Agregar columna notas
        if (Schema::hasTable('citas') && !Schema::hasColumn('citas', 'notas')) {
            Schema::table('citas', function (Blueprint $table) {
                $table->text('notas')->nullable();
            });
        }

        // 2. NOTIFICACIONES: Timestamps y expansión de ENUMs
        if (Schema::hasTable('notificaciones')) {
            // Timestamps si faltan
            if (!Schema::hasColumn('notificaciones', 'created_at')) {
                Schema::table('notificaciones', function (Blueprint $table) {
                    $table->timestamps();
                });
            }

            // Fix para Railway: Durante el proceso de "Build", Railway corre las migraciones
            // usando un SQLite temporal. SQLite NO soporta modificar o alterar ENUMs.
            // Por lo tanto, solo corremos la modificación si la base de datos de producción es MySQL.
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE notificaciones MODIFY COLUMN tipo ENUM(
                    'recordatorio','confirmacion','cancelacion','push','reagenda'
                ) NULL");

                DB::statement("ALTER TABLE notificaciones MODIFY COLUMN estado ENUM(
                    'pendiente','enviado','leido','no_leida'
                ) NULL");
            }
        }

        // 3. CLINICAS: Dirección normalizada y GPS
        if (Schema::hasTable('clinicas')) {
            Schema::table('clinicas', function (Blueprint $table) {
                if (!Schema::hasColumn('clinicas', 'calle')) {
                    $table->string('calle', 150)->nullable();
                }
                if (!Schema::hasColumn('clinicas', 'ciudad')) {
                    $table->string('ciudad', 100)->nullable();
                }
                if (!Schema::hasColumn('clinicas', 'municipio')) {
                    $table->string('municipio', 100)->nullable();
                }
                if (!Schema::hasColumn('clinicas', 'pais')) {
                    $table->string('pais', 50)->default('Mexico');
                }
                if (!Schema::hasColumn('clinicas', 'latitud')) {
                    $table->decimal('latitud', 10, 8)->nullable();
                }
                if (!Schema::hasColumn('clinicas', 'longitud')) {
                    $table->decimal('longitud', 11, 8)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('clinicas')) {
            Schema::table('clinicas', function (Blueprint $table) {
                $cols = ['calle', 'ciudad', 'municipio', 'pais', 'latitud', 'longitud'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('clinicas', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('notificaciones')) {
            // Revertir ENUMs solo en MySQL
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE notificaciones MODIFY COLUMN tipo ENUM('recordatorio','confirmacion','cancelacion','push') NULL");
                DB::statement("ALTER TABLE notificaciones MODIFY COLUMN estado ENUM('pendiente','enviado','leido') NULL");
            }
        }

        if (Schema::hasTable('citas') && Schema::hasColumn('citas', 'notas')) {
            Schema::table('citas', function (Blueprint $table) {
                $table->dropColumn('notas');
            });
        }
    }
};
