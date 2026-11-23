<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Crea un operador o administrador. La contraseña se pide de forma oculta:
 * el proyecto no trae credenciales por defecto.
 */
class CrearUsuario extends Command
{
    protected $signature = 'alerta:crear-usuario {email} {rol=administrador}';

    protected $description = 'Crea una cuenta de operador o administrador pidiendo la contraseña de forma oculta';

    public function handle(): int
    {
        $datos = [
            'email' => $this->argument('email'),
            'rol' => $this->argument('rol'),
            'name' => $this->ask('Nombre completo'),
            'password' => $this->secret('Contraseña (12+ caracteres, mayúscula, minúscula y número)'),
        ];

        $v = Validator::make($datos, [
            'email' => ['required', 'email', 'unique:users,email'],
            'rol' => ['required', 'in:operador,administrador'],
            'name' => ['required', 'string', 'max:120'],
            'password' => ['required', Password::min(12)->mixedCase()->numbers()],
        ]);
        if ($v->fails()) {
            foreach ($v->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $usuario = new User(['name' => $datos['name'], 'email' => $datos['email'], 'password' => $datos['password']]);
        $usuario->rol = $datos['rol']; // el rol se asigna explícitamente, no por asignación masiva
        $usuario->save();
        $this->info("Cuenta {$usuario->email} creada con rol {$usuario->rol}.");

        return self::SUCCESS;
    }
}
