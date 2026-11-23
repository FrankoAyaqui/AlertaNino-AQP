// Gráficos SVG ligeros, sin dependencias. Cada uno se vuelve a dibujar al
// cambiar el tamaño del contenedor y trae su propia capa de hover.

const NS = "http://www.w3.org/2000/svg";
const M = { arriba: 10, derecha: 8, abajo: 22, izquierda: 34 };

function el(tag, attrs = {}, padre) {
  const n = document.createElementNS(NS, tag);
  for (const [k, v] of Object.entries(attrs)) n.setAttribute(k, v);
  if (padre) padre.appendChild(n);
  return n;
}

function marcasEje(max, cantidad = 4) {
  if (max <= 0) return [0];
  const paso0 = max / cantidad;
  const mag = 10 ** Math.floor(Math.log10(paso0));
  const paso = [1, 2, 2.5, 5, 10].map((f) => f * mag).find((p) => p >= paso0);
  const out = [];
  for (let v = 0; v <= max + 1e-9; v += paso) out.push(+v.toFixed(6));
  return out;
}

const fmt = (v, d = 1) => v.toLocaleString("es-PE", { minimumFractionDigits: d, maximumFractionDigits: d });

function preparar(contenedor) {
  contenedor.querySelector("svg")?.remove();
  contenedor.querySelector(".tooltip")?.remove();
  const ancho = Math.max(200, contenedor.clientWidth);
  const alto = Math.max(100, contenedor.clientHeight);
  const svg = el("svg", { viewBox: `0 0 ${ancho} ${alto}`, "aria-hidden": "true" });
  contenedor.appendChild(svg);
  const tip = document.createElement("div");
  tip.className = "tooltip";
  tip.hidden = true;
  contenedor.appendChild(tip);
  return { svg, tip, ancho, alto, iw: ancho - M.izquierda - M.derecha, ih: alto - M.arriba - M.abajo };
}

function ejeY(svg, ancho, ih, minV, maxV, sufijo = "") {
  const y = (v) => M.arriba + ih - ((v - minV) / (maxV - minV || 1)) * ih;
  let ticks = marcasEje(maxV, 4);
  if (minV < 0) {
    const base = marcasEje(maxV - minV, 4);
    const paso = base[1] ?? 1;
    ticks = [];
    for (let v = Math.ceil(minV / paso) * paso; v <= maxV + 1e-9; v += paso) ticks.push(+v.toFixed(6));
  }
  for (const t of ticks) {
    el("line", { x1: M.izquierda, x2: ancho - M.derecha, y1: y(t), y2: y(t), class: "rejilla" }, svg);
    el("text", { x: M.izquierda - 6, y: y(t) + 3.5, "text-anchor": "end", class: "eje-texto" }, svg).textContent = fmt(t, t % 1 ? 1 : 0) + sufijo;
  }
  return y;
}

function umbrales(svg, ancho, y, lista, maxV) {
  for (const u of lista ?? []) {
    if (u.valor > maxV) continue;
    el("line", { x1: M.izquierda, x2: ancho - M.derecha, y1: y(u.valor), y2: y(u.valor), class: `umbral umbral--${u.nivel}` }, svg);
    el("text", { x: M.izquierda + 4, y: y(u.valor) - 3, "text-anchor": "start", class: "umbral-texto" }, svg).textContent = u.texto;
  }
}

function mostrarTip(tip, x, y, html) {
  tip.innerHTML = html;
  tip.hidden = false;
  tip.style.left = `${x}px`;
  tip.style.top = `${y}px`;
}

