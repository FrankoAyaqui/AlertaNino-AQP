<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DistritoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('administrar') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug((string) $this->input('nombre')),
            'limita_rio' => $this->boolean('limita_rio'),
            'es_piloto' => $this->boolean('es_piloto'),
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('distrito')?->id;

        return [
            'nombre' => ['required', 'string', 'max:100', Rule::unique('distritos', 'nombre')->ignore($id)],
            'slug' => ['required', 'string', 'max:100', Rule::unique('distritos', 'slug')->ignore($id)],
            'provincia' => ['required', 'string', 'max:60'],
            // Límites aproximados del departamento de Arequipa.
            'latitud' => ['required', 'numeric', 'between:-17.5,-14.5'],
            'longitud' => ['required', 'numeric', 'between:-75.2,-70.8'],
            'limita_rio' => ['boolean'],
            'es_piloto' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return ['slug.unique' => 'Ya existe un distrito con un nombre equivalente.'];
    }
}
