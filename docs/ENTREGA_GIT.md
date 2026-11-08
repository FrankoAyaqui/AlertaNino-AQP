# Repositorio y colaboración real

La base académica fue preparada con asistencia de Codex. Su commit inicial describe esa procedencia y no representa contribuciones individuales de los tres integrantes.

## Archivos que se entregan

El ZIP y el repositorio contienen código, pruebas, documentación, composer.json, composer.lock y .env.example. No incluyen vendor, node_modules, .env, bases de ejecución, logs ni entornos virtuales. Git conserva su historial en el repositorio; el ZIP no contiene la carpeta .git.

## Participación de cada integrante

1. Clonar el repositorio publicado y seguir el README para instalarlo con PHP y Composer. No hace falta Python ni venv.
2. Configurar la identidad real en su clon (reemplazar los valores; no usar el nombre de otra persona):

```sh
git config user.name "Nombre real del integrante"
git config user.email "Correo verificado de su cuenta GitHub"
git switch -c mejora/nombre-tarea
```

3. Realizar y comprender un cambio útil; por ejemplo, revisar una guía con su fuente, corregir una barrera de accesibilidad o agregar un caso de validación que faltaba. Registrar qué cambió y cómo se verificó. Cada persona debe realizar su propio trabajo.
4. Revisar solo los archivos previstos y crear su commit:

```sh
git status --short
git diff
git add ruta/del/archivo-modificado
git commit -m "Describir el cambio real y su motivo"
git push -u origin HEAD
```

5. Abrir una solicitud de cambios para revisión de otro integrante. Conservar los commits al integrarla; no cambiar autores ni fechas. Distribuir el trabajo durante el periodo real disponible.
6. Registrar en la tabla una URL que pueda abrir el docente. Agregar filas por cada aporte; no completar con cambios ajenos.

| Integrante | Usuario GitHub | Tarea y validación | URL del commit o PR |
| --- | --- | --- | --- |
| Franko Joel Ayaqui Arredondo | FrankoAyaqui | Completar con aporte real | Pendiente |
| Kevin Jolver Montañez Taype | Completar usuario | Completar con aporte real | Pendiente |
| Alvaro Sebastian Meza Salas | Completar usuario | Completar con aporte real | Pendiente |

## Acceso para la evaluación

Si el repositorio es privado, su propietario debe dar acceso al docente y a los integrantes mediante Settings / Collaborators. No subir videos de entrevistas, consentimientos ni datos personales al repositorio del código. El enlace del repositorio y los commits verificables constituyen la evidencia Git; un ZIP no sustituye el historial.

## Verificación de entrega

```sh
git status --short
git log --format="%h | %an | %ad | %s" --date=short
git ls-files
```

El primer comando debe estar vacío después del commit. Revisar que git ls-files no liste archivos .env, dependencias instaladas, claves, bases ni videos. Para generar un ZIP del código confirmado:

```sh
git archive --format=zip --prefix=AlertaNino_AQP/ -o ../C2_PW_GrupoXX_Codigo.zip HEAD
```

Cambiar XX por el grupo real. Las dependencias se instalan en el equipo de evaluación y no se copian al ZIP.
