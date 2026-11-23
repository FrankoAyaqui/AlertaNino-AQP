<?php

namespace Tests\Unit;

use App\Services\Oficial\EnfenService;
use App\Services\Oficial\SenamhiService;
use App\Support\Geometria;
use PHPUnit\Framework\TestCase;

class GeometriaYEnfenTest extends TestCase
{
    public function test_punto_dentro_y_fuera_de_un_poligono_con_hueco(): void
    {
        $g = ['type' => 'Polygon', 'coordinates' => [
            [[0, 0], [10, 0], [10, 10], [0, 10], [0, 0]],   // borde exterior
            [[4, 4], [6, 4], [6, 6], [4, 6], [4, 4]],         // hueco
        ]];
        $this->assertTrue(Geometria::contiene($g, 2, 2));
        $this->assertFalse(Geometria::contiene($g, 5, 5));  // en el hueco
        $this->assertFalse(Geometria::contiene($g, 12, 2)); // fuera
        $this->assertFalse(Geometria::contiene(null, 2, 2));
    }

    public function test_multipoligono(): void
    {
        $g = ['type' => 'MultiPolygon', 'coordinates' => [
            [[[0, 0], [1, 0], [1, 1], [0, 1], [0, 0]]],
            [[[5, 5], [6, 5], [6, 6], [5, 6], [5, 5]]],
        ]];
        $this->assertTrue(Geometria::contiene($g, 5.5, 5.5));
        $this->assertFalse(Geometria::contiene($g, 3, 3));
    }

    public function test_numero_de_nivel_de_senamhi(): void
    {
        $this->assertSame(3, SenamhiService::numeroNivel('Nivel 3'));
        $this->assertSame(0, SenamhiService::numeroNivel(null));
        $this->assertSame(4, SenamhiService::numeroNivel('Nivel 9'));
    }

    public function test_interpreta_el_comunicado_oficial_enfen_20_2026(): void
    {
        // Texto extraído del PDF público del Comunicado Oficial ENFEN N.° 20-2026.
        $r = EnfenService::interpretar(file_get_contents(__DIR__.'/../fixtures/enfen_20_2026.txt'));

        $this->assertSame('20-2026', $r['numero']);
        $this->assertSame('2026-11-13', $r['fecha']);
        $this->assertSame('Alerta de El Niño Costero', $r['estado']);
        $this->assertStringStartsWith('EL NIÑO COSTERO CONTINUARÍA', $r['titular']);
        $this->assertStringContainsString('magnitud extraordinaria hasta febrero de 2027', $r['resumen']); // sin la nota al pie
        $this->assertStringContainsString('Niño 3.4', $r['resumen']);
        $this->assertStringContainsString('sierra sur oriental', $r['lluvias']);
    }

    public function test_rechaza_un_texto_que_no_es_un_comunicado(): void
    {
        $this->expectException(\RuntimeException::class);
        EnfenService::interpretar('Documento cualquiera sin formato oficial');
    }
}
