// AlertaNiño AQP — lógica principal.
// Todo es dato real. Fuentes oficiales: SENAMHI y ENFEN (leídas cada 30 min por
// GitHub Actions y publicadas en data/indicadores.json) e INDECI-SINPAD
// (consultado en vivo desde el navegador). Referencia: pronóstico de Open-Meteo
// y caudal GloFAS del río Chili, consultados en vivo cada 15 minutos.

import { DISTRITOS, RIO_CHILI, NIVELES, NIVELES_OFICIALES, ACCIONES_OFICIALES, WMS_SENAMHI, cielo } from "./datos.js";
import { barras, linea } from "./graficos.js";

const CADA_MS = 15 * 60 * 1000;            // consulta cada 15 min
const DEGRADADO_MS = 30 * 60 * 1000;       // pronóstico sin éxito 30 min -> no verificado
const OFICIAL_VIEJO_MS = 2 * 60 * 60 * 1000; // datos oficiales con más de 2 h -> no verificados
const TZ = "America/Lima";
const SINPAD = "https://geosinpad.indeci.gob.pe/indeci/rest/services/Emergencias/EMERGENCIAS_SINPAD/MapServer/0/query";
const $ = (s) => document.querySelector(s);

// ------------------------------------------------------------ utilidades

const guardar = (k, v) => { try { localStorage.setItem(k, JSON.stringify(v)); } catch { /* sin almacenamiento */ } };
const leer = (k, def = null) => { try { const v = localStorage.getItem(k); return v == null ? def : JSON.parse(v); } catch { return def; } };
// Limpia restos de la versión con modo demostración.
try { Object.keys(localStorage).filter((k) => k.startsWith("demo:")).forEach((k) => localStorage.removeItem(k)); } catch { /* nada */ }

const num = (v, d = 1) => (v == null || Number.isNaN(v) ? "—" : v.toLocaleString("es-PE", { minimumFractionDigits: d, maximumFractionDigits: d }));
const hora = (fecha) => new Intl.DateTimeFormat("es-PE", { timeZone: TZ, hour: "2-digit", minute: "2-digit" }).format(fecha);
const fechaLarga = (fecha) => new Intl.DateTimeFormat("es-PE", { timeZone: TZ, weekday: "long", day: "numeric", month: "long", hour: "2-digit", minute: "2-digit" }).format(fecha);
const fechaCorta = (iso) => new Intl.DateTimeFormat("es-PE", { timeZone: "UTC", day: "numeric", month: "long", year: "numeric" }).format(new Date(iso.slice(0, 10) + "T12:00:00Z"));
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

function nodo(tag, clase, texto) {
  const n = document.createElement(tag);
  if (clase) n.className = clase;
  if (texto != null) n.textContent = texto;
  return n;
}

function enlace(url, texto) {
  const a = nodo("a", "", texto);
  a.href = url; a.target = "_blank"; a.rel = "noopener";
  return a;
}

// Insignia del nivel de referencia (modelo).
function insignia(nivel, chica = false) {
  const info = NIVELES[nivel] ?? { nombre: "Sin datos", corto: "Sin datos", icono: "?" };
  const s = nodo("span", "insignia" + (chica ? " insignia--chica" : ""));
  s.dataset.nivel = nivel ?? "";
  s.append(nodo("span", "insignia__icono", info.icono), nodo("span", "insignia__texto", chica ? info.corto : info.nombre));
  s.firstChild.setAttribute("aria-hidden", "true");
  return s;
}

// Insignia del nivel oficial de SENAMHI.
function insigniaOficial(n, chica = false, sinDato = false) {
  const info = sinDato ? { nombre: "Sin dato", icono: "?", color: "" } : NIVELES_OFICIALES[n ?? 0];
  const s = nodo("span", "insignia" + (chica ? " insignia--chica" : ""));
  s.dataset.nivel = info.color;
  s.append(nodo("span", "insignia__icono", info.icono), nodo("span", "insignia__texto", info.nombre));
  s.firstChild.setAttribute("aria-hidden", "true");
  return s;
}

// --------------------------------------------------------------- estado

const estado = {
  distrito: leer("distrito", "cercado"),
  indicadores: null,
  pronostico: null,
  rio: null,
  ultimoExito: leer("ultimoExito", 0),
  niveles: {},
  mapa: null,
  marcadores: {},
};
if (!DISTRITOS.some((d) => d.id === estado.distrito)) estado.distrito = "cercado";

