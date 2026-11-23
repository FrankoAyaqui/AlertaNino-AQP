<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/** Registro público: siempre crea un ciudadano (el rol no se acepta del formulario). */
class RegistroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()],
            'distrito_id' => ['nullable', 'integer', 'exists:distritos,id'],
            'acepta_privacidad' => ['accepted'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nombre', 'password' => 'contraseña', 'distrito_id' => 'distrito',
            'acepta_privacidad' => 'política de privacidad'];
    }
}
