# Evidencia de verificación

Fecha: 2026-11-07. Entorno: PHP 8.4.27, Laravel 13.40.0, SQLite, Windows.

- PHPUnit: 11 pruebas aprobadas; 93 comprobaciones.
- HTTP: GET / correcto; POST /login sin token devuelve 419.
- Navegador Edge aislado: inicio, filtro Socabaya, detalle, prueba de sonido, login, panel, formulario y logout correctos.
- Responsive: 1365 px y 390 px. No hubo desbordamiento horizontal de la página en 390 px.
- JavaScript: cero errores observados en el recorrido público.

Las pruebas no equivalen a auditoría exhaustiva. No se verificaron carga, uptime prolongado, todos los navegadores, lectores de pantalla, comprensión de beneficiarios ni integraciones externas. El cambio de tamaño del texto se verificó en la interfaz, no como conformidad WCAG.

Las pruebas automáticas usan base SQLite en memoria; CSRF se comprobó por HTTP real porque el framework lo omite en pruebas de aplicación. El código de los casos se incluye en tests/Feature/AlertaTest.php. Para reproducir: php artisan test --compact.