const oficialDe = (id) => estado.indicadores?.senamhi?.por_distrito?.[id] ?? null;
const nivelOficial = (id) => oficialDe(id)?.lluvia_24h?.nivel ?? 0;
const hayOficial = () => !!estado.indicadores?.senamhi?.por_distrito;

// --------------------------------------------------------- carga de datos

async function cargarIndicadores() {
  const marca = Math.floor(Date.now() / 60000); // evita copias en caché de más de 1 min
  try {
    estado.indicadores = await pedirJSON(`data/indicadores.json?m=${marca}`);
    guardar("cache:indicadores", estado.indicadores);
  } catch {
    estado.indicadores ??= leer("cache:indicadores");
  }
}

function urlPronostico() {
  return "https://api.open-meteo.com/v1/forecast?" + new URLSearchParams({
    latitude: DISTRITOS.map((d) => d.lat).join(","), longitude: DISTRITOS.map((d) => d.lon).join(","),
    timezone: TZ, forecast_days: "7",
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
  const [p, r] = await Promise.allSettled([pedirJSON(urlPronostico()), pedirJSON(urlRio()), cargarIndicadores()]);
  let exito = false;
  if (p.status === "fulfilled" && Array.isArray(p.value)) {
    estado.pronostico = p.value; guardar("cache:pronostico", p.value); exito = true;
  } else estado.pronostico ??= leer("cache:pronostico");
  if (r.status === "fulfilled" && r.value?.daily) {
    estado.rio = r.value; guardar("cache:rio", r.value);
  } else estado.rio ??= leer("cache:rio");
  if (exito) { estado.ultimoExito = Date.now(); guardar("ultimoExito", estado.ultimoExito); }
  return exito;
}

// ------------------------------------------------------------ evaluación (referencia)

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
    nivel: Math.max(nDia, nHora), nHora,
    maxDia, diaMax: f.daily.time[iMax], probDia: f.daily.precipitation_probability_max[iMax],
    maxHora, horaMax: f.hourly.time[j0 + horas.indexOf(maxHora)], total48, j0, i0,
  };
}

function evaluarTodo() {
  estado.evalRio = evaluarRio();
  DISTRITOS.forEach((d, i) => { estado.niveles[d.id] = evaluarDistrito(i); });
}

// ------------------------------------------------------------- render

