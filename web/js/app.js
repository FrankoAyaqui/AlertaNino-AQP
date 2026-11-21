// AlertaNiño AQP — lógica principal.
// Datos en vivo desde el navegador (Open-Meteo admite CORS) y un JSON estático
// con los índices de El Niño y los umbrales, que GitHub Actions regenera.

import { DISTRITOS, RIO_CHILI, NIVELES, ACCIONES, cielo } from "./datos.js";
import { barras, linea } from "./graficos.js";

const CADA_MS = 15 * 60 * 1000;          // consulta cada 15 min (sección 3.11 del informe)
const DEGRADADO_MS = 30 * 60 * 1000;     // tras 30 min sin éxito: "Actualización no verificada"
const TZ = "America/Lima";
const $ = (s) => document.querySelector(s);
// ?demo=1 simula lluvia fuerte sobre los datos reales, para presentar la app
// en temporada seca. Siempre se rotula como demostración.
const DEMO = new URLSearchParams(location.search).has("demo");
const PREFIJO = DEMO ? "demo:" : "";

// ------------------------------------------------------------ utilidades

const guardar = (k, v) => { try { localStorage.setItem(k, JSON.stringify(v)); } catch { /* sin almacenamiento */ } };
const leer = (k, def = null) => { try { const v = localStorage.getItem(k); return v == null ? def : JSON.parse(v); } catch { return def; } };

const num = (v, d = 1) => (v == null || Number.isNaN(v) ? "—" : v.toLocaleString("es-PE", { minimumFractionDigits: d, maximumFractionDigits: d }));
const hora = (fecha) => new Intl.DateTimeFormat("es-PE", { timeZone: TZ, hour: "2-digit", minute: "2-digit" }).format(fecha);
const fechaLarga = (fecha) => new Intl.DateTimeFormat("es-PE", { timeZone: TZ, weekday: "long", day: "numeric", month: "long", hour: "2-digit", minute: "2-digit" }).format(fecha);
const diaCorto = (iso) => new Intl.DateTimeFormat("es-PE", { timeZone: "UTC", weekday: "short", day: "numeric" }).format(new Date(iso + "T12:00:00Z"));
const diaMes = (iso) => new Intl.DateTimeFormat("es-PE", { timeZone: "UTC", day: "numeric", month: "short" }).format(new Date(iso + "T12:00:00Z"));

function ahoraLima() {
  const p = Object.fromEntries(new Intl.DateTimeFormat("en-CA", { timeZone: TZ, year: "numeric", month: "2-digit", day: "2-digit", hour: "2-digit", hourCycle: "h23" })
    .formatToParts(new Date()).map((x) => [x.type, x.value]));
  return { dia: `${p.year}-${p.month}-${p.day}`, hora: `${p.year}-${p.month}-${p.day}T${p.hour}:00` };
}

async function pedirJSON(url, ms = 20000) {
  const ctrl = new AbortController();
  const t = setTimeout(() => ctrl.abort(), ms);
  try {
    const r = await fetch(url, { signal: ctrl.signal, cache: "no-store" });
    if (!r.ok) throw new Error(`HTTP ${r.status}`);
    return await r.json();
  } finally { clearTimeout(t); }
}

function insignia(nivel, chica = false) {
  const info = NIVELES[nivel] ?? { nombre: "Sin datos", icono: "?" };
  const s = document.createElement("span");
  s.className = "insignia" + (chica ? " insignia--chica" : "");
  s.dataset.nivel = nivel ?? "";
  s.innerHTML = `<span class="insignia__icono" aria-hidden="true"></span><span class="insignia__texto"></span>`;
  s.firstChild.textContent = info.icono;
  s.lastChild.textContent = chica ? info.corto ?? info.nombre : info.nombre;
  return s;
}

// --------------------------------------------------------------- estado

const estado = {
  distrito: leer("distrito", "cercado"),
  indicadores: null,
  pronostico: null,   // respuesta Open-Meteo (array por distrito)
  rio: null,          // respuesta Flood API
  ultimoExito: leer("ultimoExito", 0),
  niveles: {},        // id -> resultado de evaluarDistrito
  mapa: null,
  marcadores: {},
};
if (!DISTRITOS.some((d) => d.id === estado.distrito)) estado.distrito = "cercado";

// --------------------------------------------------------- carga de datos

async function cargarIndicadores() {
  // Cambia cada hora para no quedarse con una copia antigua en caché.
  const marca = Math.floor(Date.now() / 3600000);
  try {
    estado.indicadores = await pedirJSON(`data/indicadores.json?h=${marca}`);
    guardar("cache:indicadores", estado.indicadores);
  } catch {
    estado.indicadores = leer("cache:indicadores");
  }
}

