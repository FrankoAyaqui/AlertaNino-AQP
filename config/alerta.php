<?php

/*
|--------------------------------------------------------------------------
| AlertaNiño AQP: fuentes oficiales y parámetros del dominio
|--------------------------------------------------------------------------
| Todas las URL son servicios públicos del Estado peruano. No se usa ninguna
| clave ni dato personal para consultarlos.
*/

return [

    // Tiempo máximo de espera por solicitud (segundos).
    'timeout' => (int) env('ALERTA_HTTP_TIMEOUT', 25),

    // Agente de usuario con el que se identifica la aplicación ante las fuentes.
    'agente' => 'Mozilla/5.0 (compatible; AlertaNino-AQP/2.0; proyecto academico Universidad Continental)',

    'senamhi' => [
        // Servicio WFS/WMS de la Infraestructura de Datos Espaciales de SENAMHI (IDESEP).
        'wfs' => 'https://idesep.senamhi.gob.pe/geoserver/ows',
        'visor' => 'https://idesep.senamhi.gob.pe/portalidesep/',
        'capa_lluvia_24h' => 'g_prono_pp_24h:view_aviso24h',
        'capa_avisos' => 'g_aviso:view_aviso',
        'capa_quebradas' => 'g_acti_quebrada:view_av_activ_qdra',
        'pagina_nino' => 'https://www.senamhi.gob.pe/?p=fenomeno-el-nino',
    ],

    'sinpad' => [
        // Servicio ArcGIS de emergencias registradas por INDECI.
        'consulta' => 'https://geosinpad.indeci.gob.pe/indeci/rest/services/Emergencias/EMERGENCIAS_SINPAD/MapServer/0/query',
        'portal' => 'https://portal.indeci.gob.pe/emergencias/',
        'peligros' => ['LLUVIAS INTENSAS', 'INUNDACIÓN', 'INUNDACIÓN POR DESBORDE DE RIO', 'INUNDACION', 'HUAICO',
            'HUAYCO', 'DESLIZAMIENTO', 'ALUVIÓN', 'ALUVION', 'DERRUMBE DE CERRO', 'EROSIÓN'],
    ],

    // Copia pública de la última lectura hecha desde Perú. Se usa solo si el
    // servidor no logra conectarse con SENAMHI (ver README, "Límite conocido").
    'espejo' => env('ALERTA_ESPEJO_URL', 'https://frankoayaqui.github.io/AlertaNino-AQP/data/indicadores.json'),

    // Una lectura oficial con más horas que esto se muestra como "no verificada".
    'horas_vigencia_lectura' => 3,

    // Niveles oficiales del aviso de SENAMHI. Colores según la leyenda de la capa oficial.
    'niveles' => [
        0 => ['nombre' => 'Sin aviso', 'clase' => 'n0'],
        1 => ['nombre' => 'Nivel 1', 'clase' => 'n1'],
        2 => ['nombre' => 'Nivel 2', 'clase' => 'n2'],
        3 => ['nombre' => 'Nivel 3', 'clase' => 'n3'],
        4 => ['nombre' => 'Nivel 4', 'clase' => 'n4'],
    ],

    // Orientación general según el nivel oficial (no reemplaza las indicaciones de las autoridades).
    'acciones' => [
        0 => ['SENAMHI no incluye tu distrito en el aviso por lluvias de hoy. Esto no significa que no haya riesgo.',
            'Revisa techos, canaletas y tu mochila de emergencia.'],
        1 => ['Mantente informado por los canales oficiales de SENAMHI y Defensa Civil.',
            'No dejes objetos ni desmonte en torrenteras y cauces secos.',
            'Ten lista tu mochila de emergencia y el celular cargado.'],
        2 => ['Evita cruzar torrenteras y cauces secos, aunque parezcan sin agua.',
            'Planifica tus traslados y aléjate de laderas inestables.',
            'Sigue la radio local y las indicaciones de Defensa Civil.'],
        3 => ['Aléjate de torrenteras, quebradas y de la orilla del río Chili.',
            'Ten a mano tu mochila de emergencia y acuerda un punto de encuentro con tu familia.',
            'Atiende las instrucciones de las autoridades. Emergencias: 115.'],
        4 => ['Peligro alto: no te acerques a torrenteras, quebradas ni al río Chili.',
            'Si las autoridades lo indican, dirígete a una zona segura de inmediato.',
            'Emergencias: 115 Defensa Civil · 116 Bomberos · 105 Policía.'],
    ],

    'fuentes' => ['SENAMHI', 'ENFEN', 'INDECI', 'Gobierno Regional de Arequipa', 'Municipalidad'],

    'tipos_aviso' => [
        'lluvias' => 'Lluvias intensas',
        'quebradas' => 'Activación de quebradas / huaicos',
        'inundacion' => 'Inundación',
        'deslizamiento' => 'Deslizamiento',
        'el_nino' => 'Fenómeno El Niño',
        'otro' => 'Otro',
    ],

    'categorias_guia' => [
        'antes' => 'Antes de las lluvias',
        'durante' => 'Durante una lluvia intensa',
        'despues' => 'Después',
        'mochila' => 'Mochila de emergencia',
    ],

    'tipos_contacto' => [
        'emergencia' => 'Emergencias',
        'salud' => 'Salud',
        'municipal' => 'Municipalidad',
        'otro' => 'Otro',
    ],
];
