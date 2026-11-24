# Publicar AlertaNiño AQP en Render (gratis, sin tu PC)

Render deja la web **siempre accesible** sin tu computadora. Es gratis y no pide tarjeta.

## Lo que debes saber antes

- El servidor de Render está **fuera de Perú**, y SENAMHI solo responde dentro del Perú.
  Por eso el **nivel por distrito de SENAMHI** y el **comunicado de ENFEN** se toman de una
  **copia** que se publica desde una PC en Perú (el "espejo"). Esos dos datos quedan con la
  fecha de la última copia; para refrescarlos, ejecuta desde tu PC en Perú:
  `powershell -ExecutionPolicy Bypass -File scripts\publicar_datos_oficiales.ps1`.
- El **mapa, las emergencias de INDECI y el pronóstico** sí se actualizan en vivo para quien
  abra la web desde Perú (los carga su navegador).
- El plan gratuito **se duerme tras 15 min sin visitas** y tarda ~50 s en despertar en la
  primera visita. La base de datos SQLite se reinicia en cada despliegue (los distritos,
  guías, contactos y el administrador se vuelven a crear solos).

## Pasos (una sola vez)

1. Entra a **https://render.com** y crea una cuenta gratis (puedes usar tu cuenta de GitHub).
2. Autoriza a Render a ver tu repositorio **FrankoAyaqui/AlertaNino-AQP**.
3. Pulsa **New +  →  Blueprint**. Elige el repositorio y la rama **codex/entrega**.
   Render detecta el archivo `render.yaml` y propone el servicio **alertanino-aqp**.
4. Te pedirá completar las variables marcadas. Escríbelas:
   | Variable | Valor |
   |---|---|
   | `APP_KEY` | la clave `base64:...` que te paso en el chat (o genera otra) |
   | `APP_URL` | déjala vacía por ahora; la pones en el paso 6 |
   | `ADMIN_EMAIL` | tu correo de administrador |
   | `ADMIN_PASSWORD` | tu contraseña (12+ caracteres, con mayúscula, minúscula y número) |
5. Pulsa **Apply / Create**. Render construye la imagen (tarda unos minutos) y te da una URL
   como `https://alertanino-aqp.onrender.com`.
6. Copia esa URL, ponla en la variable **`APP_URL`** (Environment → Save). Render redespliega
   solo. Listo: esa es tu dirección pública permanente.

## Entrar como administrador

En la web → **Ingresar** con el `ADMIN_EMAIL` y la `ADMIN_PASSWORD` que pusiste →
**Panel → Seguridad** para ver la trazabilidad.

## Actualizar el código más adelante

Cada vez que se haga `git push` a la rama `codex/entrega`, Render vuelve a desplegar solo
(`autoDeploy: true`).
