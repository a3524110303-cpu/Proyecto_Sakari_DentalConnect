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
        Schema::table('pacientes', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by_doctor_id')->nullable()->after('is_active');
            
            // Si quieres puedes poner la foreign key, pero como es solo referencial y para aislar data,
            // no es estrictamente obligatorio, aunque es buena práctica.
            $table->foreign('created_by_doctor_id')
                  ->references('id_doctor')
                  ->on('doctores')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->dropForeign(['created_by_doctor_id']);
            $table->dropColumn('created_by_doctor_id');
        });
    }
};
