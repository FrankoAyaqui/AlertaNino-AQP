<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Crea o actualiza la cuenta de administrador a partir de variables de entorno
 * (ADMIN_EMAIL, ADMIN_PASSWORD, ADMIN_NOMBRE). Se usa al desplegar en un
 * servidor (Render) donde no hay terminal interactiva. La contraseña vive solo
 * en las variables de entorno del servidor, nunca en el código.
 */
class AdminDesdeEntorno extends Command
{
    protected $signature = 'alerta:admin-desde-entorno';

    protected $description = 'Crea o actualiza el administrador usando ADMIN_EMAIL, ADMIN_PASSWORD y ADMIN_NOMBRE';

    public function handle(): int
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');
        $nombre = env('ADMIN_NOMBRE', 'Administrador');

        if (! $email || ! $password) {
            $this->info('Sin ADMIN_EMAIL/ADMIN_PASSWORD: no se crea administrador.');

            return self::SUCCESS;
        }

        $v = Validator::make(compact('email', 'password', 'nombre'), [
            'email' => ['required', 'email'],
            'password' => ['required', Password::min(12)->mixedCase()->numbers()],
            'nombre' => ['required', 'string', 'max:120'],
        ]);
        if ($v->fails()) {
            foreach ($v->errors()->all() as $e) {
                $this->error($e);
            }

            return self::FAILURE;
        }

        $u = User::firstOrNew(['email' => $email]);
        $u->name = $nombre;
        $u->password = $password; // el modelo lo guarda con hash bcrypt
        $u->rol = 'administrador';
        $u->save();

        $this->info("Administrador {$email} listo.");

        return self::SUCCESS;
    }
}
