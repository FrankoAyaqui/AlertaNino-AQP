<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuiaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gestionar-contenido') ?? false;
    }

    protected function prepareForValidation(): void
    {
        // Una casilla sin marcar no se envía: se interpreta como "no publicado".
        $this->merge(['publicado' => $this->boolean('publicado')]);
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:160'],
            'categoria' => ['required', Rule::in(array_keys(config('alerta.categorias_guia')))],
            'contenido' => ['required', 'string', 'min:15', 'max:5000'],
            'fuente_url' => ['nullable', 'url:https', 'max:500'],
            'orden' => ['required', 'integer', 'between:0,999'],
            'publicado' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['fuente_url' => 'enlace a la fuente'];
    }
}
