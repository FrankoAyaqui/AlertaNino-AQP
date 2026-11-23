# AlertaNiño AQP

Aplicación web en **Laravel (MVC)** que reúne en tiempo real los **avisos oficiales** de SENAMHI, ENFEN e INDECI sobre lluvias,
Fenómeno El Niño y emergencias para los distritos de Arequipa, y los notifica a los ciudadanos de cada distrito.

Proyecto de impacto social · Programación Web · Universidad Continental · Arequipa.
Equipo: Franko Joel Ayaqui Arredondo, Kevin Jolver Montañez Taype, Alvaro Sebastian Meza Salas. Docente: Victor Alfonzo Cornejo Arismendi.

> Herramienta académica de consulta. Ante una emergencia sigue las indicaciones de las autoridades.
> Emergencias: 115 Defensa Civil · 116 Bomberos · 105 Policía · 106 SAMU.

## Funcionalidades

| Módulo | Qué hace | Quién |
|---|---|---|
| Situación en mi distrito | Nivel oficial del aviso por lluvias de SENAMHI (1 a 4) con su texto y recomendación, avisos vigentes, estado de ENFEN, pronóstico de referencia y qué hacer | Público |
| Mapa | Capa oficial de SENAMHI en vivo (WMS), nivel por distrito y emergencias de INDECI; tabla equivalente | Público |
| Avisos | Lista con filtros (distrito, fuente, texto, vigentes/vencidos) y detalle con enlace al documento oficial | Público |
| Guías y contactos | Preparación antes, durante y después; teléfonos de emergencia | Público |
| Estado de las fuentes | Hora y resultado de la última lectura de cada fuente | Público |
| Registro e ingreso | Cuentas de ciudadano con contraseña con hash bcrypt | Público |
| Mi cuenta y bandeja | Elegir distrito, aceptar alertas y leer los avisos recibidos | Ciudadano |
| Alertas en el dispositivo | Notificación del navegador y sonido opcional cuando SENAMHI eleva el nivel o ENFEN cambia de estado | Todos |
| Panel: avisos, guías, contactos | CRUD con validación en el servidor; ejecutar la lectura oficial | Operador |
| Panel: distritos, usuarios y roles | CRUD con validación en el servidor | Administrador |
| API `GET /api/estado` | JSON de solo lectura con el estado oficial por distrito | Público |

## Fuentes oficiales y tiempo real

| Fuente | Dato | Cómo se obtiene |
|---|---|---|
| SENAMHI (IDESEP) `g_prono_pp_24h:view_aviso24h` | Aviso por lluvias de 24 h, Nivel 1 a 4 | WFS; se calcula qué zona contiene el punto de cada distrito |
| SENAMHI `g_aviso:view_aviso` | Avisos meteorológicos vigentes | WFS, filtro por vigencia |
| SENAMHI `g_acti_quebrada:view_av_activ_qdra` | Activación de quebradas en Arequipa | WFS, filtro por departamento |
| ENFEN | Estado del sistema de alerta (último comunicado oficial) | PDF enlazado por SENAMHI, leído con `smalot/pdfparser` |
| INDECI GeoSINPAD | Emergencias registradas en la provincia | Servicio ArcGIS REST |
| Open-Meteo | Pronóstico de referencia (no oficial) | Desde el navegador |

`php artisan alerta:sincronizar` lee todo, guarda una copia local, crea **avisos automáticos** desde el Nivel 2 con el texto oficial y
**notifica** a los ciudadanos del distrito. El programador de Laravel lo ejecuta **cada 30 minutos**; un operador también puede
ejecutarlo desde el panel. Cada lectura queda registrada en la tabla `sincronizaciones`. No hay datos simulados.

**Límite conocido.** SENAMHI no responde a servidores fuera del Perú. Si el servidor no puede leer SENAMHI, usa la copia publicada
desde Perú por `scripts/publicar_datos_oficiales.ps1` (sitio `web/` en GitHub Pages) y marca la lectura como «espejo».
Toda lectura muestra su hora y la portada avisa «no verificada» si tiene más de 3 horas.

## Arquitectura (MVC)

