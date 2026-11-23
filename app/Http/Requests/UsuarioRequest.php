<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Alta y edición de cuentas por un administrador (incluye el rol). */
class UsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('administrar') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['recibir_alertas' => $this->boolean('recibir_alertas')]);
    }

    public function rules(): array
    {
        $usuario = $this->route('usuario');

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario?->id)],
            'rol' => ['required', Rule::in(User::ROLES)],
            'distrito_id' => ['nullable', 'integer', 'exists:distritos,id'],
            'recibir_alertas' => ['boolean'],
            // Obligatoria al crear; opcional al editar (vacía = no se cambia).
            'password' => [$usuario ? 'nullable' : 'required', 'confirmed', Password::min(12)->mixedCase()->numbers()],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nombre', 'distrito_id' => 'distrito', 'password' => 'contraseña'];
    }
}