function renderTarjeta() {
  const d = DISTRITOS.find((x) => x.id === estado.distrito);
  const idx = DISTRITOS.indexOf(d);
  const of = oficialDe(d.id);
  const aviso = of?.lluvia_24h;
  const n = aviso?.nivel ?? 0;
  const sen = estado.indicadores?.senamhi;
  const tarjeta = $("#tarjeta-estado");

  if (!hayOficial()) {
    tarjeta.dataset.nivel = "cargando";
    $("#insignia").replaceWith(Object.assign(insigniaOficial(null, false, true), { id: "insignia" }));
    $("#estado-titulo").textContent = "No se pudo leer el aviso oficial de SENAMHI";
    $("#estado-detalle").textContent = "Revisa tu conexión o consulta directamente a SENAMHI. La falta de datos no significa que no haya riesgo.";
  } else {
    tarjeta.dataset.nivel = NIVELES_OFICIALES[n].color || "cargando";
    $("#insignia").replaceWith(Object.assign(insigniaOficial(n), { id: "insignia" }));
    $("#estado-titulo").textContent = aviso
      ? `Aviso de SENAMHI por lluvias: ${aviso.texto} en ${d.nombre}`
      : `SENAMHI no incluye a ${d.nombre} en el aviso por lluvias de hoy`;
    const det = $("#estado-detalle");
    det.replaceChildren();
    if (aviso?.descripcion) det.append(nodo("span", "texto-oficial", `«${aviso.descripcion}»`), nodo("br"));
    const actual = estado.pronostico?.[idx]?.current;
    const extra = `Vigencia: ${aviso?.fecha ? fechaCorta(aviso.fecha) : "hoy"} · SENAMHI consultado a las ${hora(new Date(sen.consultado))}` +
      (actual ? ` · Ahora: ${cielo(actual.weather_code)}, ${num(actual.temperature_2m)} °C` : "");
    det.append(nodo("small", "fuente-linea", extra));
  }

  const cifras = $("#cifras");
  cifras.innerHTML = "";
  const agregar = (dt, dd, extra) => {
    const div = document.createElement("div");
    div.append(nodo("dt", "", dt), nodo("dd", "", dd));
    if (extra) div.lastChild.append(nodo("small", "", " " + extra));
    cifras.append(div);
  };
  const otros = of?.avisos ?? [];
  agregar("Avisos meteorológicos vigentes", String(otros.length), otros.length ? `N.º ${otros.map((a) => a.numero).join(", ")}` : "SENAMHI");
  const enf = estado.indicadores?.enfen;
  agregar("ENFEN", enf?.estado ?? "—", enf ? `(${enf.numero})` : "");
  const e = estado.niveles[d.id];
  if (e) agregar("Lluvia máx. prevista (modelo)", `${num(e.maxDia)} mm`, `${diaCorto(e.diaMax)} · ${e.probDia ?? "—"} %`);
  const r = estado.evalRio;
  if (r) agregar("Río Chili (modelo, 7 días)", NIVELES[r.nivel].corto, d.rio ? "· limita con tu distrito" : "");

  const lista = $("#acciones");
  lista.innerHTML = "";
  const acciones = [];
  if (aviso?.recomendacion) acciones.push(`SENAMHI recomienda: «${aviso.recomendacion}»`);
  acciones.push(...ACCIONES_OFICIALES[hayOficial() ? n : 0]);
  if (d.rio && r?.nivel >= 1) acciones.push("El caudal previsto del río Chili está por encima de lo habitual: no te acerques a la orilla.");
  for (const a of acciones) lista.append(nodo("li", "", a));
}

function renderNino() {
  const s = estado.indicadores?.semanal;
  const o = estado.indicadores?.oni;
  const enf = estado.indicadores?.enfen;
  const caja = $("#enfen-estado");
  caja.replaceChildren();
  if (enf) {
    caja.append(document.createTextNode(`ENFEN: ${enf.estado}`), nodo("small", "", `Comunicado oficial N.º ${enf.numero}, ${fechaCorta(enf.fecha)}`));
  } else caja.textContent = "Estado oficial de ENFEN no disponible por ahora.";
  if (!s) { $("#nino-clase").textContent = "Índices no disponibles por ahora."; return; }
  const a = s.ultima.nino12_anom;
  $("#nino-anom").textContent = (a > 0 ? "+" : "") + num(a);
  $("#nino-clase").textContent = `Promedio de 13 semanas: ${a > 0 ? "+" : ""}${num(s.icen_aprox, 2)} °C (${s.icen_categoria.toLowerCase()} según la escala del ICEN)`;
  const serie = s.serie.slice(-26);
  barras($("#g-nino"), {
    etiquetas: serie.map((x) => diaMes(x.semana)),
    valores: serie.map((x) => x.nino12_anom),
    ticks: [0, 13, 25].filter((i) => i < serie.length),
    colores: (v) => (v >= 0 ? "#e34948" : "#2a78d6"),
    tooltip: (i) => `<strong>Semana del ${diaMes(serie[i].semana)}</strong>${num(serie[i].nino12)} °C · ${serie[i].nino12_anom > 0 ? "+" : ""}${num(serie[i].nino12_anom)} °C`,
  });
  const oniTxt = o ? ` ONI ${o.ultimo.trimestre} ${o.ultimo.anio}: ${o.ultimo.anomalia > 0 ? "+" : ""}${num(o.ultimo.anomalia, 2)} °C.` : "";
  $("#nino-nota").textContent = `Datos semanales de NOAA, semana del ${diaMes(s.ultima.semana)}.${oniTxt}`;
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
  $("#rio-nota").textContent = `Modelo GloFAS (Copernicus), dato de referencia: no es una medición de estación ni un aviso oficial. "Alto" = por encima del 90 % de los días de 1991–2020 (mediana ${num(r.u.p50)} m³/s).`;
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
  $("#lluvia-sub").textContent = `${DISTRITOS[i].nombre}, mm por hora (pronóstico Open-Meteo)`;
  $("#lluvia-total").textContent = num(e.total48);
  $("#lluvia-clase").replaceChildren(insignia(e.nHora, true), document.createTextNode(` Hora más intensa: ${num(e.maxHora)} mm`));
  const etiqueta = (t) => `${new Date(t.slice(0, 10) + "T12:00:00Z").toLocaleDateString("es-PE", { timeZone: "UTC", weekday: "short" }).replace(".", "")} ${t.slice(11, 13)} h`;
  barras($("#g-horas"), {
    etiquetas: tiempos.map(etiqueta), valores,
    ticks: [0, 12, 24, 36].filter((k) => k < tiempos.length),
    umbrales: [{ valor: u.p95, nivel: 1, texto: `atención ${num(u.p95)}` }, { valor: u.p99, nivel: 2, texto: `alto ${num(u.p99)}` }],
    tooltip: (k) => `<strong>${etiqueta(tiempos[k])}</strong>${num(valores[k])} mm · prob. ${probs[k] ?? "—"} %`,
  });
  $("#lluvia-nota").textContent = "Pronóstico numérico hora a hora (referencia). Las lluvias cortas e intensas son las que activan las torrenteras.";
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
    [diaCorto(t), cielo(dd.weather_code[k]), `${num(dd.precipitation_sum[k])} mm`, `${dd.precipitation_probability_max[k] ?? "—"} %`, `${num(dd.temperature_2m_min[k], 0)}–${num(dd.temperature_2m_max[k], 0)} °C`]
      .forEach((c, j) => tr.append(nodo("td", j >= 2 ? "num" : "", c)));
    const td = nodo("td"); td.append(insignia(nivelPorUmbral(dd.precipitation_sum[k] ?? 0, u), true)); tr.append(td);
    tbody.append(tr);
  });
}

