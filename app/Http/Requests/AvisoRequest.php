<?php

namespace App\Http\Requests;

use App\Models\Aviso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validación en el servidor de un aviso oficial registrado por un operador. */
class AvisoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gestionar-contenido') ?? false;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:160'],
            'distrito_id' => ['nullable', 'integer', 'exists:distritos,id'],
            'fuente' => ['required', Rule::in(config('alerta.fuentes'))],
            'tipo' => ['required', Rule::in(array_keys(config('alerta.tipos_aviso')))],
            'nivel' => ['required', 'integer', 'between:1,4'],
            'mensaje' => ['required', 'string', 'min:15', 'max:3000'],
            'recomendacion' => ['nullable', 'string', 'max:1000'],
            // Solo enlaces https: evita esquemas peligrosos como javascript:
            'fuente_url' => ['required', 'url:https', 'max:500'],
            'emitido_en' => ['required', 'date', 'before_or_equal:now'],
            'vence_en' => array_merge(['required', 'date', 'after:emitido_en'],
                // Un aviso que se publica debe seguir vigente.
                $this->input('estado') === 'publicado' ? ['after:now'] : []),
            'estado' => ['required', Rule::in(Aviso::ESTADOS)],
        ];
    }

    public function attributes(): array
    {
        return ['fuente_url' => 'enlace a la fuente oficial', 'emitido_en' => 'fecha de emisión',
            'vence_en' => 'fecha de vencimiento', 'distrito_id' => 'distrito'];
    }
}
