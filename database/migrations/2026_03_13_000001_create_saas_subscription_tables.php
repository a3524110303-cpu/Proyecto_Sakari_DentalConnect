<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('saas_planes', function (Blueprint $table) {
            $table->id('id_plan');
            $table->string('slug', 30)->unique();
            $table->string('nombre', 50);
            $table->unsignedTinyInteger('nivel')->unique();
            $table->decimal('precio_mensual', 10, 2);
            $table->string('stripe_price_id', 120)->nullable();
            $table->unsignedInteger('max_doctores')->nullable();
            $table->unsignedInteger('max_pacientes')->nullable();
            $table->json('features')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('suscripciones_clinica', function (Blueprint $table) {
            $table->id('id_suscripcion');
            $table->unsignedBigInteger('id_clinica');
            $table->unsignedBigInteger('id_plan');
            $table->string('stripe_customer_id', 120)->nullable();
            $table->string('stripe_subscription_id', 120)->nullable()->unique();
            $table->string('stripe_checkout_session_id', 120)->nullable();
            $table->string('estado', 30)->default('pending');
            $table->string('moneda', 10)->default('mxn');
            $table->decimal('monto_periodo', 10, 2)->nullable();
            $table->timestamp('periodo_inicio')->nullable();
            $table->timestamp('periodo_fin')->nullable();
            $table->boolean('auto_renovar')->default(true);
            $table->timestamps();

            $table->foreign('id_clinica')->references('id_clinica')->on('clinicas')->onDelete('cascade');
            $table->foreign('id_plan')->references('id_plan')->on('saas_planes');
            $table->index(['id_clinica', 'estado'], 'idx_sub_clinica_estado');
        });

        DB::table('saas_planes')->insert([
            [
                'slug' => 'basic',
                'nombre' => 'Basico',
                'nivel' => 1,
                'precio_mensual' => 499.00,
                'max_doctores' => 2,
                'max_pacientes' => 500,
                'features' => json_encode([
                    'Agenda y expedientes',
                    'Dashboard operativo',
                    'Soporte por correo'
                ]),
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'premium',
                'nombre' => 'Premium',
                'nivel' => 2,
                'precio_mensual' => 999.00,
                'max_doctores' => 8,
                'max_pacientes' => 5000,
                'features' => json_encode([
                    'Todo en Basico',
                    'Publicidad y captacion',
                    'Analitica avanzada'
                ]),
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'ultra',
                'nombre' => 'Ultra',
                'nivel' => 3,
                'precio_mensual' => 1999.00,
                'max_doctores' => 999,
                'max_pacientes' => 999999,
                'features' => json_encode([
                    'Todo en Premium',
                    'Multi-sucursal',
                    'Soporte prioritario'
                ]),
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('suscripciones_clinica');
        Schema::dropIfExists('saas_planes');
    }
};
