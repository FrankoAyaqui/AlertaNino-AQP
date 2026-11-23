<?php

namespace App\Services\Oficial;

use RuntimeException;

/**
 * Lee la copia pública de la última lectura oficial hecha desde Perú.
 *
 * SENAMHI no responde a servidores fuera del país. Si este servidor no puede
 * leer SENAMHI directamente, usa esta copia (generada por
 * scripts/publicar_datos_oficiales.ps1) y marca la lectura como "espejo".
 */
class EspejoService
{
    use ClienteHttp;

    public function leer(): array
    {
        $datos = $this->http()->get(config('alerta.espejo'), ['m' => now()->format('YmdHi')])->throw()->json();
        if (! is_array($datos) || ! isset($datos['generado'])) {
            throw new RuntimeException('El espejo no tiene el formato esperado.');
        }

        return $datos;
    }

    /**
     * Niveles por distrito según el espejo, indexados por el slug del distrito.
     *
     * @return array{consultado:string, niveles: array<string, array>}
     */
    public function lluvia24h(): array
    {
        $s = $this->leer()['senamhi'] ?? null;
        if (! is_array($s['por_distrito'] ?? null) || empty($s['consultado'])) {
            throw new RuntimeException('El espejo no tiene lecturas de SENAMHI.');
        }
        $niveles = [];
        foreach ($s['por_distrito'] as $slug => $d) {
            $l = $d['lluvia_24h'] ?? null;
            $niveles[$slug] = [
                'nivel' => (int) ($l['nivel'] ?? 0),
                'fecha' => $l['fecha'] ?? substr($s['consultado'], 0, 10),
                'descripcion' => $l['descripcion'] ?? null,
                'recomendacion' => $l['recomendacion'] ?? null,
            ];
        }

        return ['consultado' => $s['consultado'], 'niveles' => $niveles];
    }

    public function enfen(): array
    {
        $e = $this->leer()['enfen'] ?? null;
        if (! is_array($e) || empty($e['numero'])) {
            throw new RuntimeException('El espejo no tiene comunicado de ENFEN.');
        }

        return [
            'numero' => $e['numero'], 'fecha' => $e['fecha'], 'estado' => $e['estado'], 'titular' => $e['titular'],
            'resumen' => $e['resumen'], 'lluvias' => $e['lluvias'] ?? null, 'pdf_url' => $e['pdf'],
        ];
    }
}