```
app/
  Models/                 Modelo: User, Distrito, Aviso, Guia, Contacto, LecturaSenamhi, ComunicadoEnfen, Emergencia, Sincronizacion
  Http/Controllers/       Controlador: público, Auth/, Panel/ (CRUD), Api/EstadoController
  Http/Requests/          Validación en el servidor (Form Requests)
  Http/Middleware/        CabecerasSeguridad (CSP, X-Frame-Options...)
  Services/Oficial/       Clientes de SENAMHI, ENFEN, INDECI y espejo
  Services/               SincronizadorOficial, PublicadorAvisos
  Notifications/          Avisos a la bandeja (canal database)
  Console/Commands/       alerta:sincronizar, alerta:crear-usuario
  Support/Geometria.php   Punto en polígono (GeoJSON)
resources/views/          Vista: Blade (layouts, parciales, públicas, panel)
public/css, public/js     Frontend sin compilación: CSS propio, JS en módulos, Leaflet desde cdnjs con SRI
routes/web.php, api.php   Rutas con middleware auth y can:
database/migrations       Esquema con claves foráneas e índices
tests/                    Pruebas de funcionalidad, seguridad y sincronización
web/, scripts/            Espejo estático (GitHub Pages) y lectura oficial desde Perú
```

**Roles:** `ciudadano` (consulta, distrito, bandeja), `operador` (además avisos, guías, contactos y lectura oficial),
`administrador` (además distritos y usuarios). Se autorizan con Gates (`gestionar-contenido`, `administrar`) en rutas y vistas.

## Seguridad

- Contraseñas con hash **bcrypt** (cast `hashed`); mínimo 12 caracteres con mayúscula, minúscula y número.
- El registro público siempre crea `ciudadano`: `rol` no es asignable en masa.
- **CSRF** en todos los formularios (`@csrf`); **XSS**: Blade escapa la salida; datos externos insertados con `textContent`.
- **Inyección SQL**: Eloquent con parámetros enlazados; los `LIKE` escapan `%` y `_`.
- Sesión regenerada al ingresar e invalidada al salir; **5 intentos por minuto** en ingreso y registro.
- Cabeceras **CSP**, `X-Frame-Options: DENY`, `nosniff`, `Referrer-Policy`, `Permissions-Policy`; Leaflet con **SRI**.
- Enlaces de fuentes solo `https://`; una notificación ajena devuelve 404; los avisos automáticos no se editan a mano.
- Sin credenciales por defecto: el personal se crea con `alerta:crear-usuario` (contraseña oculta). `.env` fuera del repositorio.

## Instalación local (Windows / PowerShell)

Requisitos: PHP 8.3+ (probado con 8.4.28) con `curl, fileinfo, mbstring, openssl, pdo_sqlite, sqlite3, zip`, y Composer 2.
En Windows, PHP necesita certificados raíz: descarga `cacert.pem` de https://curl.se/ca/ y configura `curl.cainfo` y `openssl.cafile` en `php.ini`.

```powershell
composer install
copy .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed
php artisan alerta:crear-usuario admin@ejemplo.pe administrador
php artisan alerta:sincronizar
php artisan serve
```

Abrir http://127.0.0.1:8000. Para la lectura automática cada 30 minutos, en otra terminal: `php artisan schedule:work`
(en un servidor: una tarea que ejecute `php artisan schedule:run` cada minuto).

## Pruebas

```powershell
php artisan test
```

36 pruebas, 196 comprobaciones: registro y hash, ingreso, límite de intentos, roles (403), CRUD de las cinco entidades con
validación, integridad (distrito con avisos), XSS, inyección SQL, cabeceras, notificaciones ajenas, API, lectura oficial con
respuestas simuladas (directa, repetida, espejo, Nivel 1) e intérprete del comunicado ENFEN N.º 20-2026.

## Licencias

- Código propio: uso académico; licencia de publicación por decidir entre los tres integrantes.
- Laravel (MIT, `docs/LARAVEL_LICENSE.md`); 78 dependencias de producción inventariadas en `docs/LICENCIAS_DEPENDENCIAS.json`:
  69 MIT, 5 BSD-3-Clause, 1 Apache-2.0, `nette/*` (BSD-3-Clause, opción elegida entre BSD/GPL) y `smalot/pdfparser` (LGPL-3.0, usado sin modificar).
- Leaflet 1.9.4 (BSD-2-Clause) · OpenStreetMap (ODbL, atribución visible) · Open-Meteo (CC BY 4.0).
- Datos: SENAMHI, ENFEN e INDECI (información pública del Estado peruano, citada con su fuente).

## Uso de inteligencia artificial

La base de código y la documentación se prepararon con asistencia de IA (Codex en la versión 1; Claude, de Anthropic, en la versión 2).
Las entrevistas, consentimientos, acuerdos y la participación del equipo no fueron generados por IA. Cada integrante debe
comprender y poder explicar el código que presenta.
