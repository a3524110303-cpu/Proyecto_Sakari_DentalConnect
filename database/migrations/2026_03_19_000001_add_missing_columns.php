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
        // ──────────────────────────────────────────
        // 1. citas → agregar columna `notas`
        // ──────────────────────────────────────────
        if (!Schema::hasColumn('citas', 'notas')) {
            Schema::table('citas', function (Blueprint $table) {
                $table->text('notas')->nullable()->after('motivo');
            });
        }

        // ──────────────────────────────────────────
        // 2. notificaciones → timestamps + nuevos valores de enums
        // ──────────────────────────────────────────
        if (!Schema::hasColumn('notificaciones', 'created_at')) {
            Schema::table('notificaciones', function (Blueprint $table) {
                $table->timestamps();
            });
        }

        // MySQL no soporta ALTER COLUMN sobre enums con Blueprint,
        // así que usamos DB::statement para ampliar los valores permitidos.
        DB::statement("ALTER TABLE notificaciones MODIFY COLUMN tipo ENUM(
            'recordatorio','confirmacion','cancelacion','push','reagenda'
        ) NULL");

        DB::statement("ALTER TABLE notificaciones MODIFY COLUMN estado ENUM(
            'pendiente','enviado','leido','no_leida'
        ) NULL");

        // ──────────────────────────────────────────
        // 3. clinicas → dirección normalizada + GPS
        // ──────────────────────────────────────────
        Schema::table('clinicas', function (Blueprint $table) {
            if (!Schema::hasColumn('clinicas', 'calle')) {
                $table->string('calle', 150)->nullable()->after('numero_telefono');
            }
            if (!Schema::hasColumn('clinicas', 'ciudad')) {
                $table->string('ciudad', 100)->nullable()->after('calle');
            }
            if (!Schema::hasColumn('clinicas', 'municipio')) {
                $table->string('municipio', 100)->nullable()->after('ciudad');
            }
            if (!Schema::hasColumn('clinicas', 'pais')) {
                $table->string('pais', 50)->default('México')->after('estado');
            }
            if (!Schema::hasColumn('clinicas', 'latitud')) {
                $table->decimal('latitud', 10, 8)->nullable()->after('codigo_postal');
            }
            if (!Schema::hasColumn('clinicas', 'longitud')) {
                $table->decimal('longitud', 11, 8)->nullable()->after('latitud');
            }
        });
    }

    public function down(): void
    {
        // Revertir columnas de citas
        if (Schema::hasColumn('citas', 'notas')) {
            Schema::table('citas', function (Blueprint $table) {
                $table->dropColumn('notas');
            });
        }

        // Revertir timestamps de notificaciones
        if (Schema::hasColumn('notificaciones', 'created_at')) {
            Schema::table('notificaciones', function (Blueprint $table) {
                $table->dropColumn(['created_at', 'updated_at']);
            });
        }

        // Revertir enums de notificaciones a sus valores originales
        DB::statement("ALTER TABLE notificaciones MODIFY COLUMN tipo ENUM(
            'recordatorio','confirmacion','cancelacion','push'
        ) NULL");

        DB::statement("ALTER TABLE notificaciones MODIFY COLUMN estado ENUM(
            'pendiente','enviado','leido'
        ) NULL");

        // Revertir columnas de clinicas
        Schema::table('clinicas', function (Blueprint $table) {
            $drop = [];
            foreach (['calle', 'ciudad', 'municipio', 'pais', 'latitud', 'longitud'] as $col) {
                if (Schema::hasColumn('clinicas', $col)) {
                    $drop[] = $col;
                }
            }
            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