function urlPronostico() {
  const lat = DISTRITOS.map((d) => d.lat).join(",");
  const lon = DISTRITOS.map((d) => d.lon).join(",");
  return "https://api.open-meteo.com/v1/forecast?" + new URLSearchParams({
    latitude: lat, longitude: lon, timezone: TZ, forecast_days: "7",
    current: "temperature_2m,precipitation,weather_code",
    hourly: "precipitation,precipitation_probability",
    daily: "precipitation_sum,precipitation_probability_max,weather_code,temperature_2m_max,temperature_2m_min",
  });
}

function urlRio() {
  return "https://flood-api.open-meteo.com/v1/flood?" + new URLSearchParams({
    latitude: RIO_CHILI.lat, longitude: RIO_CHILI.lon, past_days: "60", forecast_days: "30",
    daily: "river_discharge,river_discharge_min,river_discharge_max",
  });
}

async function cargarEnVivo() {
  const [p, r] = await Promise.allSettled([pedirJSON(urlPronostico()), pedirJSON(urlRio())]);
  let exito = false;
  if (p.status === "fulfilled" && Array.isArray(p.value)) {
    estado.pronostico = p.value; guardar("cache:pronostico", p.value); exito = true;
  } else estado.pronostico ??= leer("cache:pronostico");
  if (r.status === "fulfilled" && r.value?.daily) {
    estado.rio = r.value; guardar("cache:rio", r.value);
  } else estado.rio ??= leer("cache:rio");
  if (exito) { estado.ultimoExito = Date.now(); guardar("ultimoExito", estado.ultimoExito); }
  if (DEMO) simular();
  return exito;
}

// Lluvia simulada mañana por la tarde, distinta por distrito para mostrar
// todos los niveles. Copia los datos: nunca toca la caché real.
function simular() {
  // Parte siempre de la copia real guardada, para no simular dos veces.
  estado.pronostico = leer("cache:pronostico");
  estado.rio = leer("cache:rio");
  if (!estado.pronostico) return;
  const mm = [8, 26, 20, 6, 15, 18, 22, 14, 19, 24, 16, 13, 21, 28, 17, 5, 23, 4];
  const { dia } = ahoraLima();
  estado.pronostico.forEach((f, i) => {
    let k = f.daily.time.indexOf(dia) + 1;
    if (k < 1) k = 1;
    const total = mm[i % mm.length];
    f.daily.precipitation_sum[k] = total;
    f.daily.precipitation_probability_max[k] = 85;
    f.daily.weather_code[k] = total > 15 ? 65 : 63;
    const h0 = f.hourly.time.indexOf(`${f.daily.time[k]}T14:00`);
    if (h0 >= 0) [0.15, 0.35, 0.3, 0.15, 0.05].forEach((p, j) => {
      f.hourly.precipitation[h0 + j] = +(total * p).toFixed(1);
      f.hourly.precipitation_probability[h0 + j] = 85;
    });
  });
  if (estado.rio?.daily) {
    const d = estado.rio.daily;
    const hoy = d.time.indexOf(dia);
    d.time.forEach((_, i) => {
      if (i <= hoy) return;
      const f = Math.min(4, 1 + (i - hoy) * 0.8);
      d.river_discharge[i] *= f; d.river_discharge_min[i] *= f * 0.8; d.river_discharge_max[i] *= f * 1.25;
    });
  }
}

// ------------------------------------------------------------ evaluación

function nivelPorUmbral(valor, u) {
  if (valor == null) return null;
  if (valor >= u.p99) return 3;
  if (valor >= u.p95) return 2;
  if (valor >= u.p90) return 1;
  return 0;
}

function evaluarRio() {
  const u = estado.indicadores?.umbrales_rio;
  const d = estado.rio?.daily;
  if (!u || !d) return null;
  const hoy = ahoraLima().dia;
  let iHoy = d.time.indexOf(hoy);
  if (iHoy < 0) iHoy = d.time.findIndex((t) => t > hoy) - 1;
  if (iHoy < 0) iHoy = d.time.length - 1;
  const proximos = d.river_discharge.slice(iHoy, iHoy + 7).filter((x) => x != null);
  const maxProx = Math.max(...proximos);
  const nivel = maxProx >= u.p99 ? 2 : maxProx >= u.p90 ? 1 : 0;
  return { nivel, hoy: d.river_discharge[iHoy], maxProx, iHoy, u };
}

