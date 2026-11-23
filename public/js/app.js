// AlertaNiño AQP — comportamiento común a todas las páginas.
// Sin scripts en línea: la política de seguridad (CSP) solo permite archivos propios.

const $ = (s, raiz = document) => raiz.querySelector(s);
const leer = (k, def = null) => { try { const v = localStorage.getItem(k); return v == null ? def : JSON.parse(v); } catch { return def; } };
const guardar = (k, v) => { try { localStorage.setItem(k, JSON.stringify(v)); } catch { /* sin almacenamiento */ } };

// ---------------------------------------------------------------- lectura

let escala = Number(leer('escala', 1)) || 1;
const aplicarEscala = () => document.documentElement.style.setProperty('--escala', escala);
$('#texto-mas')?.addEventListener('click', () => { escala = Math.min(1.5, +(escala + 0.1).toFixed(2)); guardar('escala', escala); aplicarEscala(); });
$('#texto-menos')?.addEventListener('click', () => { escala = Math.max(0.9, +(escala - 0.1).toFixed(2)); guardar('escala', escala); aplicarEscala(); });

$('#tema')?.addEventListener('click', () => {
  const actual = document.documentElement.dataset.theme
    || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
  const nuevo = actual === 'dark' ? 'light' : 'dark';
  document.documentElement.dataset.theme = nuevo;
  try { localStorage.setItem('tema', nuevo); } catch { /* nada */ }
});

// Menú en celulares
const menu = $('#menu-movil');
menu?.addEventListener('click', () => {
  const nav = $('#nav-principal');
  const abierto = nav.dataset.abierto === 'true';
  nav.dataset.abierto = String(!abierto);
  menu.setAttribute('aria-expanded', String(!abierto));
});

// ------------------------------------------------------------ formularios

// Confirmación antes de eliminar (el servidor vuelve a autorizar de todas formas).
document.querySelectorAll('form[data-confirmar]').forEach((f) => {
  f.addEventListener('submit', (e) => { if (!confirm(f.dataset.confirmar)) e.preventDefault(); });
});

// Formularios lentos: desactiva el botón para evitar envíos dobles.
document.querySelectorAll('form[data-ocupado]').forEach((f) => {
  f.addEventListener('submit', () => {
    const b = f.querySelector('button[type=submit]');
    if (b) { b.disabled = true; b.textContent = f.dataset.ocupado; }
  });
});

// Listas que se envían solas al cambiar (con botón visible como alternativa).
document.querySelectorAll('select[data-auto-enviar]').forEach((s) => s.addEventListener('change', () => s.form.submit()));

// ---------------------------------------------------------------- avisos

export function toast(texto) {
  const t = $('#toast');
  if (!t) return;
  t.textContent = texto;
  t.hidden = false;
  clearTimeout(toast.t);
  toast.t = setTimeout(() => { t.hidden = true; }, 9000);
}

let audio = null;
export function detener() { if (audio) { audio.close().catch(() => {}); audio = null; } }

/** Tonos generados con Web Audio (sin archivos externos). Solo suenan tras una acción del usuario. */
export function sonar(tono) {
  detener();
  const Ctx = window.AudioContext || window.webkitAudioContext;
  if (!Ctx) return;
  audio = new Ctx();
  const t0 = audio.currentTime + 0.05;
  const volumen = audio.createGain();
  volumen.gain.value = 0.18;
  volumen.connect(audio.destination);
  const nota = (frec, ini, dur, tipo = 'sine', hasta) => {
    const o = audio.createOscillator();
    const g = audio.createGain();
    o.type = tipo;
    o.frequency.setValueAtTime(frec, ini);
    if (hasta) o.frequency.linearRampToValueAtTime(hasta, ini + dur);
    g.gain.setValueAtTime(0.0001, ini);
    g.gain.exponentialRampToValueAtTime(1, ini + 0.02);
    g.gain.exponentialRampToValueAtTime(0.0001, ini + dur);
    o.connect(g).connect(volumen);
    o.start(ini); o.stop(ini + dur + 0.05);
  };
  if (tono === 'campana') [0, 0.9, 1.8].forEach((s) => { nota(880, t0 + s, 0.8); nota(1320, t0 + s, 0.6); });
  if (tono === 'pitido') for (let k = 0; k < 6; k++) nota(1000, t0 + k * 0.35, 0.18, 'square');
  if (tono === 'sirena') for (let k = 0; k < 3; k++) { nota(600, t0 + k, 0.5, 'sawtooth', 1100); nota(1100, t0 + k + 0.5, 0.5, 'sawtooth', 600); }
  setTimeout(detener, 3500);
}

export async function notificar(texto) {
  toast(texto);
  if (leer('opt:sonido', false)) sonar(leer('opt:tono', 'campana'));
  if (!leer('opt:notificar', false) || !('Notification' in window) || Notification.permission !== 'granted') return;
  try { new Notification('AlertaNiño AQP', { body: texto, icon: '/icono.svg', tag: 'alertanino' }); } catch { /* bloqueado */ }
}

// ------------------------------------------------- vigilancia en tiempo real

/**
 * Cada 5 minutos consulta la API pública y avisa si SENAMHI elevó el nivel
 * del distrito del usuario (o del elegido en la portada) o si ENFEN cambió de estado.
 */
async function vigilar() {
  const api = document.body.dataset.api;
  const slug = document.body.dataset.distritoUsuario || $('#mapa')?.dataset.seleccionado || leer('distrito');
  if (!api) return;
  try {
    const r = await fetch(api, { headers: { Accept: 'application/json' }, cache: 'no-store' });
    if (!r.ok) return;
    const datos = await r.json();
    const d = datos.distritos.find((x) => x.slug === slug);
    if (d?.senamhi) {
      const previo = leer(`nivel:${slug}`, null);
      guardar(`nivel:${slug}`, d.senamhi.nivel);
      if (previo != null && d.senamhi.nivel > previo) {
        notificar(`SENAMHI elevó el aviso por lluvias en ${d.nombre} a Nivel ${d.senamhi.nivel}.`);
      }
    }
    if (datos.enfen) {
      const previo = leer('enfen', null);
      guardar('enfen', datos.enfen.estado);
      if (previo && previo !== datos.enfen.estado) notificar(`ENFEN cambió su estado: ${datos.enfen.estado}.`);
    }
    document.dispatchEvent(new CustomEvent('estado-actualizado', { detail: datos }));
  } catch { /* sin conexión: se reintenta en la próxima vuelta */ }
}
vigilar();
setInterval(vigilar, 5 * 60 * 1000);
