<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatosInicialesSeeder extends Seeder
{
    public function run()
    {
        // 1. Crear la Clínica
        $idClinica = DB::table('clinicas')->insertGetId([
            'nombre_comercial' => 'Dental Connect Pro',
            'numero_telefono' => '5551234567',
            'localidad' => 'Ciudad de México',
            'estado' => 'CDMX',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Crear Catálogos Básicos (Si no los tienes llenos)
        DB::table('catalogo_servicios')->insert([
            ['id_clinica' => $idClinica, 'nombre_servicio' => 'Consulta General', 'precio_base' => 500.00, 'categoria' => 'Diagnóstico'],
            ['id_clinica' => $idClinica, 'nombre_servicio' => 'Limpieza Ultrasónica', 'precio_base' => 800.00, 'categoria' => 'Preventiva'],
            ['id_clinica' => $idClinica, 'nombre_servicio' => 'Resina', 'precio_base' => 600.00, 'categoria' => 'Restaurativa'],
            ['id_clinica' => $idClinica, 'nombre_servicio' => 'Extracción Simple', 'precio_base' => 450.00, 'categoria' => 'Cirugía'],
        ]);

        DB::table('catalogo_tipo_sangre')->insertOrIgnore([
            ['tipo' => 'O+'],
            ['tipo' => 'O-'],
            ['tipo' => 'A+'],
            ['tipo' => 'A-']
        ]);

        // 3. Crear Usuario Admin (Doctor)
        // OJO: La contraseña será 'password123'
        $idUsuario = DB::table('usuarios_sistema')->insertGetId([
            'id_clinica' => $idClinica,
            'nombre_completo' => 'Dr. Juan Pérez',
            'rol' => 'doctor',
            'email' => 'admin@dentalconnect.com',
            'password' => Hash::make('password123'), // <-- CAMBIO AQUÍ
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Crear el registro en la tabla 'doctores'
        $idDoctor = DB::table('doctores')->insertGetId([
            'id_usuario' => $idUsuario,
            'cedula_profesional' => '12345678',
            'created_at' => now(),
        ]);

        // 4.5 Crear Usuario Super Administrador (SaaS)
        DB::table('usuarios_sistema')->insertOrIgnore([
            'id_clinica' => $idClinica,
            'nombre_completo' => 'Super Administrador SaaS',
            'rol' => 'administrador',
            'email' => 'superadmin@dentalconnect.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 5. Crear un Paciente de Prueba
        // Primero el usuario del paciente (para la App)
        $idUserPaciente = DB::table('usuarios_sistema')->insertGetId([
            'id_clinica' => $idClinica,
            'nombre_completo' => 'Paciente Demo',
            'rol' => 'paciente',
            'email' => 'paciente@demo.com',
            'password' => Hash::make('paciente123'), // <-- CAMBIO AQUÍ
            'created_at' => now(),
        ]);
        // Ahora los datos médicos del paciente
        $idPaciente = DB::table('pacientes')->insertGetId([
            'id_usuario' => $idUserPaciente,
            'nombre' => 'María',
            'apellido_paterno' => 'Gómez',
            'telefono' => '5559876543',
            'fecha_nacimiento' => '1995-05-20',
            'sexo' => 'F',
            'correo_electronico' => 'paciente@demo.com',
            'tipo_sangre' => 'O+',
            'created_at' => now(),
        ]);

        // 6. Crear una Cita de Prueba para HOY (Para verla en el Dashboard)
        DB::table('citas')->insert([
            'id_clinica' => $idClinica,
            'id_paciente' => $idPaciente,
            'id_doctor' => $idDoctor,
            'id_servicio' => 1, // Consulta General
            'fecha_hora_inicio' => Carbon::now()->addHours(2), // Dentro de 2 horas
            'estado_cita' => 'pendiente',
            'created_at' => now(),
        ]);

        $this->command->info('¡Datos de prueba insertados correctamente!');
    }
}