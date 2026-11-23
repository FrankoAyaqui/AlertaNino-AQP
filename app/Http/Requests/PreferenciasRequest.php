<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Preferencias del propio usuario: nombre, distrito y aceptación de alertas. */
class PreferenciasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['recibir_alertas' => $this->boolean('recibir_alertas')]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'distrito_id' => ['nullable', 'integer', 'exists:distritos,id'],
            'recibir_alertas' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nombre', 'distrito_id' => 'distrito'];
    }
}