function evaluarDistrito(i) {
  const f = estado.pronostico?.[i];
  const u = estado.indicadores?.umbrales_lluvia;
  if (!f || !u) return null;
  const { dia, hora: h } = ahoraLima();
  let i0 = f.daily.time.indexOf(dia);
  if (i0 < 0) i0 = 0;
  const dias = [0, 1, 2].map((k) => i0 + k).filter((k) => k < f.daily.time.length);
  let maxDia = -1, iMax = dias[0];
  for (const k of dias) if ((f.daily.precipitation_sum[k] ?? 0) > maxDia) { maxDia = f.daily.precipitation_sum[k] ?? 0; iMax = k; }
  let j0 = f.hourly.time.indexOf(h);
  if (j0 < 0) j0 = 0;
  const horas = f.hourly.precipitation.slice(j0, j0 + 48).map((x) => x ?? 0);
  const maxHora = Math.max(0, ...horas);
  const total48 = horas.reduce((a, b) => a + b, 0);
  const nDia = nivelPorUmbral(maxDia, u.diaria_mm);
  const nHora = maxHora >= u.horaria_mm.p99 ? 2 : maxHora >= u.horaria_mm.p95 ? 1 : 0;
  return {
    nivelLluvia: Math.max(nDia, nHora), nDia, nHora,
    maxDia, diaMax: f.daily.time[iMax], probDia: f.daily.precipitation_probability_max[iMax],
    maxHora, horaMax: f.hourly.time[j0 + horas.indexOf(maxHora)], total48, j0, i0,
  };
}

function evaluarTodo() {
  const rio = evaluarRio();
  estado.evalRio = rio;
  DISTRITOS.forEach((d, i) => {
    const e = evaluarDistrito(i);
    if (!e) { estado.niveles[d.id] = null; return; }
    const nRio = d.rio && rio ? rio.nivel : 0;
    estado.niveles[d.id] = { ...e, nRio, nivel: Math.max(e.nivelLluvia, nRio) };
  });
}

// ------------------------------------------------------------- render

function renderTarjeta() {
  const d = DISTRITOS.find((x) => x.id === estado.distrito);
  const idx = DISTRITOS.indexOf(d);
  const e = estado.niveles[d.id];
  const tarjeta = $("#tarjeta-estado");
  const caja = $("#insignia");
  if (!e) {
    tarjeta.dataset.nivel = "cargando";
    caja.replaceWith(Object.assign(insignia(null), { id: "insignia" }));
    $("#estado-titulo").textContent = "No se pudo obtener el pronóstico";
    $("#estado-detalle").textContent = "Revisa tu conexión. La falta de datos no significa que no haya riesgo.";
    $("#acciones").innerHTML = "";
    return;
  }
  tarjeta.dataset.nivel = e.nivel;
  caja.replaceWith(Object.assign(insignia(e.nivel), { id: "insignia" }));
  const titulos = [
    `Sin lluvia fuera de lo habitual en ${d.nombre}`,
    `Atención por lluvia en ${d.nombre}`,
    `Lluvia fuerte prevista en ${d.nombre}`,
    `Lluvia muy fuerte prevista en ${d.nombre}`,
  ];
  $("#estado-titulo").textContent = e.nRio > e.nivelLluvia
    ? `Caudal alto del río Chili cerca de ${d.nombre}`
    : titulos[e.nivel];
  const actual = estado.pronostico[idx].current;
  $("#estado-detalle").textContent =
    `Ahora: ${cielo(actual.weather_code)}, ${num(actual.temperature_2m)} °C` +
    (actual.precipitation > 0 ? `, lloviendo ${num(actual.precipitation)} mm/h.` : ".") +
    ` Nivel calculado para las próximas 72 horas.`;

  const cifras = $("#cifras");
  cifras.innerHTML = "";
  const u = estado.indicadores.umbrales_lluvia;
  const agregar = (dt, dd, extra) => {
    const div = document.createElement("div");
    div.innerHTML = `<dt></dt><dd></dd>`;
    div.firstChild.textContent = dt;
    div.lastChild.textContent = dd;
    if (extra) { const s = document.createElement("small"); s.textContent = " " + extra; div.lastChild.append(s); }
    cifras.append(div);
  };
  agregar("Lluvia máx. en un día", `${num(e.maxDia)} mm`, `(${diaCorto(e.diaMax)})`);
  agregar("Probabilidad ese día", `${e.probDia ?? "—"} %`);
  agregar("Hora más intensa", `${num(e.maxHora)} mm`, e.maxHora > 0 ? hora(new Date(e.horaMax + ":00-05:00")) : "");
  agregar("Umbral de atención", `${num(u.diaria_mm.p90)} mm/día`);
  const r = estado.evalRio;
  if (r) agregar("Río Chili (7 días)", NIVELES[r.nivel].corto, d.rio ? "· tu distrito limita con el río" : "· referencia");

  const lista = $("#acciones");
  lista.innerHTML = "";
  const acciones = [...ACCIONES[e.nivel]];
  if (d.rio && r?.nivel >= 1) acciones.unshift("El caudal del río Chili está por encima de lo habitual: no te acerques a la orilla ni al cauce.");
  for (const a of acciones) { const li = document.createElement("li"); li.textContent = a; lista.append(li); }
}