function renderTablaDistritos() {
  const tbody = $("#tabla-distritos tbody");
  tbody.innerHTML = "";
  const orden = DISTRITOS.map((d) => ({ d, n: nivelOficial(d.id), e: estado.niveles[d.id] }))
    .sort((a, b) => b.n - a.n || (b.e?.nivel ?? -1) - (a.e?.nivel ?? -1) || (b.e?.maxDia ?? 0) - (a.e?.maxDia ?? 0));
  for (const { d, n, e } of orden) {
    const tr = document.createElement("tr");
    if (d.id === estado.distrito) tr.setAttribute("aria-current", "true");
    const b = nodo("button", "", d.nombre);
    b.type = "button";
    b.addEventListener("click", () => seleccionar(d.id, true));
    const td0 = nodo("td"); td0.append(b);
    const td1 = nodo("td"); td1.append(insigniaOficial(n, true, !hayOficial()));
    const td2 = nodo("td"); td2.append(insignia(e?.nivel ?? null, true));
    const td3 = nodo("td", "num", e ? `${num(e.maxDia)} mm` : "—");
    tr.append(td0, td1, td2, td3);
    tr.addEventListener("click", (ev) => { if (ev.target.tagName === "TD") seleccionar(d.id, true); });
    tbody.append(tr);
  }
}

function renderLeyenda() {
  const c = $("#leyenda");
  c.innerHTML = "";
  c.append(nodo("span", "nota", "SENAMHI:"));
  [0, 1, 2, 3, 4].forEach((n) => c.append(insigniaOficial(n, true)));
}

function iniciarMapa() {
  if (!window.L) {
    $("#mapa").classList.add("mapa--error");
    $("#mapa").textContent = "No se pudo cargar el mapa. Usa la tabla con la misma información.";
    return;
  }
  const mapa = L.map("mapa", { scrollWheelZoom: false }).setView([-16.41, -71.54], 12);
  L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 18, attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
  }).addTo(mapa);
  // Zonas oficiales del aviso por lluvias, servidas en vivo por SENAMHI.
  L.tileLayer.wms(WMS_SENAMHI, {
    layers: "g_prono_pp_24h:view_aviso24h", format: "image/png", transparent: true, opacity: 0.5,
    attribution: 'Aviso por lluvias © <a href="https://idesep.senamhi.gob.pe/portalidesep/">SENAMHI</a>',
  }).addTo(mapa);
  estado.mapa = mapa;
  DISTRITOS.forEach((d) => {
    const icono = L.divIcon({ className: "", html: `<div class="marcador" data-id="${d.id}">${d.abrev}</div>`, iconSize: [30, 30], iconAnchor: [15, 15] });
    const m = L.marker([d.lat, d.lon], { icon: icono, keyboard: true, title: d.nombre, alt: d.nombre }).addTo(mapa);
    m.on("click", () => seleccionar(d.id, false));
    estado.marcadores[d.id] = m;
  });
  const rio = L.divIcon({ className: "", html: `<div class="marcador marcador--rio" title="Río Chili">≈</div>`, iconSize: [26, 26], iconAnchor: [13, 13] });
  L.marker([-16.374, -71.531], { icon: rio, title: "Río Chili" }).bindTooltip("Río Chili").addTo(mapa);
  estado.capaSinpad = L.layerGroup().addTo(mapa);
}

