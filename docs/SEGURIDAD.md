# Seguridad de AlertaNiño AQP

Resumen de las defensas implementadas y su relación con el **OWASP Top 10 (2021)**.
La filosofía es **defensa en profundidad**: varias capas, de modo que si una falla, otra contiene el ataque.

> La aplicación **no** ataca ni rastrea de vuelta a nadie. Ante un ataque: detecta, registra con IP y navegador,
> bloquea al origen y conserva evidencia para reportarlo a la autoridad (DIVINDAT - PNP). «Hackear de vuelta» es ilegal.

## Mapa OWASP Top 10 (2021)

| # | Riesgo | Defensa en AlertaNiño AQP |
|---|---|---|
| A01 | Pérdida de control de acceso | Tres roles con *Gates* (`gestionar-contenido`, `administrar`) en rutas y vistas; `rol` fuera de la asignación masiva; rutas de administración en grupo con `can:administrar`; una notificación ajena devuelve 404; un aviso automático no se edita (403). Pruebas: `AutorizacionTest`, `CrudTest`. |
| A02 | Fallos criptográficos | Contraseñas con **bcrypt** (cast `hashed`); sesión cifrada (`SESSION_ENCRYPT=true`); **HTTPS forzado** y **HSTS** (1 año); cookies `HttpOnly`, `SameSite=Lax` y `Secure` en producción; `APP_KEY` fuera de Git. |
| A03 | Inyección | **Eloquent con parámetros enlazados** (nunca SQL concatenado); `LIKE` con `%` y `_` escapados; **Blade escapa** toda salida y el JS inserta datos con `textContent`; filtros con listas cerradas; validación de tipos. Pruebas: `SeguridadTest` (XSS, SQLi). |
| A04 | Diseño inseguro | Validación en el servidor con *Form Requests* por entidad; enlaces solo `https`; avisos con vigencia (caducan); el texto oficial se copia sin ejecutarlo; separación modelo/servicio/controlador. |
| A05 | Configuración incorrecta | `APP_DEBUG=false` en producción; cabeceras `CSP`, `X-Frame-Options: DENY`, `nosniff`, `Referrer-Policy`, `Permissions-Policy`, `COOP`; se ocultan `Server` y `X-Powered-By`; `.env`, base de datos y credenciales fuera de Git; solo `public/` se expone en la web. |
| A06 | Componentes vulnerables | `composer audit` sin vulnerabilidades; versiones fijas en `composer.lock`; Leaflet con **SRI**; dependencias de producción inventariadas en `docs/LICENCIAS_DEPENDENCIAS.json`. |
| A07 | Fallos de identificación y autenticación | Política de contraseña de 12+ con mayúscula, minúscula y número; **límite de 5 intentos/min** por correo + IP; regeneración de sesión al entrar e invalidación al salir; sin credenciales por defecto (`alerta:crear-usuario` con contraseña oculta). |
| A08 | Fallos de integridad de datos y software | CSRF (`@csrf`) en todos los formularios; CORS restringido a la API de solo lectura, sin credenciales; SRI en librerías externas; el comunicado de ENFEN se rechaza si su formato no es el oficial. |
| A09 | Registro y monitoreo insuficientes | Middleware **`VigilanciaSeguridad`**: registra cada solicitud de ataque (IP, navegador, patrón) en `registros_seguridad`, con panel para el administrador (`/panel/seguridad`); se registran los inicios de sesión fallidos y cada lectura de fuentes (`sincronizaciones`). |
| A10 | Falsificación de solicitudes del servidor (SSRF) | El servidor solo consulta URLs oficiales fijas de `config/alerta.php`; no acepta URLs del usuario para consultarlas; los enlaces que registra el operador se guardan pero no los descarga el servidor. |

## Vigilancia y límite de peticiones (`VigilanciaSeguridad`)

- **Patrones bloqueados** en la URL: *path traversal*, `<script>`/`javascript:`, SQLi (`UNION SELECT`, `OR 1=1`, `information_schema`), rutas de escaneo (`.env`, `.git`, `wp-login`, `phpmyadmin`), `${jndi:`, `<?php`, `%00`. Respuesta: **403** y registro.
- **Límite global por IP**: 240 peticiones/minuto; al superarlo, **429** y bloqueo.
- **Bloqueo progresivo**: 8 eventos sospechosos (incluidos 401/403/419) en 10 minutos ⇒ **bloqueo de 30 minutos** (429).
- **Trazabilidad**: cada evento guarda IP, método, ruta, patrón, severidad y navegador. Detrás de Cloudflare se usa la IP real del visitante (`X-Forwarded-For`, proxies de confianza).
- Límites específicos: **5/min** en ingreso y registro; **60/min** en la API; **3/min** en la lectura oficial manual.

## No aplican a esta aplicación

- **Subida de archivos**: la aplicación no recibe archivos de usuarios, así que no hay riesgo de carga maliciosa.
- **Precios / pagos**: no maneja dinero ni precios; nada que calcular en el cliente.
- **Claves de API de terceros**: las fuentes oficiales son públicas y sin clave, así que no hay secretos que filtrar ni revocar.

## Lista para publicar en producción

1. `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true` en `.env`.
2. `php artisan key:generate` (clave única, nunca compartida).
3. Servidor con **HTTPS** y, de preferencia, **ubicado en Perú** (SENAMHI no responde a servidores del extranjero).
4. `composer install --no-dev --optimize-autoloader` y `php artisan config:cache route:cache view:cache`.
5. Programar `php artisan schedule:run` cada minuto (lectura oficial cada 30 min).
6. Respaldo periódico de la base de datos; revisar `/panel/seguridad` ante actividad inusual.
7. Mantener dependencias al día: `composer update` + `php artisan test` antes de publicar.