function renderNino() {
  const s = estado.indicadores?.semanal;
  const o = estado.indicadores?.oni;
  if (!s) { $("#nino-clase").textContent = "Índices no disponibles por ahora."; return; }
  const a = s.ultima.nino12_anom;
  $("#nino-anom").textContent = (a > 0 ? "+" : "") + num(a);
  $("#nino-clase").textContent = `Condición aproximada: ${s.icen_categoria} (ICEN aprox. ${a > 0 ? "+" : ""}${num(s.icen_aprox, 2)} °C)`;
  const serie = s.serie.slice(-26);
  barras($("#g-nino"), {
    etiquetas: serie.map((x) => diaMes(x.semana)),
    valores: serie.map((x) => x.nino12_anom),
    ticks: [0, 13, 25].filter((i) => i < serie.length),
    colores: (v) => (v >= 0 ? "#e34948" : "#2a78d6"),
    tooltip: (i) => `<strong>Semana del ${diaMes(serie[i].semana)}</strong>${num(serie[i].nino12)} °C · ${serie[i].nino12_anom > 0 ? "+" : ""}${num(serie[i].nino12_anom)} °C`,
  });
  const oniTxt = o ? ` ONI (Pacífico central) ${o.ultimo.trimestre} ${o.ultimo.anio}: ${o.ultimo.anomalia > 0 ? "+" : ""}${num(o.ultimo.anomalia, 2)} °C → ${o.clasificacion.fase} ${o.clasificacion.intensidad}.` : "";
  $("#nino-nota").textContent = `Semana del ${diaMes(s.ultima.semana)}, NOAA.${oniTxt} El estado oficial en el Perú lo comunica ENFEN.`;
}

function renderRio() {
  const r = estado.evalRio;
  const d = estado.rio?.daily;
  if (!r || !d) { $("#rio-clase").textContent = "Caudal no disponible por ahora."; return; }
  $("#rio-valor").textContent = num(r.hoy);
  $("#rio-clase").replaceChildren(insignia(r.nivel, true), document.createTextNode(` Máx. próximos 7 días: ${num(r.maxProx)} m³/s`));
  const n = d.time.length;
  const banda = d.time.map((_, i) => (i >= r.iHoy && d.river_discharge_min[i] != null ? [d.river_discharge_min[i], d.river_discharge_max[i]] : null));
  linea($("#g-rio"), {
    etiquetas: d.time.map(diaMes),
    valores: d.river_discharge.map((x) => x ?? 0),
    banda, corte: r.iHoy,
    ticks: [0, Math.round(r.iHoy / 2), r.iHoy, n - 1],
    umbrales: [
      { valor: r.u.p90, nivel: 1, texto: `alto ${num(r.u.p90)}` },
      { valor: r.u.p99, nivel: 3, texto: `muy alto ${num(r.u.p99)}` },
    ],
    tooltip: (i) => `<strong>${diaMes(d.time[i])}${i > r.iHoy ? " · pronóstico" : ""}</strong>${num(d.river_discharge[i])} m³/s` +
      (banda[i] && i > r.iHoy ? `<br>rango ${num(banda[i][0])}–${num(banda[i][1])}` : ""),
  });
  $("#rio-nota").textContent = `Modelo GloFAS (Copernicus), no es una medición de estación. "Alto" = por encima del 90 % de los días de 1991–2020 (mediana ${num(r.u.p50)} m³/s). La banda muestra el rango de los escenarios.`;
}

function renderHoras() {
  const i = DISTRITOS.findIndex((x) => x.id === estado.distrito);
  const f = estado.pronostico?.[i];
  const e = estado.niveles[estado.distrito];
  if (!f || !e) return;
  const u = estado.indicadores.umbrales_lluvia.horaria_mm;
  const tiempos = f.hourly.time.slice(e.j0, e.j0 + 48);
  const valores = f.hourly.precipitation.slice(e.j0, e.j0 + 48).map((x) => x ?? 0);
  const probs = f.hourly.precipitation_probability.slice(e.j0, e.j0 + 48);
  $("#lluvia-sub").textContent = `${DISTRITOS[i].nombre}, mm por hora`;
  $("#lluvia-total").textContent = num(e.total48);
  $("#lluvia-clase").replaceChildren(insignia(e.nHora, true), document.createTextNode(` Hora más intensa: ${num(e.maxHora)} mm`));
  const etiqueta = (t) => `${new Date(t.slice(0, 10) + "T12:00:00Z").toLocaleDateString("es-PE", { timeZone: "UTC", weekday: "short" }).replace(".", "")} ${t.slice(11, 13)} h`;
  barras($("#g-horas"), {
    etiquetas: tiempos.map(etiqueta), valores,
    ticks: [0, 12, 24, 36].filter((k) => k < tiempos.length),
    umbrales: [{ valor: u.p95, nivel: 1, texto: `atención ${num(u.p95)}` }, { valor: u.p99, nivel: 2, texto: `alto ${num(u.p99)}` }],
    tooltip: (k) => `<strong>${etiqueta(tiempos[k])} h</strong>${num(valores[k])} mm · prob. ${probs[k] ?? "—"} %`,
  });
  $("#lluvia-nota").textContent = "Pronóstico hora a hora. Las lluvias cortas e intensas son las que activan las torrenteras.";
}

