# AlertaNiño AQP

Web de **monitoreo en tiempo real con datos oficiales** de lluvias, Fenómeno El Niño y emergencias para los distritos de Arequipa.
Proyecto de impacto social del curso de Programación Web, Universidad Continental.

> Reúne datos oficiales de SENAMHI, ENFEN e INDECI. Ante una emergencia sigue siempre las indicaciones de las autoridades.
> Emergencias: 115 Defensa Civil · 116 Bomberos · 105 Policía · 106 SAMU.

## Fuentes oficiales

| Fuente | Dato | Cómo se lee |
|---|---|---|
| SENAMHI (IDESEP, WFS `g_prono_pp_24h:view_aviso24h`) | Aviso por lluvias de 24 h, Nivel 1 a 4, por zona | Cada 30 min en GitHub Actions; se cruza cada zona con el punto de cada distrito |
| SENAMHI (WFS `g_aviso:view_aviso`) | Avisos meteorológicos nacionales vigentes | Cada 30 min |
| SENAMHI (WFS `g_acti_quebrada:view_av_activ_qdra`) | Activación de quebradas en Arequipa | Cada 30 min |
| ENFEN (Comunicado Oficial, PDF enlazado por SENAMHI) | Estado del sistema de alerta, titular y pronóstico de lluvias | Cada 30 min |
| INDECI (GeoSINPAD) | Emergencias registradas en la provincia de Arequipa | En vivo desde el navegador |

No hay datos simulados: todo lo que muestra la web proviene de estas fuentes o de los datos de referencia de la tabla siguiente.

> **Límite conocido:** los servidores de SENAMHI no responden a GitHub Actions (que corre en EE. UU.). El mapa muestra la capa
> oficial de SENAMHI en vivo desde el navegador de cada visitante, pero el nivel por distrito, los avisos y el estado de ENFEN se
> actualizan cuando se ejecuta la lectura desde Perú:
> `powershell -ExecutionPolicy Bypass -File scripts\publicar_datos_oficiales.ps1`.
> La web muestra la hora de la última lectura y avisa «no verificado» cuando tiene más de 2 horas.

## Qué hace

| Función | Cómo funciona |
|---|---|
| Situación en mi distrito | Nivel oficial del aviso por lluvias de SENAMHI, con su descripción y recomendación textuales, más el estado de ENFEN y el pronóstico de referencia. |
| Referencia numérica | Pronóstico de lluvia de 18 distritos (Open-Meteo) comparado con percentiles del mismo modelo; se rotula como referencia, no como aviso. |
| Río Chili | Caudal diario modelado por GloFAS (60 días atrás + 30 de pronóstico) con umbrales P90 y P99 de 1991–2020. |
| El Niño hoy | Anomalía semanal del mar en Niño 1+2 (frente a la costa norte del Perú), ICEN aproximado con las categorías de ENFEN y ONI de NOAA. |
| Mapa | Leaflet + OpenStreetMap con el nivel de cada distrito, y una tabla con la misma información. |
| Tiempo real | Consulta al abrir y cada 15 min. Si pasan 30 min sin datos nuevos, muestra «Actualización no verificada». Los índices oceánicos se regeneran cada 3 h con GitHub Actions. |
| Alertas | Notificación del navegador (con permiso) y sonido opcional (3 tonos, con botón de prueba) cuando SENAMHI eleva el aviso del distrito elegido o ENFEN cambia su estado. |
| Avisos oficiales | `web/data/avisos.json`: avisos copiados de fuentes oficiales, validados antes de publicar y ocultados al vencer. |
| Accesibilidad | Texto ampliable (A−/A+), modo claro/oscuro, enlace para saltar al contenido, tablas como alternativa a mapa y gráficos, sin desbordamiento en móvil. |
| Sin conexión | Service worker y caché local: si se cae la red, muestra los últimos datos con su hora. |

## Estructura

