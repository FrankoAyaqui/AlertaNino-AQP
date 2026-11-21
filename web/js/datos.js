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

// Niveles propios de la app. Son etiquetas internas: no equivalen a los
// niveles amarillo/naranja/rojo de SENAMHI.
export const NIVELES = [
  { n: 0, nombre: "Sin señal relevante", corto: "Normal", icono: "✓" },
  { n: 1, nombre: "Atención", corto: "Atención", icono: "!" },
  { n: 2, nombre: "Alto", corto: "Alto", icono: "!!" },
  { n: 3, nombre: "Muy alto", corto: "Muy alto", icono: "!!!" },
];

export const ACCIONES = {
  0: [
    "No se prevé lluvia fuera de lo habitual en tu distrito.",
    "Es buen momento para revisar techos, canaletas y tu mochila de emergencia.",
    "Consulta de vez en cuando: el pronóstico se actualiza cada hora.",
  ],
  1: [
    "Se prevé lluvia por encima de lo habitual (más que 9 de cada 10 días lluviosos).",
    "Evita dejar objetos o desmonte en torrenteras y cauces secos.",
    "Revisa los avisos de SENAMHI para Arequipa.",
  ],
  2: [
    "Lluvia fuerte prevista. Planifica tus traslados y evita cruzar torrenteras.",
    "Ten a mano tu mochila de emergencia y cargado el celular.",
    "Sigue la radio local y los canales oficiales de Defensa Civil.",
  ],
  3: [
    "Lluvia muy fuerte prevista, entre las mayores del registro reciente.",
    "Aléjate de torrenteras, quebradas y de la orilla del río Chili.",
    "Si las autoridades lo indican, dirígete a una zona segura. Emergencias: 115.",
  ],
};

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