function renderSemana() {
  const i = DISTRITOS.findIndex((x) => x.id === estado.distrito);
  const f = estado.pronostico?.[i];
  if (!f) return;
  const u = estado.indicadores.umbrales_lluvia.diaria_mm;
  const dd = f.daily;
  $("#semana-distrito").textContent = DISTRITOS[i].nombre;
  barras($("#g-semana"), {
    etiquetas: dd.time.map(diaCorto),
    valores: dd.precipitation_sum.map((x) => x ?? 0),
    umbrales: [
      { valor: u.p90, nivel: 1, texto: `atención ${num(u.p90)} mm` },
      { valor: u.p95, nivel: 2, texto: `alto ${num(u.p95)} mm` },
      { valor: u.p99, nivel: 3, texto: `muy alto ${num(u.p99)} mm` },
    ],
    tooltip: (k) => `<strong>${diaCorto(dd.time[k])}</strong>${num(dd.precipitation_sum[k])} mm · prob. ${dd.precipitation_probability_max[k] ?? "—"} %<br>${cielo(dd.weather_code[k])}`,
  });
  const tbody = $("#tabla-semana tbody");
  tbody.innerHTML = "";
  dd.time.forEach((t, k) => {
    const tr = document.createElement("tr");
    const celdas = [diaCorto(t), cielo(dd.weather_code[k]), `${num(dd.precipitation_sum[k])} mm`, `${dd.precipitation_probability_max[k] ?? "—"} %`, `${num(dd.temperature_2m_min[k], 0)}–${num(dd.temperature_2m_max[k], 0)} °C`];
    celdas.forEach((c, j) => { const td = document.createElement("td"); td.textContent = c; if (j >= 2) td.className = "num"; tr.append(td); });
    const td = document.createElement("td"); td.append(insignia(nivelPorUmbral(dd.precipitation_sum[k] ?? 0, u), true)); tr.append(td);
    tbody.append(tr);
  });
}

function renderTablaDistritos() {
  const tbody = $("#tabla-distritos tbody");
  tbody.innerHTML = "";
  const orden = DISTRITOS.map((d) => ({ d, e: estado.niveles[d.id] }))
    .sort((a, b) => (b.e?.nivel ?? -1) - (a.e?.nivel ?? -1) || (b.e?.maxDia ?? 0) - (a.e?.maxDia ?? 0));
  for (const { d, e } of orden) {
    const tr = document.createElement("tr");
    if (d.id === estado.distrito) tr.setAttribute("aria-current", "true");
    const td0 = document.createElement("td");
    const b = document.createElement("button");
    b.type = "button"; b.textContent = d.nombre;
    b.addEventListener("click", () => seleccionar(d.id, true));
    td0.append(b);
    const td1 = document.createElement("td"); td1.append(insignia(e?.nivel ?? null, true));
    const td2 = document.createElement("td"); td2.className = "num"; td2.textContent = e ? `${num(e.maxDia)} mm` : "—";
    const td3 = document.createElement("td"); td3.className = "num"; td3.textContent = e ? `${e.probDia ?? "—"} %` : "—";
    tr.append(td0, td1, td2, td3);
    tr.addEventListener("click", (ev) => { if (ev.target === tr || ev.target.tagName === "TD") seleccionar(d.id, true); });
    tbody.append(tr);
  }
}

function renderLeyenda() {
  const c = $("#leyenda");
  c.innerHTML = "";
  NIVELES.forEach((n) => c.append(insignia(n.n, true)));
}

