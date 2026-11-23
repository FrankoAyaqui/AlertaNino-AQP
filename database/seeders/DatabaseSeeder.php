<?php

namespace Database\Seeders;

use App\Models\Contacto;
use App\Models\Distrito;
use App\Models\Guia;
use Illuminate\Database\Seeder;

/**
 * Datos iniciales reales: distritos con su punto de referencia, guías de
 * preparación y teléfonos de emergencia nacionales.
 *
 * No se crean avisos ni usuarios: los avisos llegan de las fuentes oficiales
 * (php artisan alerta:sincronizar) y las cuentas de personal se crean con
 * php artisan alerta:crear-usuario (contraseña oculta, sin credenciales por defecto).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // [slug, nombre, latitud, longitud, limita con el río Chili, distrito del piloto]
        $distritos = [
            ['cercado', 'Arequipa (Cercado)', -16.3989, -71.5369, true, false],
            ['alto-selva-alegre', 'Alto Selva Alegre', -16.3780, -71.5180, true, true],
            ['cayma', 'Cayma', -16.3560, -71.5490, true, false],
            ['cerro-colorado', 'Cerro Colorado', -16.3720, -71.5880, false, false],
            ['characato', 'Characato', -16.4680, -71.4880, false, false],
            ['chiguata', 'Chiguata', -16.4020, -71.3930, false, false],
            ['jacobo-hunter', 'Jacobo Hunter', -16.4440, -71.5560, true, false],
            ['jlbyr', 'José Luis Bustamante y Rivero', -16.4230, -71.5220, false, false],
            ['mariano-melgar', 'Mariano Melgar', -16.4030, -71.5060, false, false],
            ['miraflores', 'Miraflores', -16.3920, -71.5180, false, true],
            ['paucarpata', 'Paucarpata', -16.4280, -71.4990, false, false],
            ['sabandia', 'Sabandía', -16.4560, -71.4930, false, false],
            ['sachaca', 'Sachaca', -16.4260, -71.5680, true, false],
            ['socabaya', 'Socabaya', -16.4680, -71.5290, false, true],
            ['tiabaya', 'Tiabaya', -16.4490, -71.5910, true, false],
            ['uchumayo', 'Uchumayo', -16.4240, -71.6700, true, false],
            ['yanahuara', 'Yanahuara', -16.3880, -71.5430, true, false],
            ['yura', 'Yura', -16.2520, -71.6810, false, false],
        ];
        foreach ($distritos as [$slug, $nombre, $lat, $lon, $rio, $piloto]) {
            Distrito::updateOrCreate(['slug' => $slug], [
                'nombre' => $nombre, 'provincia' => 'Arequipa', 'latitud' => $lat, 'longitud' => $lon,
                'limita_rio' => $rio, 'es_piloto' => $piloto,
            ]);
        }

        $guias = [
            ['antes', 'Conoce tu entorno', 'Ubica las torrenteras y quebradas cercanas a tu vivienda. No construyas ni acumules desmonte o basura en sus cauces, aunque estén secos.'],
            ['antes', 'Prepara tu vivienda', 'Limpia techos, canaletas y desagües antes de la temporada de lluvias (diciembre a marzo). Revisa paredes y techos con grietas.'],
            ['antes', 'Plan familiar', 'Acuerda con tu familia un punto de encuentro y un contacto fuera de la ciudad. Pregunta en tu municipalidad por las rutas y zonas seguras oficiales de tu sector.'],
            ['mochila', 'Mochila de emergencia', 'Agua, alimentos no perecibles, linterna, radio a pilas, botiquín, documentos en bolsa plástica, abrigo, mascarillas y un cargador portátil.'],
            ['durante', 'Aléjate de los cauces', 'Durante una lluvia intensa aléjate de torrenteras, quebradas y de la orilla del río Chili: el agua y el lodo pueden bajar de golpe.'],
            ['durante', 'No cruces calles inundadas', 'No cruces calles inundadas a pie ni en vehículo: 30 cm de agua en movimiento pueden arrastrar un automóvil.'],
            ['durante', 'Corta la electricidad', 'Si entra agua a tu vivienda, desconecta la electricidad desde el tablero y sigue las indicaciones de Defensa Civil por la radio local.'],
            ['despues', 'Regresa con seguridad', 'No regreses a zonas afectadas hasta que las autoridades lo indiquen. Revisa daños en paredes y techos antes de entrar.'],
            ['despues', 'Cuida tu salud', 'Consume agua hervida o embotellada y reporta emergencias al 115 o a tu municipalidad.'],
        ];
        foreach ($guias as $i => [$categoria, $titulo, $contenido]) {
            Guia::updateOrCreate(['titulo' => $titulo], [
                'categoria' => $categoria, 'contenido' => $contenido, 'orden' => $i + 1, 'publicado' => true,
                'fuente_url' => 'https://www.gob.pe/indeci',
            ]);
        }

        $contactos = [
            ['Defensa Civil (INDECI)', '115', 'emergencia', 'Emergencias y desastres.', 'https://www.gob.pe/indeci'],
            ['Bomberos', '116', 'emergencia', 'Cuerpo General de Bomberos Voluntarios del Perú.', 'https://www.bomberosperu.gob.pe/'],
            ['Policía Nacional', '105', 'emergencia', 'Central de emergencias de la PNP.', 'https://www.gob.pe/pnp'],
            ['SAMU', '106', 'salud', 'Atención médica de urgencia del MINSA.', 'https://www.gob.pe/minsa'],
        ];
        foreach ($contactos as [$nombre, $telefono, $tipo, $descripcion, $url]) {
            Contacto::updateOrCreate(['nombre' => $nombre], [
                'telefono' => $telefono, 'tipo' => $tipo, 'descripcion' => $descripcion, 'fuente_url' => $url,
            ]);
        }
    }
}
