# Evidencia de verificación

Fecha: 2026-11-07. Entorno: PHP 8.4.27, Laravel 13.40.0, SQLite, Windows.

- PHPUnit: 11 pruebas aprobadas; 93 comprobaciones.
- HTTP: GET / correcto; POST /login sin token devuelve 419.
- Navegador Edge aislado: inicio, filtro Socabaya, detalle, prueba de sonido, login, panel, formulario y logout correctos.
- Responsive: 1365 px y 390 px. No hubo desbordamiento horizontal de la página en 390 px.
- JavaScript: cero errores observados en el recorrido público.

Las pruebas no equivalen a auditoría exhaustiva. No se verificaron carga, uptime prolongado, todos los navegadores, lectores de pantalla, comprensión de beneficiarios ni integraciones externas. El cambio de tamaño del texto se verificó en la interfaz, no como conformidad WCAG.

Las pruebas automáticas usan base SQLite en memoria; CSRF se comprobó por HTTP real porque el framework lo omite en pruebas de aplicación. El código de los casos se incluye en tests/Feature/AlertaTest.php. Para reproducir: php artisan test --compact.

## Instalación del ZIP generado desde Git — 7 de noviembre de 2026

Se extrajo el código en una carpeta nueva, se instalaron las dependencias con PHP 8.4.27 y Composer 2.11.1, se creó la configuración local y se ejecutaron migraciones y seeders. Resultado: 11 pruebas aprobadas y 93 comprobaciones. No se utilizó Python, venv, Node ni npm para instalar o ejecutar la aplicación. La configuración y dependencias se generaron solo en la carpeta de prueba; están excluidas del ZIP y del repositorio.