function iniciarMapa() {
  if (!window.L) {
    $("#mapa").classList.add("mapa--error");
    $("#mapa").textContent = "No se pudo cargar el mapa. Usa la tabla con la misma información.";
    return;
  }
  const mapa = L.map("mapa", { scrollWheelZoom: false, zoomControl: true }).setView([-16.41, -71.54], 12);
  L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 18, attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
  }).addTo(mapa);
  estado.mapa = mapa;
  DISTRITOS.forEach((d) => {
    const icono = L.divIcon({ className: "", html: `<div class="marcador" data-id="${d.id}">${d.abrev}</div>`, iconSize: [30, 30], iconAnchor: [15, 15] });
    const m = L.marker([d.lat, d.lon], { icon: icono, keyboard: true, title: d.nombre, alt: d.nombre }).addTo(mapa);
    m.on("click", () => seleccionar(d.id, false));
    estado.marcadores[d.id] = m;
  });
  const rio = L.divIcon({ className: "", html: `<div class="marcador marcador--rio" title="Punto de caudal del río Chili">≈</div>`, iconSize: [26, 26], iconAnchor: [13, 13] });
  // Solo un símbolo sobre el cauce (zona de Chilina) para ubicar el río.
  L.marker([-16.374, -71.531], { icon: rio, title: "Río Chili" })
    .bindTooltip("Río Chili · el caudal se calcula para el tramo urbano").addTo(mapa);
}

function renderMapa() {
  if (!estado.mapa) return;
  DISTRITOS.forEach((d) => {
    const e = estado.niveles[d.id];
    const m = estado.marcadores[d.id];
    const div = m.getElement()?.querySelector(".marcador");
    if (div) {
      div.dataset.nivel = e?.nivel ?? "";
      div.classList.toggle("marcador--activo", d.id === estado.distrito);
    }
    m.setZIndexOffset(d.id === estado.distrito ? 1000 : 0);
    const texto = e ? `${d.nombre}: ${NIVELES[e.nivel].nombre} · máx. ${num(e.maxDia)} mm/día` : `${d.nombre}: sin datos`;
    m.unbindTooltip().bindTooltip(texto, { direction: "top", offset: [0, -14] });
  });
}

function renderMetodo() {
  const i = estado.indicadores;
  const u = i?.umbrales_lluvia, r = i?.umbrales_rio;
  const caja = $("#metodo");
  caja.innerHTML = `
    <h3>Niveles de lluvia</h3>
    <p>Se compara la lluvia diaria prevista para los próximos 3 días con lo que ha llovido en Arequipa según el mismo modelo de pronóstico
    (${u ? `${u.periodo}, ${u.dias_con_lluvia} días con lluvia` : "periodo reciente"}). <strong>Atención</strong>: más que el 90 % de los días lluviosos
    (${u ? num(u.diaria_mm.p90) : "—"} mm); <strong>Alto</strong>: más que el 95 % (${u ? num(u.diaria_mm.p95) : "—"} mm);
    <strong>Muy alto</strong>: más que el 99 % (${u ? num(u.diaria_mm.p99) : "—"} mm). También se revisa la hora más intensa de las próximas 48 h
    (${u ? `${num(u.horaria_mm.p95)} y ${num(u.horaria_mm.p99)} mm/h` : "—"}). Es el mismo criterio de percentiles que usa la climatología, pero
    <em>no es un aviso de SENAMHI</em>.</p>
    <h3>Río Chili</h3>
    <p>Caudal diario modelado por GloFAS. "Alto" si en los próximos 7 días supera el percentil 90 de 1991–2020 (${r ? num(r.p90) : "—"} m³/s) y
    "Muy alto" si supera el 99 % (${r ? num(r.p99) : "—"} m³/s). Solo suma al nivel de los distritos que limitan con el río.</p>
    <h3>El Niño</h3>
    <p>Se muestra aparte y no cambia los niveles locales: El Niño cambia las probabilidades, pero no cada lluvia se debe a él.
    El ICEN aproximado es el promedio de las últimas 13 semanas de anomalía en Niño 1+2 (NOAA OISST). El ICEN oficial y el estado del sistema
    de alerta los publica ENFEN.</p>
    <h3>Actualización</h3>
    <p>El pronóstico y el caudal se consultan al abrir la página y cada 15 minutos. Los índices oceánicos se regeneran automáticamente cada 3 horas
    (última: ${i ? fechaLarga(new Date(i.generado)) : "—"}). Si pasan 30 minutos sin una consulta exitosa se indica
    «Actualización no verificada».</p>`;
}

async function renderAvisos() {
  const caja = $("#avisos");
  let lista = [];
  try { lista = await pedirJSON("data/avisos.json"); } catch { /* sin avisos */ }
  const ahora = Date.now();
  const validos = (Array.isArray(lista) ? lista : []).filter((a) =>
    a && typeof a.titulo === "string" && /^https:\/\//.test(a.fuente_url ?? "") &&
    !Number.isNaN(Date.parse(a.emitido)) && Date.parse(a.vence) > ahora);
  caja.innerHTML = "";
  if (!validos.length) {
    const p = document.createElement("p");
    p.className = "vacio";
    p.textContent = "No hay avisos oficiales cargados manualmente en este momento. Esto no significa ausencia de riesgo: revisa las fuentes oficiales.";
    caja.append(p);
    return;
  }
  for (const a of validos) {
    const art = document.createElement("article");
    art.className = "aviso";
    const h = document.createElement("h3"); h.textContent = a.titulo;
    const meta = document.createElement("p"); meta.className = "aviso__meta";
    meta.textContent = `${a.emisor ?? "Fuente oficial"} · ${a.nivel_original ?? ""} · ${(a.distritos ?? []).join(", ") || "Arequipa"} · vigente hasta ${fechaLarga(new Date(a.vence))}`;
    const msg = document.createElement("p"); msg.textContent = a.mensaje ?? "";
    const link = document.createElement("a"); link.href = a.fuente_url; link.target = "_blank"; link.rel = "noopener"; link.textContent = "Ver documento original";
    art.append(h, meta, msg, link);
    caja.append(art);
  }
}

