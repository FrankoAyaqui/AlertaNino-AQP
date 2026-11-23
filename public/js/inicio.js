// Portada: mapa con la capa oficial de SENAMHI, pronóstico de referencia y alertas del dispositivo.
import { barras } from './graficos.js';
import { sonar, detener, notificar } from './app.js';

const $ = (s) => document.querySelector(s);
const leer = (k, def = null) => { try { const v = localStorage.getItem(k); return v == null ? def : JSON.parse(v); } catch { return def; } };
const guardar = (k, v) => { try { localStorage.setItem(k, JSON.stringify(v)); } catch { /* nada */ } };
const num = (v, d = 1) => (v == null ? '—' : Number(v).toLocaleString('es-PE', { minimumFractionDigits: d, maximumFractionDigits: d }));
const nombreNivel = (n) => (n ? `Nivel ${n}` : 'Sin aviso');

const mapaEl = $('#mapa');
const seleccionado = mapaEl?.dataset.seleccionado;
if (seleccionado) guardar('distrito', seleccionado);

// ------------------------------------------------------------------- mapa

let mapa = null;
const marcadores = {};
let capaEmergencias = null;

function iniciarMapa() {
  if (!mapaEl) return;
  if (!window.L) {
    mapaEl.classList.add('mapa--error');
    mapaEl.textContent = 'No se pudo cargar el mapa. La tabla contiene la misma información.';
    return;
  }
  mapa = L.map(mapaEl, { scrollWheelZoom: false }).setView([-16.41, -71.54], 12);
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 18, attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
  }).addTo(mapa);
  // Capa oficial del aviso por lluvias, servida en vivo por SENAMHI.
  L.tileLayer.wms(mapaEl.dataset.wms, {
    layers: mapaEl.dataset.capa, format: 'image/png', transparent: true, opacity: 0.5,
    attribution: 'Aviso por lluvias © <a href="https://idesep.senamhi.gob.pe/portalidesep/">SENAMHI</a>',
  }).addTo(mapa);
  capaEmergencias = L.layerGroup().addTo(mapa);
}

function pintarMapa(datos) {
  if (!mapa) return;
  for (const d of datos.distritos) {
    const n = d.senamhi?.nivel ?? 0;
    const activo = d.slug === seleccionado;
    const html = `<div class="marcador${activo ? ' marcador--activo' : ''}" data-nivel="n${n}">${d.nombre.slice(0, 2).toUpperCase()}</div>`;
    const icono = L.divIcon({ className: '', html, iconSize: [30, 30], iconAnchor: [15, 15] });
    const texto = `${d.nombre}: SENAMHI ${nombreNivel(n)}${d.avisos_vigentes ? ` · ${d.avisos_vigentes} aviso(s) vigente(s)` : ''}`;
    if (marcadores[d.slug]) {
      marcadores[d.slug].setIcon(icono).setTooltipContent(texto);
    } else {
      marcadores[d.slug] = L.marker([d.latitud, d.longitud], { icon: icono, title: d.nombre, alt: d.nombre, zIndexOffset: activo ? 1000 : 0 })
        .bindTooltip(texto, { direction: 'top', offset: [0, -14] })
        .on('click', () => { window.location.href = `?distrito=${encodeURIComponent(d.slug)}`; })
        .addTo(mapa);
    }
  }
  capaEmergencias.clearLayers();
  for (const e of datos.emergencias ?? []) {
    L.circleMarker([e.latitud, e.longitud], { radius: 6, color: '#52514e', weight: 2, fillColor: '#ffffff', fillOpacity: 1 })
      .bindTooltip(`INDECI: ${e.fenomeno.toLowerCase()} en ${e.distrito}, ${e.fecha}`)
      .addTo(capaEmergencias);
  }
}

iniciarMapa();
document.addEventListener('estado-actualizado', (ev) => pintarMapa(ev.detail));

// -------------------------------------------------- pronóstico de referencia

async function pronostico() {
  const g = $('#g-semana');
  if (!g?.dataset.lat) return;
  const url = 'https://api.open-meteo.com/v1/forecast?' + new URLSearchParams({
    latitude: g.dataset.lat, longitude: g.dataset.lon, timezone: 'America/Lima', forecast_days: '7',
    current: 'temperature_2m,precipitation,weather_code',
    daily: 'precipitation_sum,precipitation_probability_max,temperature_2m_max,temperature_2m_min',
  });
  try {
    const r = await fetch(url);
    if (!r.ok) throw new Error();
    const f = await r.json();
    const dia = (iso) => new Date(iso + 'T12:00:00Z').toLocaleDateString('es-PE', { timeZone: 'UTC', weekday: 'short', day: 'numeric' });
    $('#clima-actual').textContent = `${num(f.current.temperature_2m)} °C`;
    $('#lluvia-hoy').textContent = `${num(f.daily.precipitation_sum[0])} mm · ${f.daily.precipitation_probability_max[0] ?? '—'} %`;
    barras(g, {
      etiquetas: f.daily.time.map(dia),
      valores: f.daily.precipitation_sum.map((x) => x ?? 0),
      tooltip: (k) => `<strong>${dia(f.daily.time[k])}</strong>${num(f.daily.precipitation_sum[k])} mm · prob. ${f.daily.precipitation_probability_max[k] ?? '—'} %`,
    });
    // Tabla equivalente para lectores de pantalla y para quien prefiera texto.
    const tbody = $('#tabla-semana tbody');
    tbody.replaceChildren(...f.daily.time.map((t, k) => {
      const tr = document.createElement('tr');
      for (const texto of [dia(t), `${num(f.daily.precipitation_sum[k])} mm`, `${f.daily.precipitation_probability_max[k] ?? '—'} %`,
        `${num(f.daily.temperature_2m_min[k], 0)}–${num(f.daily.temperature_2m_max[k], 0)} °C`]) {
        const td = document.createElement('td');
        td.textContent = texto;
        tr.append(td);
      }
      return tr;
    }));
  } catch {
    g.textContent = 'No se pudo cargar el pronóstico de referencia.';
  }
}
pronostico();

// ------------------------------------------------------ alertas del dispositivo

const optN = $('#opt-notificar');
if (optN) {
  optN.checked = leer('opt:notificar', false) && 'Notification' in window && Notification.permission === 'granted';
  optN.addEventListener('change', async () => {
    if (optN.checked) {
      if (!('Notification' in window)) { optN.checked = false; $('#notif-estado').textContent = 'Este navegador no permite notificaciones.'; return; }
      const permiso = await Notification.requestPermission();
      if (permiso !== 'granted') { optN.checked = false; $('#notif-estado').textContent = 'Permiso denegado. Puedes activarlo en la configuración del navegador.'; return; }
      guardar('opt:notificar', true);
      notificar('Notificaciones activadas. Te avisaremos si SENAMHI eleva el nivel de tu distrito.');
    } else guardar('opt:notificar', false);
  });
}
const optS = $('#opt-sonido');
if (optS) {
  optS.checked = leer('opt:sonido', false);
  optS.addEventListener('change', () => guardar('opt:sonido', optS.checked));
}
const tono = $('#tono');
if (tono) {
  tono.value = leer('opt:tono', 'campana');
  tono.addEventListener('change', () => guardar('opt:tono', tono.value));
}
$('#probar-sonido')?.addEventListener('click', () => sonar(tono?.value ?? 'campana'));
$('#detener-sonido')?.addEventListener('click', detener);
