<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('usuarios_sistema', function (Blueprint $table) {
            if (!Schema::hasColumn('usuarios_sistema', 'telefono')) {
                $table->string('telefono', 20)->nullable()->after('email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuarios_sistema', function (Blueprint $table) {
            if (Schema::hasColumn('usuarios_sistema', 'telefono')) {
                $table->dropColumn('telefono');
            }
        });
    }
};