/** Barras verticales con umbrales opcionales. */
export function barras(contenedor, { etiquetas, valores, ticks, umbrales: us, tooltip, colores, minimo }) {
  const dibujar = () => {
    const { svg, tip, ancho, iw, ih } = preparar(contenedor);
    const maxDato = Math.max(0, ...valores);
    const minDato = minimo ?? Math.min(0, ...valores);
    const maxU = Math.max(0, ...(us ?? []).map((u) => u.valor));
    const maxV = Math.max(maxDato, maxU * 1.08, minDato < 0 ? 0.5 : 1) * 1.05;
    const minV = minDato < 0 ? minDato * 1.1 : 0;
    const y = ejeY(svg, ancho, ih, minV, maxV);
    const banda = iw / valores.length;
    const hueco = Math.min(2, banda * 0.2);
    const anchoBarra = Math.max(1, banda - hueco);
    const cero = y(0);
    valores.forEach((v, i) => {
      const x = M.izquierda + i * banda + hueco / 2;
      const y1 = Math.min(y(v), cero), h = Math.max(v === 0 ? 0 : 1, Math.abs(y(v) - cero));
      const r = Math.min(4, anchoBarra / 2, h);
      const clase = colores ? "" : "barra";
      const color = colores ? colores(v, i) : null;
      // Barra con extremo redondeado (lado opuesto a la base).
      const arriba = v >= 0;
      const d = arriba
        ? `M${x},${cero} V${y1 + r} Q${x},${y1} ${x + r},${y1} H${x + anchoBarra - r} Q${x + anchoBarra},${y1} ${x + anchoBarra},${y1 + r} V${cero} Z`
        : `M${x},${cero} V${y1 + h - r} Q${x},${y1 + h} ${x + r},${y1 + h} H${x + anchoBarra - r} Q${x + anchoBarra},${y1 + h} ${x + anchoBarra},${y1 + h - r} V${cero} Z`;
      const p = el("path", { d, class: clase }, svg);
      if (color) p.setAttribute("fill", color);
      p.dataset.i = i;
    });
    el("line", { x1: M.izquierda, x2: ancho - M.derecha, y1: cero, y2: cero, class: "base" }, svg);
    umbrales(svg, ancho, y, us, maxV);
    // Etiquetas del eje X
    (ticks ?? etiquetas.map((_, i) => i)).forEach((i) => {
      el("text", { x: M.izquierda + i * banda + banda / 2, y: M.arriba + ih + 15, "text-anchor": "middle", class: "eje-texto" }, svg).textContent = etiquetas[i];
    });
    // Capa de hover más grande que la marca
    const zona = el("rect", { x: M.izquierda, y: M.arriba, width: iw, height: ih, class: "zona-hover" }, svg);
    const mover = (ev) => {
      const caja = svg.getBoundingClientRect();
      const px = ((ev.clientX - caja.left) / caja.width) * ancho;
      const i = Math.min(valores.length - 1, Math.max(0, Math.floor((px - M.izquierda) / banda)));
      svg.querySelectorAll("path[data-i]").forEach((p) => p.classList.toggle("resaltado", +p.dataset.i !== i));
      const cx = ((M.izquierda + i * banda + banda / 2) / ancho) * caja.width;
      const cy = ((Math.min(y(valores[i]), cero)) / (contenedor.clientHeight || 1)) * contenedor.clientHeight;
      mostrarTip(tip, cx, Math.max(16, cy), tooltip(i));
    };
    const salir = () => { tip.hidden = true; svg.querySelectorAll("path[data-i]").forEach((p) => p.classList.remove("resaltado")); };
    zona.addEventListener("pointermove", mover);
    zona.addEventListener("pointerdown", mover);
    zona.addEventListener("pointerleave", salir);
  };
  observar(contenedor, dibujar);
}