function renderMapa() {
  if (!estado.mapa) return;
  DISTRITOS.forEach((d) => {
    const n = nivelOficial(d.id);
    const m = estado.marcadores[d.id];
    const div = m.getElement()?.querySelector(".marcador");
    if (div) {
      div.dataset.nivel = hayOficial() ? NIVELES_OFICIALES[n].color : "";
      div.classList.toggle("marcador--activo", d.id === estado.distrito);
    }
    m.setZIndexOffset(d.id === estado.distrito ? 1000 : 0);
    const e = estado.niveles[d.id];
    const texto = `${d.nombre}: SENAMHI ${hayOficial() ? NIVELES_OFICIALES[n].nombre : "sin dato"}` + (e ? ` · modelo: ${num(e.maxDia)} mm/día` : "");
    m.unbindTooltip().bindTooltip(texto, { direction: "top", offset: [0, -14] });
  });
}

function renderOficiales() {
  const ind = estado.indicadores;
  // ENFEN
  const cE = $("#enfen");
  cE.replaceChildren();
  const enf = ind?.enfen;
  if (enf) {
    cE.append(nodo("p", "estado-enfen", enf.estado));
    cE.append(nodo("p", "titular", enf.titular));
    cE.append(nodo("p", "", enf.resumen));
    if (enf.lluvias) cE.append(nodo("p", "texto-oficial", enf.lluvias));
    const f = nodo("p", "fuente-linea", `Comunicado Oficial ENFEN N.º ${enf.numero} del ${fechaCorta(enf.fecha)}. `);
    f.append(enlace(enf.pdf, "Ver PDF oficial"));
    cE.append(f);
  } else cE.append(nodo("p", "vacio", "No se pudo leer el último comunicado de ENFEN."), enlace("https://www.senamhi.gob.pe/?p=fenomeno-el-nino", "Consultar en SENAMHI"));

  // SENAMHI avisos
  const cS = $("#senamhi");
  cS.replaceChildren();
  const sen = ind?.senamhi;
  if (sen) {
    const ll = sen.lluvia_24h;
    const niveles = DISTRITOS.map((d) => nivelOficial(d.id));
    const max = Math.max(...niveles);
    const p1 = nodo("p");
    p1.append(nodo("strong", "", "Aviso por lluvias (24 h): "), insigniaOficial(max, true),
      document.createTextNode(` nivel más alto en los ${DISTRITOS.length} distritos de Arequipa metropolitana. Vigencia: ${ll.fechas.map(fechaCorta).join(", ") || "—"}. ${ll.zonas_en_peru} zonas con aviso en todo el país.`));
    cS.append(p1);
    const av = sen.avisos_meteorologicos;
    if (av.que_afectan_arequipa.length) {
      const ul = nodo("ul");
      for (const a of av.que_afectan_arequipa) {
        ul.append(nodo("li", "", `Aviso meteorológico N.º ${a.numero} · ${a.texto} · ${a.fenomeno || "fenómeno sin código"} · del ${a.inicio ?? "—"} al ${a.fin ?? "—"} · distritos: ${a.distritos.length}`));
      }
      cS.append(ul);
    } else {
      cS.append(nodo("p", "", `Avisos meteorológicos vigentes que incluyen Arequipa metropolitana: ninguno (${av.vigentes_en_peru} vigentes en todo el país).`));
    }
    const f = nodo("p", "fuente-linea", `SENAMHI, servicio geográfico IDESEP, consultado ${fechaLarga(new Date(sen.consultado))}. `);
    f.append(enlace(sen.visor, "Abrir visor oficial"));
    cS.append(f);
  } else cS.append(nodo("p", "vacio", "No se pudieron leer los avisos de SENAMHI."));

  // Quebradas
  const cQ = $("#quebradas");
  cQ.replaceChildren();
  const q = sen?.quebradas_arequipa;
  if (q) {
    if (q.lista.length) {
      const ul = nodo("ul");
      for (const x of q.lista.slice(0, 12)) ul.append(nodo("li", "", `${fechaCorta(x.fecha)} · ${x.provincia} / ${x.distrito} · ${x.nivel}${x.descripcion ? " — " + x.descripcion : ""}`));
      cQ.append(ul);
      if (q.total > 12) cQ.append(nodo("p", "nota", `y ${q.total - 12} más en el visor oficial.`));
    } else cQ.append(nodo("p", "", "SENAMHI no reporta avisos de activación de quebradas vigentes en el departamento de Arequipa."));
    cQ.append(nodo("p", "fuente-linea", `Capa oficial ${q.capa}, consultada ${sen ? hora(new Date(sen.consultado)) : ""}.`));
  }
}

