# AlertaNiño AQP

Repositorio: https://github.com/FrankoAyaqui/AlertaNino-AQP (privado).

## Seguimiento real — 8 de noviembre de 2026

La portada `/` consulta Open-Meteo para clima modelado y pronóstico de Arequipa, Socabaya, Miraflores y Alto Selva Alegre. Consulta la capa oficial de activación de quebradas de SENAMHI para Arequipa y representa en Leaflet los registros de la fecha del día. Muestra la fecha real de la fuente, las exclusiones y los fallos; no rellena datos con ejercicios.

Las consultas se realizan al abrir la página y cada cinco minutos mientras permanece visible, con caché de servidor de cinco minutos. No es vigilancia continua en segundo plano. En fallo se etiqueta la última respuesta como desactualizada; los avisos desactualizados no se dibujan como vigentes. Una consulta sin registros de hoy no demuestra ausencia de peligro.

ENFEN: resumen editorial del comunicado 19-2026 del 30/10/2026, revisado el 08/11/2026, con enlace al original. No se actualiza automáticamente; desde el 13/11/2026 se muestra advertencia de revisión pendiente. Ningún evento local se atribuye automáticamente a El Niño. El pronóstico no confirma daños.

La ruta `/practica` conserva los ejercicios y cuatro CRUD académicos. Están separados de los datos de fuentes externas. La bandeja corresponde a publicaciones manuales; no envía alertas automáticas por clima, correo, SMS o push. El proyecto todavía no se declara un servicio público completo ni desplegado.

## Requisitos e instalación

PHP 8.4+, Composer 2 y extensiones curl, dom, fileinfo, mbstring, openssl, pdo_sqlite, sqlite3, tokenizer, xml y zip. No utiliza Python ni venv. Se requiere Internet para las fuentes y el mapa. No requiere Node/npm para esta instalación:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed
php artisan alerta:usuario admin@example.test administrador
php artisan serve --host=127.0.0.1 --port=8000
```

No sobrescribir una configuración existente. El comando de usuario pide nombre y contraseña; no existen credenciales predeterminadas. Para este equipo, usar INICIAR_LOCAL.cmd de la carpeta superior y http://127.0.0.1:8765.

## Fuentes y atribución

- Open-Meteo: https://open-meteo.com/ — datos bajo CC BY 4.0, uso de API gratuito no comercial sujeto a límites: https://open-meteo.com/en/terms . Datos actuales modelados, no una estación local.
- SENAMHI IDESEP: https://idesep.senamhi.gob.pe/portalidesep/wms.do — capa view_av_activ_qdra; filtros de departamento y fecha, límite de 200 registros y aviso de respuesta parcial. El nivel y texto se conservan sin recalificar. Revisión de condiciones de reutilización para cualquier despliegue público.
- ENFEN: https://enfen.imarpe.gob.pe/download/comunicado-oficial-enfen-n-19-2026/ . Perspectivas por región, distintas del pronóstico puntual.
- Leaflet 1.9.4 (BSD-2-Clause): https://leafletjs.com/ ; carga mediante CDN. Mapa base © OpenStreetMap contributors: https://www.openstreetmap.org/copyright ; política: https://operations.osmfoundation.org/policies/tiles/ . No descarga masiva.

Si CDN o mapa base fallan, queda la lista textual. Las fechas y disponibilidad dependen de los proveedores. INDECI está integrado en la sección de afectaciones: el catálogo cartográfico se etiqueta por fecha y se complementa con el listado de reportes; no se presenta como detección instantánea. IGP no está integrado en esta versión.

## Pruebas y seguridad

```powershell
php artisan test --compact
```

Revisión: 20 pruebas y 132 comprobaciones, además de consultas reales desde PHP y navegador Edge a 1365 y 390 px sin errores JavaScript. Autorización, hash, cuatro CRUD, CSRF, XSS, filtros, caché ante fallos, rechazo de clima antiguo y exclusión de avisos sin fecha o de otras regiones. Las URLs externas son fijas y el cliente no puede indicar servidores arbitrarios. El texto externo se inserta mediante textContent.

Consulta `/api/seguimiento?source=weather&place=arequipa` o `source=warnings`. Límite 30 peticiones/minuto. La configuración, claves, bases, vendor y node_modules no se incluyen en Git ni ZIP. `.env.example` es solo una plantilla sin secretos. Composer instala las dependencias al ejecutar.

## Colaboración y alcance académico

Ver docs/ENTREGA_GIT.md. La base e integración se prepararon con asistencia de Codex; no constituyen aportes individuales de los estudiantes. Deben completar entrevistas, consentimientos, evaluación, acuerdos y commits propios. Las licencias del backend se conservan en docs/LICENCIAS_DEPENDENCIAS.json y docs/LARAVEL_LICENSE.md; el código específico mantiene pendiente la decisión de licencia del equipo.

El PDF corregido incorpora una actualización técnica inicial que prevalece sobre el alcance anterior. Conserva las guías académicas pendientes. No se generó un video nuevo.

## Zonas con afectaciones reportadas (08/11/2026)

Sección `/#zonas-afectadas`. Dos consultas independientes, con caché de 5 minutos:
- `source=impacts`: SINPAD / INDECI, Arequipa, lluvias intensas, inundación, huaico, deslizamiento, aluvión y sequía. Últimos 300 eventos del servicio, ordenados por fecha e identificador. Solo muestra registros con al menos una cifra positiva de personas o viviendas afectadas/dañadas. Conserva valores desconocidos como «Sin dato». Descarta duplicados, fechas futuras y otras regiones; las coordenadas inválidas no se dibujan, pero el registro queda en la lista.
- `source=reports`: primera página del listado oficial filtrado por Arequipa y lluvias intensas. Reconoce reportes preliminares, complementarios e informes de emergencia; excluye peligro inminente. Extrae fecha del título, sin usar la fecha del enlace antiguo como fecha de actualización. No descarga ni interpreta automáticamente las tablas de daños de los PDF.