/** Línea con tramo de pronóstico punteado, banda de incertidumbre y umbrales. */
export function linea(contenedor, { etiquetas, valores, banda, corte, umbrales: us, tooltip, ticks }) {
  const dibujar = () => {
    const { svg, tip, ancho, iw, ih } = preparar(contenedor);
    const maxV = Math.max(...valores, ...(banda ? banda.map((b) => b?.[1] ?? 0) : []), ...(us ?? []).map((u) => u.valor * 1.08)) * 1.05;
    const y = ejeY(svg, ancho, ih, 0, maxV);
    const n = valores.length;
    const x = (i) => M.izquierda + (i / (n - 1)) * iw;
    if (banda) {
      const puntos = banda.map((b, i) => (b ? [i, b] : null)).filter(Boolean);
      if (puntos.length > 1) {
        const d = puntos.map(([i, b], k) => `${k ? "L" : "M"}${x(i)},${y(b[1])}`).join("") +
          puntos.slice().reverse().map(([i, b]) => `L${x(i)},${y(b[0])}`).join("") + "Z";
        el("path", { d, class: "banda" }, svg);
      }
    }
    umbrales(svg, ancho, y, us, maxV);
    const tramo = (desde, hasta, clase) => {
      const d = valores.slice(desde, hasta + 1).map((v, k) => `${k ? "L" : "M"}${x(desde + k)},${y(v)}`).join("");
      el("path", { d, class: clase }, svg);
    };
    if (corte != null && corte > 0 && corte < n - 1) {
      tramo(0, corte, "linea");
      tramo(corte, n - 1, "linea linea--prono");
      el("line", { x1: x(corte), x2: x(corte), y1: M.arriba, y2: M.arriba + ih, class: "hoy" }, svg);
      el("text", { x: x(corte) + 4, y: M.arriba + 10, class: "eje-texto" }, svg).textContent = "hoy";
    } else tramo(0, n - 1, "linea");
    el("line", { x1: M.izquierda, x2: ancho - M.derecha, y1: y(0), y2: y(0), class: "base" }, svg);
    (ticks ?? []).forEach((i) => {
      el("text", { x: x(i), y: M.arriba + ih + 15, "text-anchor": "middle", class: "eje-texto" }, svg).textContent = etiquetas[i];
    });
    const cruz = el("line", { y1: M.arriba, y2: M.arriba + ih, class: "cruz", visibility: "hidden" }, svg);
    const punto = el("circle", { r: 4.5, fill: "var(--serie)", stroke: "var(--superficie)", "stroke-width": 2, visibility: "hidden" }, svg);
    const zona = el("rect", { x: M.izquierda, y: M.arriba, width: iw, height: ih, class: "zona-hover" }, svg);
    const mover = (ev) => {
      const caja = svg.getBoundingClientRect();
      const px = ((ev.clientX - caja.left) / caja.width) * ancho;
      const i = Math.min(n - 1, Math.max(0, Math.round(((px - M.izquierda) / iw) * (n - 1))));
      cruz.setAttribute("x1", x(i)); cruz.setAttribute("x2", x(i)); cruz.setAttribute("visibility", "visible");
      punto.setAttribute("cx", x(i)); punto.setAttribute("cy", y(valores[i])); punto.setAttribute("visibility", "visible");
      mostrarTip(tip, (x(i) / ancho) * caja.width, Math.max(16, (y(valores[i]) / contenedor.clientHeight) * contenedor.clientHeight), tooltip(i));
    };
    const salir = () => { tip.hidden = true; cruz.setAttribute("visibility", "hidden"); punto.setAttribute("visibility", "hidden"); };
    zona.addEventListener("pointermove", mover);
    zona.addEventListener("pointerdown", mover);
    zona.addEventListener("pointerleave", salir);
  };
  observar(contenedor, dibujar);
}

const observadores = new WeakMap();
function observar(contenedor, dibujar) {
  observadores.get(contenedor)?.disconnect();
  dibujar();
  let ultimo = contenedor.clientWidth;
  const ro = new ResizeObserver(() => {
    if (Math.abs(contenedor.clientWidth - ultimo) < 2) return;
    ultimo = contenedor.clientWidth;
    dibujar();
  });
  ro.observe(contenedor);
  observadores.set(contenedor, ro);
}