async function renderSinpad() {
  const caja = $("#sinpad");
  const peligros = ["LLUVIAS INTENSAS", "INUNDACIÓN", "INUNDACIÓN POR DESBORDE DE RIO", "INUNDACION", "HUAICO", "HUAYCO", "DESLIZAMIENTO", "ALUVIÓN", "ALUVION", "DERRUMBE DE CERRO", "EROSIÓN"];
  const where = `DEPARTAMENTO='AREQUIPA' AND PROVINCIA='AREQUIPA' AND FENOMENO IN ('${peligros.join("','")}')`;
  const url = SINPAD + "?" + new URLSearchParams({
    f: "json", where, outFields: "IDE_SINPAD,FECHA,DISTRITO,FENOMENO,SAFECTA,SDAMNI,SAFECTAVIVI",
    orderByFields: "FECHA DESC", resultRecordCount: "8", returnGeometry: "true", outSR: "4326",
  });
  try {
    const datos = await pedirJSON(url);
    if (!Array.isArray(datos.features)) throw new Error("respuesta inválida");
    caja.replaceChildren();
    estado.capaSinpad?.clearLayers();
    if (!datos.features.length) { caja.append(nodo("p", "", "Sin registros de emergencias por lluvias en la provincia.")); return; }
    const ul = nodo("ul");
    for (const f of datos.features) {
      const a = f.attributes;
      const fecha = new Date(a.FECHA).toISOString().slice(0, 10);
      const cifras = [a.SAFECTA ? `${a.SAFECTA} afectados` : "", a.SDAMNI ? `${a.SDAMNI} damnificados` : "", a.SAFECTAVIVI ? `${a.SAFECTAVIVI} viviendas afectadas` : ""].filter(Boolean).join(", ");
      ul.append(nodo("li", "", `${fechaCorta(fecha)} · ${a.DISTRITO} · ${a.FENOMENO.toLowerCase()}${cifras ? " · " + cifras : ""}`));
      const g = f.geometry;
      if (estado.capaSinpad && g && Number.isFinite(g.x) && Number.isFinite(g.y)) {
        L.circleMarker([g.y, g.x], { radius: 6, color: "#52514e", weight: 2, fillColor: "#ffffff", fillOpacity: 1 })
          .bindTooltip(`INDECI SINPAD ${a.IDE_SINPAD}: ${a.FENOMENO.toLowerCase()}, ${fechaCorta(fecha)}`).addTo(estado.capaSinpad);
      }
    }
    caja.append(ul);
    const ultimo = new Date(datos.features[0].attributes.FECHA).toISOString().slice(0, 10);
    const f = nodo("p", "fuente-linea", `Registro más reciente: ${fechaCorta(ultimo)}. Consulta en vivo al servicio GeoSINPAD de INDECI, ${hora(new Date())}. `);
    f.append(enlace("https://portal.indeci.gob.pe/emergencias/", "Reportes del COEN"));
    caja.append(f);
  } catch {
    caja.replaceChildren(nodo("p", "vacio", "No se pudo consultar SINPAD en este momento."), enlace("https://portal.indeci.gob.pe/emergencias/", "Ver reportes de INDECI"));
  }
}