```
web/                    ← sitio estático publicado en GitHub Pages
  index.html
  css/estilos.css
  js/app.js             ← carga de datos, niveles, mapa, alertas
  js/graficos.js        ← gráficos SVG sin dependencias
  js/datos.js           ← distritos, niveles y textos
  data/indicadores.json ← generado por scripts/actualizar_datos.py
  data/avisos.json      ← avisos oficiales cargados a mano
  sw.js, manifest.webmanifest
scripts/
  actualizar_datos.py   ← descarga NOAA y calcula umbrales (solo biblioteca estándar)
  validar_avisos.py     ← valida avisos.json antes de publicar
.github/workflows/pages.yml
```

## Ejecutar en local

```powershell
python scripts/actualizar_datos.py
python -m http.server 8000 --directory web
```

Abrir http://localhost:8000. El script necesita `pip install pypdf` para leer el comunicado de ENFEN; sin él, el resto de fuentes se actualiza igual.

## Publicar en GitHub Pages

1. Sube el repositorio a GitHub (rama `main`).
2. En **Settings → Pages → Build and deployment**, elige **Source: GitHub Actions**.
3. El flujo `Actualizar datos y publicar` se ejecuta en cada push y cada 3 horas. También se puede lanzar a mano desde **Actions**.

GitHub desactiva los flujos programados tras 60 días sin actividad en el repositorio: si pasa, reactívalo desde **Actions**.

## Cargar un aviso oficial

Edita `web/data/avisos.json` copiando el aviso real (no redactar avisos propios):

```json
[
  {
    "id": "senamhi-2026-123",
    "titulo": "Precipitaciones en la sierra sur",
    "emisor": "SENAMHI",
    "nivel_original": "Nivel naranja",
    "distritos": ["Socabaya", "Miraflores"],
    "mensaje": "Texto del aviso tal como lo publica la fuente oficial.",
    "fuente_url": "https://www.gob.pe/...",
    "emitido": "2026-11-21T10:00:00-05:00",
    "vence": "2026-11-23T23:59:00-05:00"
  }
]
```

`scripts/validar_avisos.py` exige URL https, fechas con zona horaria, vencimiento posterior a la emisión y distritos existentes.
Cada cambio queda registrado en el historial de Git (autor, fecha y revisión).

## Metodología y límites

- **Lluvia:** percentiles 90/95/99 de los días con lluvia (≥ 1 mm) del mismo modelo de pronóstico, desde 2022, en Arequipa Cercado. Se revisa también la hora más intensa (P95/P99 horario). Es un criterio climatológico propio, no la escala de SENAMHI.
- **Río:** caudal *modelado*, no medido por una estación. Solo cuenta para los distritos que limitan con el río.
- **El Niño:** se muestra aparte y no cambia los niveles locales. El ICEN aproximado usa 13 semanas de OISST; el ICEN oficial lo publica ENFEN.
- Las ubicaciones de los distritos son puntos de referencia, no límites oficiales. No se dibujan zonas de riesgo, rutas ni refugios inventados.
- Las notificaciones funcionan mientras la página esté abierta (también en segundo plano), no con el navegador cerrado.
- Que no haya alerta **no significa que la zona sea segura**.

## Fuentes de datos

- [Open-Meteo](https://open-meteo.com/) — pronóstico, histórico de pronósticos y Flood API (CC BY 4.0).
- [GloFAS / Copernicus](https://global-flood.emergency.copernicus.eu/) — caudales modelados.
- [NOAA CPC](https://www.cpc.ncep.noaa.gov/) — ONI y temperatura semanal del mar (dominio público).
- [OpenStreetMap](https://www.openstreetmap.org/copyright) — mapa base (ODbL).
- Referencias oficiales: [SENAMHI](https://www.gob.pe/9323-consultar-los-avisos-meteorologicos-mas-recientes-en-peru), [ENFEN](https://enfen.imarpe.gob.pe/), [INDECI](https://portal.indeci.gob.pe/), [SIGRID](https://sigrid.cenepred.gob.pe/sigridv3/).

Bibliotecas: [Leaflet](https://leafletjs.com/) 1.9.4 (BSD-2-Clause).