function renderEstadoConexion() {
  const caja = $("#estado-conexion");
  const t = $("#estado-texto");
  if (!estado.ultimoExito) { caja.dataset.estado = "error"; t.textContent = "Sin datos en vivo"; return; }
  const edad = Date.now() - estado.ultimoExito;
  if (DEMO) {
    caja.dataset.estado = "degradado";
    t.textContent = "Modo demostración · lluvia simulada";
  } else if (edad > DEGRADADO_MS) {
    caja.dataset.estado = "degradado";
    t.textContent = `Actualización no verificada · último dato ${hora(new Date(estado.ultimoExito))}`;
  } else {
    caja.dataset.estado = "vivo";
    t.textContent = `En vivo · actualizado ${hora(new Date(estado.ultimoExito))}`;
  }
}

function renderTodo() {
  evaluarTodo();
  renderTarjeta();
  renderNino();
  renderRio();
  renderHoras();
  renderSemana();
  renderTablaDistritos();
  renderMapa();
  renderMetodo();
  renderEstadoConexion();
  $("#pie-generado").textContent = estado.indicadores ? `Índices generados: ${fechaLarga(new Date(estado.indicadores.generado))}.` : "";
}

function seleccionar(id, centrar) {
  estado.distrito = id;
  guardar("distrito", id);
  $("#distrito").value = id;
  renderTodo();
  if (centrar && estado.mapa) {
    const d = DISTRITOS.find((x) => x.id === id);
    estado.mapa.panTo([d.lat, d.lon]);
  }
}

// ---------------------------------------------------- avisos al usuario

function toast(texto) {
  const t = $("#toast");
  t.textContent = texto;
  t.hidden = false;
  clearTimeout(toast.t);
  toast.t = setTimeout(() => { t.hidden = true; }, 9000);
}

async function notificar(titulo, cuerpo) {
  if (!leer("opt:notificar", false) || !("Notification" in window) || Notification.permission !== "granted") return;
  const opciones = { body: cuerpo, icon: "img/icono.svg", tag: "alertanino", renotify: true };
  try {
    const reg = await navigator.serviceWorker?.getRegistration();
    if (reg) await reg.showNotification(titulo, opciones);
    else new Notification(titulo, opciones);
  } catch { /* el navegador bloqueó la notificación */ }
}

function revisarCambios() {
  const d = DISTRITOS.find((x) => x.id === estado.distrito);
  const e = estado.niveles[d.id];
  if (!e) return;
  const clave = `${PREFIJO}nivelPrevio:${d.id}`;
  const previo = leer(clave, DEMO ? 0 : null);
  guardar(clave, e.nivel);
  if (previo == null || e.nivel <= previo) return;
  const texto = `${DEMO ? "[DEMOSTRACIÓN] " : ""}${d.nombre}: el nivel subió a «${NIVELES[e.nivel].nombre}». Lluvia máx. ${num(e.maxDia)} mm el ${diaCorto(e.diaMax)}.`;
  toast(texto);
  notificar("AlertaNiño AQP", texto);
  if (leer("opt:sonido", false)) sonar(leer("opt:tono", "campana"));
}

// ---------------------------------------------------------------- sonido

