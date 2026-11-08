# AlertaNiño AQP

Repositorio: https://github.com/FrankoAyaqui/AlertaNino-AQP (privado; el docente y los integrantes necesitan acceso).

Prototipo académico local para Programación Web, Universidad Continental. Creado con asistencia de Codex el 7 de noviembre de 2026. El equipo debe revisar y comprender el código, completar entrevistas y documentar aportes propios. No constituye un sistema oficial de alerta temprana.

## Funciones

- Consulta pública con filtro por distrito y búsqueda por título.
- Registro ciudadano, login, logout y contraseñas con hash.
- Roles ciudadano, operador y administrador. Solo el administrador gestiona zonas.
- CRUD completo de alertas, zonas, guías y contactos con validación en servidor.
- Bandeja privada: al publicar, cada suscriptor de ese distrito recibe un aviso en la base de datos.
- Borradores ocultos, avisos vencidos excluidos de inicio, detalle con advertencia de vencimiento.
- Diseño adaptable, aumento de texto y prueba voluntaria de tono.

## Límites

Todos los avisos son DEMOSTRACIÓN. No hay integración automática con fuentes, predicción, mapa de evacuación, SMS, correo, notificación push ni contacto con familiares. El sonido funciona solo al pulsar el botón y no es alarma de emergencia. Las fuentes se revisan manualmente. Una URL válida no acredita un aviso oficial. El directorio empieza vacío para no inventar teléfonos. No usar datos personales reales en esta versión.

## Requisitos

PHP 8.4+ compatible con composer.lock, Composer 2 y extensiones ctype, curl, dom, fileinfo, mbstring, openssl, pdo_sqlite, sqlite3, tokenizer, xml y zip. Se probó con PHP 8.4.27, Laravel 13.40.0 y SQLite en Windows. No necesita Python, venv ni un entorno virtual. La instalación manual indicada abajo no necesita Node ni compilación frontend: utiliza public/app.css y public/app.js. Los comandos opcionales setup/dev del esqueleto no se usan en este procedimiento.

## Instalación limpia en Windows PowerShell

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed
php artisan alerta:usuario admin@example.test administrador
php artisan serve --host=127.0.0.1 --port=8000
```

El comando alerta:usuario solicita nombre y contraseña de forma oculta. No hay contraseñas por defecto. Crea operadores con el mismo comando y rol operador. Los ciudadanos pueden registrarse en /registro. Abre http://127.0.0.1:8000. En Linux/macOS usa cp en vez de Copy-Item. No sobrescribas un .env existente sin revisar su configuración.

Los seeders agregan tres distritos y avisos ficticios que vencen siete días después de sembrarse por primera vez. Son idempotentes y no prolongan automáticamente la vigencia de registros existentes. Para practicar después, crea un aviso nuevo o actualiza las fechas mediante el panel.

## Flujo para demostrar los avisos

1. Registra una cuenta ciudadana de prueba, elige Socabaya y sal.
2. Inicia sesión como administrador u operador y crea una alerta para Socabaya con fechas válidas y estado publicada.
3. Sal y entra como ciudadano. Verifica Mi bandeja y marca el aviso como leído.
4. Una edición de una alerta que sigue publicada no vuelve a notificar. Cerrar y volver a publicar sí representa una nueva publicación.

## Arquitectura

Modelos en app/Models; controladores PublicController, AuthController y ResourceController; vistas Blade en resources/views; rutas en routes/web.php. ResourceController utiliza una lista cerrada de cuatro entidades y reglas específicas. El middleware Staff protege el panel; zonas requiere además administrador. Las notificaciones se guardan dentro de la transacción de publicación. No hay procesos de envío externos.

## Pruebas

```powershell
php artisan test --compact
```

Resultado de la revisión local: 11 pruebas, 93 comprobaciones aprobadas. Escenarios: cuatro CRUD, validación, integridad de zonas, permisos, hash, roles, login/logout, XSS, filtro SQL, vigencia, preferencias y propiedad de notificaciones. Además se comprobó HTTP 419 para un POST sin CSRF, interfaz de 390 px sin desbordamiento horizontal y flujo en Edge sin errores JavaScript observados. docs/PRUEBAS.md detalla límites.

## Seguridad y operación

El registro no acepta role del cliente. Eloquent parametriza consultas; Blade escapa texto. Todos los formularios mutables incluyen CSRF. Solo se aceptan fuentes HTTPS. El login y registro tienen límite de 5 solicitudes por minuto. APP_DEBUG=false. Mantén .env, bases, logs, claves y material de entrevistas fuera de Git. Antes de publicar: HTTPS, cookies seguras, responsable de privacidad, plazos de conservación, respaldo/restauración y revisión de contenido. El servidor artisan serve es solo para desarrollo local.

## Colaboración y entrega

El ZIP excluye vendor, node_modules, .env, bases, caches, logs y .git. Incluye composer.lock para reproducir dependencias. Cada integrante debe usar su identidad real y realizar cambios comprensibles con evidencia de prueba. El historial inicial se identifica como preparación asistida por Codex. No acredita aportes individuales de los estudiantes. Consulta docs/ENTREGA_GIT.md para registrar cambios reales y comprobar la entrega. La URL fue publicada y verificada el 7 de noviembre de 2026. Los videos de entrevistas no pertenecen al repositorio de código.

## Licencias y asistencia

Las dependencias conservan sus licencias; ver docs/LICENCIAS_DEPENDENCIAS.json y docs/LARAVEL_LICENSE.md. La decisión de licencia del código específico del equipo está pendiente de acuerdo; no se concede aquí una licencia abierta adicional. La licencia MIT de Laravel no equivale a autoría del equipo. La base y documentación fueron generadas con asistencia de Codex y requieren revisión y declaración según la política del curso.

## Diferencia entre venv, .env y dependencias

- `venv` o `.venv` es un entorno virtual de Python. Esta aplicación no lo utiliza.
- `.env` es configuración local de Laravel. No se incluye en el ZIP ni en Git. Cada instalación crea su propia configuración y clave siguiendo los comandos anteriores.
- `.env.example` es una plantilla sin claves ni credenciales; sí se entrega.
- `vendor` contiene las librerías de Laravel instaladas por Composer. Se necesita para ejecutar la aplicación, pero se excluye de la entrega; se reconstruye con `composer install` y `composer.lock`.
- Los scripts Python usados para preparar documentos o empaquetar esta entrega no forman parte de la aplicación ni se requieren para instalarla.

## Zonas afectadas

El inicio incluye un resumen por distrito de todos los avisos de demostración publicados y vigentes que coinciden con los filtros, con fecha, nivel y referencia. No confirma afectaciones reales ni atribuye eventos a El Niño. La conexión con reportes oficiales sigue pendiente.
