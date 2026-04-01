<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Usamos sentencias nativas porque Laravel/Doctrine tienen problemas modificando ENUMs con ->change()
        DB::statement("ALTER TABLE notificaciones MODIFY COLUMN tipo ENUM('recordatorio', 'confirmacion', 'cancelacion', 'push', 'reagenda') NULL");
        DB::statement("ALTER TABLE notificaciones MODIFY COLUMN estado ENUM('pendiente', 'enviado', 'leido', 'no_leida') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
