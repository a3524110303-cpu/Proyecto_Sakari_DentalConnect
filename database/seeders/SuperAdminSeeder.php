<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Verificar si existe al menos una clínica, si no, usar 1 por defecto
        $clinica = DB::table('clinicas')->first();
        $idClinica = $clinica ? $clinica->id_clinica : 1;

        // Crear o actualizar el Usuario Super Administrador
        DB::table('usuarios_sistema')->updateOrInsert(
            ['email' => 'superadmin@dentalconnect.com'],
            [
                'id_clinica' => $idClinica,
                'nombre_completo' => 'Super Administrador SaaS',
                'rol' => 'administrador',
                'password' => Hash::make('password123'),
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        );

        $this->command->info('¡Usuario Super Administrador creado o actualizado exitosamente!');
    }
}
