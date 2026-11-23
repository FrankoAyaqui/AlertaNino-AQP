<?php

namespace App\Support;

/**
 * Operaciones geométricas mínimas sobre GeoJSON (coordenadas [longitud, latitud]).
 * Se usan para saber en qué zona del aviso de SENAMHI cae el punto de cada distrito.
 */
final class Geometria
{
    /** ¿El punto (lon, lat) está dentro de un Polygon o MultiPolygon GeoJSON? */
    public static function contiene(?array $geometria, float $lon, float $lat): bool
    {
        if (! $geometria || ! isset($geometria['type'], $geometria['coordinates'])) {
            return false;
        }

        $poligonos = match ($geometria['type']) {
            'Polygon' => [$geometria['coordinates']],
            'MultiPolygon' => $geometria['coordinates'],
            default => [],
        };

        foreach ($poligonos as $poligono) {
            // El primer anillo es el borde exterior; los siguientes son huecos.
            if (! empty($poligono[0]) && self::enAnillo($lon, $lat, $poligono[0])) {
                $enHueco = false;
                foreach (array_slice($poligono, 1) as $hueco) {
                    if (self::enAnillo($lon, $lat, $hueco)) {
                        $enHueco = true;
                        break;
                    }
                }
                if (! $enHueco) {
                    return true;
                }
            }
        }

        return false;
    }

    /** Algoritmo de trazado de rayos (ray casting). */
    private static function enAnillo(float $lon, float $lat, array $anillo): bool
    {
        $dentro = false;
        $n = count($anillo);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            [$xi, $yi] = $anillo[$i];
            [$xj, $yj] = $anillo[$j];
            if ((($yi > $lat) !== ($yj > $lat)) && ($lon < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 1e-12) + $xi)) {
                $dentro = ! $dentro;
            }
        }

        return $dentro;
    }
}
