<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gestionar-contenido') ?? false;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:120'],
            'telefono' => ['required', 'string', 'regex:/^[0-9 +()\-]{3,20}$/'],
            'tipo' => ['required', Rule::in(array_keys(config('alerta.tipos_contacto')))],
            'distrito_id' => ['nullable', 'integer', 'exists:distritos,id'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'fuente_url' => ['nullable', 'url:https', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return ['telefono.regex' => 'El teléfono solo puede tener números, espacios, +, guiones y paréntesis (3 a 20 caracteres).'];
    }

    public function attributes(): array
    {
        return ['distrito_id' => 'distrito', 'fuente_url' => 'enlace a la fuente'];
    }
}