function renderMetodo() {
  const i = estado.indicadores;
  const u = i?.umbrales_lluvia, r = i?.umbrales_rio;
  const caja = $("#metodo");
  caja.innerHTML = `
    <h3>Nivel oficial (SENAMHI)</h3>
    <p>El nivel principal de cada distrito es el del <strong>aviso por lluvias de 24 horas de SENAMHI</strong> (capa oficial
    <code>view_aviso24h</code> del servicio IDESEP). Se comprueba qué zona del aviso contiene el punto de referencia del distrito y se muestran
    su nivel, su descripción y su recomendación tal como los publica SENAMHI. También se revisan los avisos meteorológicos nacionales vigentes y
    los avisos de activación de quebradas del departamento de Arequipa.</p>
    <h3>Estado de El Niño (ENFEN)</h3>
    <p>Se lee el último Comunicado Oficial de ENFEN enlazado en la página de El Niño de SENAMHI: número, fecha, estado del sistema de alerta,
    titular y pronóstico de lluvias. Los datos semanales de temperatura del mar provienen de NOAA.</p>
    <h3>Emergencias (INDECI)</h3>
    <p>Las emergencias por lluvias, inundaciones, huaicos y deslizamientos registradas en la provincia de Arequipa se consultan en vivo en el servicio
    GeoSINPAD de INDECI y se marcan en el mapa con un círculo blanco.</p>
    <h3>Datos de referencia (no oficiales)</h3>
    <p>El pronóstico numérico de lluvia (Open-Meteo) y el caudal modelado del río Chili (GloFAS) son datos reales que complementan el aviso
    oficial. Su nivel de referencia compara la lluvia prevista con los percentiles 90, 95 y 99 del mismo modelo
    (${u ? `${num(u.diaria_mm.p90)}, ${num(u.diaria_mm.p95)} y ${num(u.diaria_mm.p99)} mm/día` : "—"}) y el caudal con los percentiles 90 y 99 de 1991–2020
    (${r ? `${num(r.p90)} y ${num(r.p99)} m³/s` : "—"}).</p>
    <h3>Actualización</h3>
    <p>Los avisos de SENAMHI y el comunicado de ENFEN se leen automáticamente cada 30 minutos (última lectura:
    ${i ? fechaLarga(new Date(i.generado)) : "—"}). La página vuelve a consultar todo cada 15 minutos. Si los datos oficiales tienen más de 2 horas
    o el pronóstico no responde en 30 minutos, se indica «no verificado».</p>`;
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
  for (const a of validos) {
    const art = nodo("article", "aviso");
    art.append(nodo("h3", "", a.titulo),
      nodo("p", "aviso__meta", `${a.emisor ?? "Fuente oficial"} · ${a.nivel_original ?? ""} · ${(a.distritos ?? []).join(", ") || "Arequipa"} · vigente hasta ${fechaLarga(new Date(a.vence))}`),
      nodo("p", "", a.mensaje ?? ""), enlace(a.fuente_url, "Ver documento original"));
    caja.append(art);
  }
}