El usuario filtra el mapa por últimos 30 días (predeterminado), 12 meses o historial, y por distrito. Los puntos son ubicaciones reportadas, no polígonos de daños. No se deduce causalidad de El Niño ni continuidad del evento. La falta de registros recientes no significa ausencia de afectaciones. Ante fallo se indica el estado y no se dibuja la respuesta antigua como actual. Lista accesible aunque falle Leaflet.

Prueba real: 162 eventos con daños entre los 300 registros consultados; última fecha del catálogo 22/01/2024. Tres reportes en el listado consultado: Ocoña (27/09/2026), Caravelí y Mariscal Cáceres (23/09/2026). Estos resultados describen esa consulta, no todo Arequipa ni daños activos hoy. El proveedor limita la actualidad y cobertura. Las fuentes se vuelven a consultar automáticamente: no son fixtures incluidos en el código.

Verificación: 20 pruebas / 132 comprobaciones; Edge, filtro histórico/distrito, puntos y reportes reales; móvil 390 px sin desbordamiento y sin errores JavaScript. Solo se amplió la sección de afectaciones. Sin notificaciones, sin cambios en el PDF ni en ENFEN durante esta ampliación.

Fuentes:
- https://geosinpad.indeci.gob.pe/indeci/rest/services/Emergencias/EMERGENCIAS_SINPAD/MapServer/0
- https://portal.indeci.gob.pe/emergencias/?title=AREQUIPA&tipo_alerta=lluvias-intensas

## Mapa interactivo ampliado el 9 de noviembre de 2026

Se añadieron filtros de hoy y 7 días, filtro por fenómeno, botón de actualización, cuenta regresiva, pausa de actualización automática, ajuste a todos los puntos, pantalla completa y selección desde las tarjetas mediante «Ver en mapa». La actualización conserva el encuadre. Los marcadores diferencian fechas recientes e históricas por color, sin representar gravedad. Los puntos y fichas enlazan la fuente y no atribuyen automáticamente causalidad a El Niño.

La consulta se repite cada 5 minutos con la página visible y conexión. Al regresar a la página, consulta si venció el intervalo; al recuperar Internet, vuelve a consultar si la actualización automática está activada. «Actualizar consulta» respeta la caché de 5 minutos del servidor. No equivale a detección instantánea ni modifica la fecha oficial. Las fuentes cartográficas alternativas de INDECI comprobadas tampoco aportaron registros más recientes de Arequipa.

Validación del 09/11: prueba aislada en Edge con datos de ensayo identificados: puntos, historial, filtro de fenómeno, ficha, conservación de encuadre, actualización, pausa, limpieza ante fallo, recuperación, filtro de hoy y móvil; sin errores JavaScript. Script: `tests/browser/affected-areas.cjs`. Para reproducirlo se necesita Node y Playwright disponibles en el entorno de pruebas (o variable PLAYWRIGHT_MODULE con su ubicación); ejecutar `node tests/browser/affected-areas.cjs`. Esto no añade Node como requisito de ejecución de la aplicación. La prueba carga Leaflet desde su CDN y no sustituye una prueba contra Laravel.

Windows bloqueó php.exe mediante Control de aplicaciones y no había servidor abierto. Por ello no se repitió la suite PHP ni la prueba integrada el 09/11. Las 20 pruebas/132 comprobaciones registradas arriba corresponden al 08/11. El backend no cambió en esta ampliación. Antes de presentar, ejecutar la prueba integrada en un equipo donde PHP esté autorizado. No se añadieron notificaciones.
