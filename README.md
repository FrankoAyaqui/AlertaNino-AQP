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

Si CDN o mapa base fallan, queda la lista textual. Las fechas y disponibilidad dependen de los proveedores. No se ha integrado INDECI como fuente actual: el servicio probado devolvió datos antiguos. IGP no está integrado en esta versión.

## Pruebas y seguridad

```powershell
php artisan test --compact
```

Revisión: 16 pruebas y 117 comprobaciones, además de consultas reales desde PHP y navegador Edge a 1365 y 390 px sin errores JavaScript. Autorización, hash, cuatro CRUD, CSRF, XSS, filtros, caché ante fallos, rechazo de clima antiguo y exclusión de avisos sin fecha o de otras regiones. Las URLs externas son fijas y el cliente no puede indicar servidores arbitrarios. El texto externo se inserta mediante textContent.

Consulta `/api/seguimiento?source=weather&place=arequipa` o `source=warnings`. Límite 30 peticiones/minuto. La configuración, claves, bases, vendor y node_modules no se incluyen en Git ni ZIP. `.env.example` es solo una plantilla sin secretos. Composer instala las dependencias al ejecutar.

## Colaboración y alcance académico

Ver docs/ENTREGA_GIT.md. La base e integración se prepararon con asistencia de Codex; no constituyen aportes individuales de los estudiantes. Deben completar entrevistas, consentimientos, evaluación, acuerdos y commits propios. Las licencias del backend se conservan en docs/LICENCIAS_DEPENDENCIAS.json y docs/LARAVEL_LICENSE.md; el código específico mantiene pendiente la decisión de licencia del equipo.

El PDF corregido incorpora una actualización técnica inicial que prevalece sobre el alcance anterior. Conserva las guías académicas pendientes. No se generó un video nuevo.