function renderEstadoConexion() {
  const caja = $("#estado-conexion");
  const t = $("#estado-texto");
  // La antigüedad oficial se mide por la última lectura EXITOSA de SENAMHI.
  const consultado = estado.indicadores?.senamhi?.consultado;
  const gen = consultado ? Date.parse(consultado) : 0;
  const oficialViejo = !gen || Date.now() - gen > OFICIAL_VIEJO_MS;
  if (!estado.ultimoExito && !gen) { caja.dataset.estado = "error"; t.textContent = "Sin datos en vivo"; return; }
  if (oficialViejo) {
    caja.dataset.estado = "degradado";
    t.textContent = `Datos oficiales no verificados · última lectura ${gen ? hora(new Date(gen)) : "—"}`;
  } else if (Date.now() - estado.ultimoExito > DEGRADADO_MS) {
    caja.dataset.estado = "degradado";
    t.textContent = `Pronóstico no verificado · último dato ${hora(new Date(estado.ultimoExito))}`;
  } else {
    caja.dataset.estado = "vivo";
    t.textContent = `En vivo · oficial ${hora(new Date(gen))} · pronóstico ${hora(new Date(estado.ultimoExito))}`;
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
  renderOficiales();
  renderMetodo();
  renderEstadoConexion();
  $("#pie-generado").textContent = estado.indicadores ? `Datos oficiales leídos: ${fechaLarga(new Date(estado.indicadores.generado))}.` : "";
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

function avisar(texto) {
  toast(texto);
  notificar("AlertaNiño AQP", texto);
  if (leer("opt:sonido", false)) sonar(leer("opt:tono", "campana"));
}

// Solo avisa por cambios en datos OFICIALES.
function revisarCambios() {
  if (!hayOficial()) return;
  const d = DISTRITOS.find((x) => x.id === estado.distrito);
  const n = nivelOficial(d.id);
  const clave = `oficialPrevio:${d.id}`;
  const previo = leer(clave, null);
  guardar(clave, n);
  if (previo != null && n > previo) {
    avisar(`SENAMHI elevó el aviso por lluvias en ${d.nombre} a ${NIVELES_OFICIALES[n].nombre}.`);
  }
  const enf = estado.indicadores?.enfen;
  if (enf) {
    const previoEnfen = leer("enfenPrevio", null);
    guardar("enfenPrevio", enf.estado);
    if (previoEnfen && previoEnfen !== enf.estado) avisar(`ENFEN cambió su estado: ${enf.estado} (comunicado N.º ${enf.numero}).`);
  }
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
    const o = nodo("option", "", d.nombre + (d.piloto ? " · piloto" : ""));
    o.value = d.id;
    sel.append(o);
  }
  sel.value = estado.distrito;
  sel.addEventListener("change", () => seleccionar(sel.value, true));

  let escala = leer("escala", 1);
  const aplicarEscala = () => document.documentElement.style.setProperty("--escala", escala);
  aplicarEscala();
  $("#texto-mas").addEventListener("click", () => { escala = Math.min(1.5, +(escala + 0.1).toFixed(2)); guardar("escala", escala); aplicarEscala(); });
  $("#texto-menos").addEventListener("click", () => { escala = Math.max(0.9, +(escala - 0.1).toFixed(2)); guardar("escala", escala); aplicarEscala(); });

  const tema = leer("tema", null);
  if (tema) document.documentElement.dataset.theme = tema;
  $("#tema").addEventListener("click", () => {
    const oscuroAhora = document.documentElement.dataset.theme
      ? document.documentElement.dataset.theme === "dark"
      : matchMedia("(prefers-color-scheme: dark)").matches;
    const nuevo = oscuroAhora ? "light" : "dark";
    document.documentElement.dataset.theme = nuevo;
    guardar("tema", nuevo);
    if (estado.indicadores) renderTodo();
  });

  $("#ver-tabla-semana").addEventListener("click", (ev) => {
    const t = $("#tabla-semana");
    t.hidden = !t.hidden;
    ev.currentTarget.setAttribute("aria-expanded", String(!t.hidden));
    ev.currentTarget.textContent = t.hidden ? "Ver como tabla" : "Ocultar tabla";
  });

  const optN = $("#opt-notificar");
  optN.checked = leer("opt:notificar", false) && "Notification" in window && Notification.permission === "granted";
  optN.addEventListener("change", async () => {
    if (optN.checked) {
      if (!("Notification" in window)) { optN.checked = false; $("#notif-estado").textContent = "Este navegador no permite notificaciones; los avisos se mostrarán en la página."; return; }
      const permiso = await Notification.requestPermission();
      if (permiso !== "granted") { optN.checked = false; $("#notif-estado").textContent = "Permiso denegado. Puedes activarlo desde la configuración del navegador."; return; }
      notificar("AlertaNiño AQP", "Notificaciones activadas. Te avisaremos si SENAMHI eleva el aviso de tu distrito o si ENFEN cambia su estado.");
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
  if (!estado.indicadores) {
    renderEstadoConexion();
    renderTarjeta();
    return;
  }
  renderTodo();
  renderSinpad();
  revisarCambios();
  if (!exito) toast("No se pudo actualizar el pronóstico. Se muestran los últimos datos guardados con su hora.");
}

async function iniciar() {
  iniciarControles();
  renderLeyenda();
  iniciarMapa();
  estado.indicadores = leer("cache:indicadores");
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
