// Datos fijos de la aplicación: distritos, niveles y textos.

// Puntos de referencia aproximados (centro urbano de cada distrito).
// No son límites oficiales ni zonas de riesgo: solo sirven para pedir el pronóstico.
// `rio: true` marca los distritos que limitan con el cauce del río Chili.
export const DISTRITOS = [
  { id: "cercado", nombre: "Arequipa (Cercado)", abrev: "AQ", lat: -16.3989, lon: -71.5369, rio: true },
  { id: "alto-selva-alegre", nombre: "Alto Selva Alegre", abrev: "AS", lat: -16.3780, lon: -71.5180, rio: true, piloto: true },
  { id: "cayma", nombre: "Cayma", abrev: "CY", lat: -16.3560, lon: -71.5490, rio: true },
  { id: "cerro-colorado", nombre: "Cerro Colorado", abrev: "CC", lat: -16.3720, lon: -71.5880 },
  { id: "characato", nombre: "Characato", abrev: "CH", lat: -16.4680, lon: -71.4880 },
  { id: "chiguata", nombre: "Chiguata", abrev: "CG", lat: -16.4020, lon: -71.3930 },
  { id: "jacobo-hunter", nombre: "Jacobo Hunter", abrev: "JH", lat: -16.4440, lon: -71.5560, rio: true },
  { id: "jlbyr", nombre: "José Luis Bustamante y Rivero", abrev: "JL", lat: -16.4230, lon: -71.5220 },
  { id: "mariano-melgar", nombre: "Mariano Melgar", abrev: "MM", lat: -16.4030, lon: -71.5060 },
  { id: "miraflores", nombre: "Miraflores", abrev: "MI", lat: -16.3920, lon: -71.5180, piloto: true },
  { id: "paucarpata", nombre: "Paucarpata", abrev: "PA", lat: -16.4280, lon: -71.4990 },
  { id: "sabandia", nombre: "Sabandía", abrev: "SB", lat: -16.4560, lon: -71.4930 },
  { id: "sachaca", nombre: "Sachaca", abrev: "SA", lat: -16.4260, lon: -71.5680, rio: true },
  { id: "socabaya", nombre: "Socabaya", abrev: "SO", lat: -16.4680, lon: -71.5290, piloto: true },
  { id: "tiabaya", nombre: "Tiabaya", abrev: "TI", lat: -16.4490, lon: -71.5910, rio: true },
  { id: "uchumayo", nombre: "Uchumayo", abrev: "UC", lat: -16.4240, lon: -71.6700, rio: true },
  { id: "yanahuara", nombre: "Yanahuara", abrev: "YA", lat: -16.3880, lon: -71.5430, rio: true },
  { id: "yura", nombre: "Yura", abrev: "YU", lat: -16.2520, lon: -71.6810 },
];

export const RIO_CHILI = { lat: -16.40, lon: -71.54 };

// Niveles OFICIALES del aviso de SENAMHI por lluvias (24 h). El texto se
// muestra tal como lo publica SENAMHI. Los colores copian la leyenda oficial
// de la capa (Nivel 1 sin color, 2 amarillo, 3 naranja, 4 rojo) y siempre
// van acompañados del número.
export const NIVELES_OFICIALES = {
  0: { nombre: "Sin aviso", icono: "–", color: "" },
  1: { nombre: "Nivel 1", icono: "1", color: "" },
  2: { nombre: "Nivel 2", icono: "2", color: "1" },
  3: { nombre: "Nivel 3", icono: "3", color: "2" },
  4: { nombre: "Nivel 4", icono: "4", color: "3" },
};

// Capa oficial de SENAMHI que el navegador dibuja directamente en el mapa.
export const WMS_SENAMHI = "https://idesep.senamhi.gob.pe/geoserver/ows";

// Orientación general por nivel oficial (no reemplaza las indicaciones de las autoridades).
export const ACCIONES_OFICIALES = {
  0: [
    "SENAMHI no incluye tu distrito en el aviso por lluvias de hoy. Esto no significa que no haya riesgo.",
    "Revisa techos, canaletas y tu mochila de emergencia.",
    "Consulta los avisos de SENAMHI e INDECI antes de salir si el cielo cambia.",
  ],
  1: [
    "Mantente informado por los canales oficiales de SENAMHI y Defensa Civil.",
    "No dejes objetos ni desmonte en torrenteras y cauces secos.",
    "Ten lista tu mochila de emergencia y el celular cargado.",
  ],
  2: [
    "Evita cruzar torrenteras y cauces secos, aunque parezcan sin agua.",
    "Planifica tus traslados y aléjate de laderas inestables.",
    "Sigue la radio local y las indicaciones de Defensa Civil.",
  ],
  3: [
    "Aléjate de torrenteras, quebradas y de la orilla del río Chili.",
    "Ten a mano tu mochila de emergencia y acuerda un punto de encuentro con tu familia.",
    "Atiende las instrucciones de las autoridades. Emergencias: 115.",
  ],
  4: [
    "Peligro alto: no te acerques a torrenteras, quebradas ni al río Chili.",
    "Si las autoridades lo indican, dirígete a una zona segura de inmediato.",
    "Emergencias: 115 Defensa Civil · 116 Bomberos · 105 Policía.",
  ],
};

// Niveles de REFERENCIA calculados con el pronóstico numérico (Open-Meteo).
// No son avisos oficiales: complementan el aviso de SENAMHI.
export const NIVELES = [
  { n: 0, nombre: "Sin señal relevante", corto: "Normal", icono: "✓" },
  { n: 1, nombre: "Atención", corto: "Atención", icono: "!" },
  { n: 2, nombre: "Alto", corto: "Alto", icono: "!!" },
  { n: 3, nombre: "Muy alto", corto: "Muy alto", icono: "!!!" },
];

const WMO = {
  0: "Despejado", 1: "Mayormente despejado", 2: "Parcialmente nublado", 3: "Nublado",
  45: "Niebla", 48: "Niebla", 51: "Llovizna débil", 53: "Llovizna", 55: "Llovizna intensa",
  56: "Llovizna helada", 57: "Llovizna helada", 61: "Lluvia débil", 63: "Lluvia moderada",
  65: "Lluvia fuerte", 66: "Lluvia helada", 67: "Lluvia helada", 71: "Nevada débil",
  73: "Nevada", 75: "Nevada fuerte", 77: "Granizo fino", 80: "Chubascos débiles",
  81: "Chubascos", 82: "Chubascos fuertes", 85: "Chubascos de nieve", 86: "Chubascos de nieve",
  95: "Tormenta", 96: "Tormenta con granizo", 99: "Tormenta con granizo",
};
export const cielo = (codigo) => WMO[codigo] ?? "—";
