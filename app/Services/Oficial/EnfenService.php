<?php

namespace App\Services\Oficial;

use Carbon\CarbonImmutable;
use RuntimeException;
use Smalot\PdfParser\Parser;

/**
 * Lee el último Comunicado Oficial de ENFEN.
 *
 * El sitio de ENFEN rechaza consultas automáticas, así que se usa el enlace que
 * publica SENAMHI en su página de El Niño (archivos 02204SENA-<n>.pdf).
 */
class EnfenService
{
    use ClienteHttp;

    private const MESES = ['enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4, 'mayo' => 5, 'junio' => 6,
        'julio' => 7, 'agosto' => 8, 'septiembre' => 9, 'setiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12];

    /** @return array{numero:string, fecha:string, estado:string, titular:string, resumen:string, lluvias:?string, pdf_url:string} */
    public function ultimoComunicado(): array
    {
        $html = $this->http()->get(config('alerta.senamhi.pagina_nino'))->throw()->body();
        preg_match_all('~load/file/02204SENA-(\d+)\.pdf~', $html, $m);
        if (empty($m[1])) {
            throw new RuntimeException('No se encontró el enlace al comunicado de ENFEN.');
        }
        $url = 'https://www.senamhi.gob.pe/load/file/02204SENA-'.max(array_map('intval', $m[1])).'.pdf';
        $pdf = $this->http()->get($url)->throw()->body();
        $texto = (new Parser)->parseContent($pdf)->getText();

        return self::interpretar($texto) + ['pdf_url' => $url];
    }

    /**
     * Extrae número, fecha, estado, titular, resumen y pronóstico de lluvias del texto del PDF.
     * Es público y estático para poder probarlo con el texto de un comunicado real.
     */
    public static function interpretar(string $texto): array
    {
        $t = trim(preg_replace('/\s+/u', ' ', $texto));
        $patron = '/COMUNICADO OFICIAL ENFEN N\s*[°º]?\s*(\d+\s*-\s*\d{4})\s+(\d{1,2})\s+([a-záéíóú]+)\s+(\d{4})\s+(.+?)\s+\d?\s*'
            .'([A-ZÁÉÍÓÚÑÜ0-9“”"\' ,;:.\-]{12,}?)\s+(?=[A-ZÁÉÍÓÚÑ][a-záéíóúñ])/u';
        if (! preg_match($patron, $t, $m, PREG_OFFSET_CAPTURE)) {
            throw new RuntimeException('No se reconoce el formato del comunicado de ENFEN.');
        }

        $mes = self::MESES[mb_strtolower($m[3][0])] ?? null;
        $estado = trim($m[5][0]);
        // Solo se aceptan los estados del sistema de alerta de ENFEN.
        if (! $mes || mb_strlen($estado) > 80 || ! preg_match('/alerta|vigilancia|no activo/i', $estado)) {
            throw new RuntimeException("Estado de ENFEN no reconocido: {$estado}");
        }

        $resto = substr($t, $m[0][1] + strlen($m[0][0]));
        $resumen = self::sinNotas(preg_replace('/\s*\(Figura \d+\)/u', '', explode('Figura 1.', $resto)[0]));

        $lluvias = null;
        if (preg_match('/(Las precipitaciones,.+?)(?=\x{f0b7}| Para el | Los caudales |$)/u', $resto, $l)) {
            $lluvias = self::sinNotas($l[1]);
        }

        return [
            'numero' => str_replace(' ', '', $m[1][0]),
            'fecha' => CarbonImmutable::create((int) $m[4][0], $mes, (int) $m[2][0])->toDateString(),
            'estado' => $estado,
            'titular' => trim($m[6][0]),
            'resumen' => mb_substr(trim($resumen), 0, 1500),
            'lluvias' => $lluvias ? mb_substr(trim($lluvias), 0, 1200) : null,
        ];
    }

    /** Quita las llamadas a notas al pie: "extraordinaria 2 hasta" -> "extraordinaria hasta". */
    private static function sinNotas(string $texto): string
    {
        $texto = preg_replace('/\b(20\d\d)\s?\d\b(?=\s|[,.;])/u', '$1', $texto);   // "2027 6" o "20276"
        // "extraordinaria2" o "extraordinaria 2", sin tocar decimales como "Niño 3.4"
        $texto = preg_replace('/(?<=[a-záéíóúñ])\s?\d(?=[\s,;]|\.(?!\d))/u', '', $texto);

        return preg_replace('/\s+/u', ' ', $texto);
    }
}