let audio = null;
function sonar(tono) {
  detener();
  const Ctx = window.AudioContext || window.webkitAudioContext;
  if (!Ctx) return;
  audio = new Ctx();
  const t0 = audio.currentTime + 0.05;
  const volumen = audio.createGain();
  volumen.gain.value = 0.18;
  volumen.connect(audio.destination);
  const nota = (frec, ini, dur, tipo = "sine", hasta) => {
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
  if (tono === "campana") [0, 0.9, 1.8].forEach((s) => { nota(880, t0 + s, 0.8); nota(1320, t0 + s, 0.6); });
  if (tono === "pitido") for (let k = 0; k < 6; k++) nota(1000, t0 + k * 0.35, 0.18, "square");
  if (tono === "sirena") for (let k = 0; k < 3; k++) { nota(600, t0 + k, 0.5, "sawtooth", 1100); nota(1100, t0 + k + 0.5, 0.5, "sawtooth", 600); }
  setTimeout(detener, 3500);
}
function detener() { if (audio) { audio.close().catch(() => {}); audio = null; } }

// ------------------------------------------------------------ controles

function iniciarControles() {
  const sel = $("#distrito");
  for (const d of DISTRITOS) {
    const o = document.createElement("option");
    o.value = d.id; o.textContent = d.nombre + (d.piloto ? " · piloto" : "");
    sel.append(o);
  }
  sel.value = estado.distrito;
  sel.addEventListener("change", () => seleccionar(sel.value, true));

  // Tamaño de texto (recomendación E3 del informe: adultos mayores)
  let escala = leer("escala", 1);
  const aplicarEscala = () => document.documentElement.style.setProperty("--escala", escala);
  aplicarEscala();
  $("#texto-mas").addEventListener("click", () => { escala = Math.min(1.5, +(escala + 0.1).toFixed(2)); guardar("escala", escala); aplicarEscala(); });
  $("#texto-menos").addEventListener("click", () => { escala = Math.max(0.9, +(escala - 0.1).toFixed(2)); guardar("escala", escala); aplicarEscala(); });

  // Tema claro/oscuro
  const tema = leer("tema", null);
  if (tema) document.documentElement.dataset.theme = tema;
  $("#tema").addEventListener("click", () => {
    const oscuroAhora = document.documentElement.dataset.theme
      ? document.documentElement.dataset.theme === "dark"
      : matchMedia("(prefers-color-scheme: dark)").matches;
    const nuevo = oscuroAhora ? "light" : "dark";
    document.documentElement.dataset.theme = nuevo;
    guardar("tema", nuevo);
    if (estado.pronostico) renderTodo();
  });

  $("#ver-tabla-semana").addEventListener("click", (ev) => {
    const t = $("#tabla-semana");
    t.hidden = !t.hidden;
    ev.currentTarget.setAttribute("aria-expanded", String(!t.hidden));
    ev.currentTarget.textContent = t.hidden ? "Ver como tabla" : "Ocultar tabla";
  });

  // Notificaciones (requieren permiso explícito)
  const optN = $("#opt-notificar");
  optN.checked = leer("opt:notificar", false) && "Notification" in window && Notification.permission === "granted";
  optN.addEventListener("change", async () => {
    if (optN.checked) {
      if (!("Notification" in window)) { optN.checked = false; $("#notif-estado").textContent = "Este navegador no permite notificaciones; los avisos se mostrarán en la página."; return; }
      const permiso = await Notification.requestPermission();
      if (permiso !== "granted") { optN.checked = false; $("#notif-estado").textContent = "Permiso denegado. Puedes activarlo desde la configuración del navegador."; return; }
      notificar("AlertaNiño AQP", "Notificaciones activadas. Te avisaremos si sube el nivel de tu distrito.");
    }
    guardar("opt:notificar", optN.checked);
  });

  const optS = $("#opt-sonido");
  optS.checked = leer("opt:sonido", false);
  optS.addEventListener("change", () => guardar("opt:sonido", optS.checked));
  const tono = $("#tono");
  tono.value = leer("opt:tono", "campana");
  tono.addEventListener("change", () => guardar("opt:tono", tono.value));
  $("#probar-sonido").addEventListener("click", () => sonar(tono.value));
  $("#detener-sonido").addEventListener("click", detener);
}

// ---------------------------------------------------------------- ciclo

async function actualizar() {
  const exito = await cargarEnVivo();
  if (!estado.pronostico || !estado.indicadores) {
    renderEstadoConexion();
    renderTarjeta();
    return;
  }
  renderTodo();
  if (exito) revisarCambios();
  else toast("No se pudo actualizar. Se muestran los últimos datos guardados.");
}

async function iniciar() {
  if (DEMO) {
    $("#aviso-demo").hidden = false;
    // En demostración siempre se vuelve a evaluar desde cero.
    Object.keys(localStorage).filter((k) => k.startsWith("demo:")).forEach((k) => localStorage.removeItem(k));
  }
  iniciarControles();
  renderLeyenda();
  iniciarMapa();
  await cargarIndicadores();
  if (!estado.indicadores) {
    $("#estado-titulo").textContent = "No se pudieron cargar los umbrales";
  }
  estado.pronostico = leer("cache:pronostico");
  estado.rio = leer("cache:rio");
  await actualizar();
  renderAvisos();
  setInterval(actualizar, CADA_MS);
  setInterval(renderEstadoConexion, 60 * 1000);
  document.addEventListener("visibilitychange", () => {
    if (!document.hidden && Date.now() - estado.ultimoExito > 5 * 60 * 1000) actualizar();
  });
  if ("serviceWorker" in navigator) navigator.serviceWorker.register("sw.js").catch(() => {});
}

iniciar();
