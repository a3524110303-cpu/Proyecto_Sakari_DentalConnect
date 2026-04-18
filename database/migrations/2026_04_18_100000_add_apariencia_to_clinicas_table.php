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
        Schema::table('clinicas', function (Blueprint $table) {
            $table->string('tema_visual')->default('claro')->after('primer_ingreso')->comment('claro, oscuro, invertido');
            $table->string('color_primario')->default('#00b4d8')->after('tema_visual');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clinicas', function (Blueprint $table) {
            $table->dropColumn('tema_visual');
            $table->dropColumn('color_primario');
        });
    }
};
