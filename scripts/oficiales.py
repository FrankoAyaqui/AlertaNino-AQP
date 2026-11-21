"""Fuentes oficiales del Estado peruano para AlertaNiño AQP.

  * SENAMHI (IDESEP GeoServer, servicio WFS público):
      - g_prono_pp_24h:view_aviso24h  -> aviso por lluvias de las próximas 24 h,
        polígonos con "Nivel 1" a "Nivel 4".
      - g_aviso:view_aviso            -> avisos meteorológicos nacionales vigentes.
      - g_acti_quebrada:view_av_activ_qdra -> aviso de activación de quebradas.
  * ENFEN: último Comunicado Oficial, enlazado desde la página de El Niño de
    SENAMHI (el sitio de ENFEN rechaza consultas automáticas).

El servidor de SENAMHI no permite consultas desde otras páginas (CORS), por eso
se leen aquí, en GitHub Actions, y se publican como JSON junto a la web.
Los textos oficiales se copian tal cual; el script solo decide qué polígono
contiene el punto de referencia de cada distrito.
"""

from __future__ import annotations

import datetime as dt
import io
import json
import re
import urllib.parse
import urllib.request
from pathlib import Path

LIMA = dt.timezone(dt.timedelta(hours=-5))
NAVEGADOR = ("Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
             "(KHTML, like Gecko) Chrome/130.0 Safari/537.36 AlertaNino-AQP")
WFS = "https://idesep.senamhi.gob.pe/geoserver/ows"
PAGINA_NINO = "https://www.senamhi.gob.pe/?p=fenomeno-el-nino"
VISOR_SENAMHI = "https://idesep.senamhi.gob.pe/portalidesep/"
DATOS_JS = Path(__file__).resolve().parent.parent / "web" / "js" / "datos.js"


def _bajar(url: str, timeout: int = 90) -> bytes:
    req = urllib.request.Request(url, headers={"User-Agent": NAVEGADOR, "Accept-Language": "es-PE,es"})
    with urllib.request.urlopen(req, timeout=timeout) as r:
        return r.read()


def _wfs(capa: str, **extra) -> dict:
    params = {"service": "WFS", "version": "2.0.0", "request": "GetFeature",
              "typeNames": capa, "outputFormat": "application/json", **extra}
    datos = json.loads(_bajar(WFS + "?" + urllib.parse.urlencode(params), timeout=120))
    if datos.get("type") != "FeatureCollection" or not isinstance(datos.get("features"), list):
        raise ValueError(f"respuesta inesperada de {capa}")
    return datos


def distritos() -> list[dict]:
    """Lee los distritos y sus puntos de referencia desde web/js/datos.js."""
    texto = DATOS_JS.read_text(encoding="utf-8")
    patron = re.compile(r'id: "([^"]+)", nombre: "([^"]+)", abrev: "[^"]+", lat: (-?[\d.]+), lon: (-?[\d.]+)')
    lista = [{"id": m[0], "nombre": m[1], "lat": float(m[2]), "lon": float(m[3])} for m in patron.findall(texto)]
    if len(lista) < 10:
        raise ValueError("no se pudieron leer los distritos")
    return lista


# ------------------------------------------------------------ geometría ---

def _en_anillo(lon: float, lat: float, anillo: list) -> bool:
    dentro = False
    j = len(anillo) - 1
    for i in range(len(anillo)):
        xi, yi = anillo[i][0], anillo[i][1]
        xj, yj = anillo[j][0], anillo[j][1]
        if (yi > lat) != (yj > lat) and lon < (xj - xi) * (lat - yi) / (yj - yi) + xi:
            dentro = not dentro
        j = i
    return dentro


def contiene(geom: dict | None, lon: float, lat: float) -> bool:
    if not geom:
        return False
    if geom["type"] == "Polygon":
        poligonos = [geom["coordinates"]]
    elif geom["type"] == "MultiPolygon":
        poligonos = geom["coordinates"]
    else:
        return False
    for poli in poligonos:
        if poli and _en_anillo(lon, lat, poli[0]) and not any(_en_anillo(lon, lat, h) for h in poli[1:]):
            return True
    return False


def _nivel(texto) -> int:
    m = re.search(r"\d", str(texto or ""))
    return int(m.group()) if m else 0


def _limpio(texto) -> str:
    return re.sub(r"\s+", " ", str(texto or "")).strip()


# ------------------------------------------------------------- SENAMHI ---

def senamhi(ahora: dt.datetime) -> dict:
    lista = distritos()
    salida: dict = {"consultado": ahora.isoformat(), "visor": VISOR_SENAMHI, "por_distrito": {}}

    # 1) Aviso por lluvias de las próximas 24 horas.
    capa = _wfs("g_prono_pp_24h:view_aviso24h")
    fechas = sorted({str(f["properties"].get("fecha", ""))[:10] for f in capa["features"]})
    salida["lluvia_24h"] = {
        "capa": "g_prono_pp_24h:view_aviso24h",
        "nombre": "Aviso por lluvias - pronóstico de 24 horas",
        "fechas": fechas,
        "zonas_en_peru": len(capa["features"]),
    }
    for d in lista:
        mejor = None
        for f in capa["features"]:
            p = f["properties"]
            if contiene(f.get("geometry"), d["lon"], d["lat"]):
                n = _nivel(p.get("nivel"))
                if mejor is None or n > mejor["nivel"]:
                    mejor = {"nivel": n, "texto": _limpio(p.get("nivel")), "fecha": str(p.get("fecha", ""))[:10],
                             "descripcion": _limpio(p.get("descripcio")), "recomendacion": _limpio(p.get("recomendac"))}
        salida["por_distrito"][d["id"]] = {"lluvia_24h": mejor, "avisos": []}

    # 2) Avisos meteorológicos nacionales vigentes.
    hoy = ahora.strftime("%Y-%m-%dT%H:%M:%S")
    capa = _wfs("g_aviso:view_aviso", CQL_FILTER=f"fech_fin >= '{hoy}'")
    vigentes = []
    for f in capa["features"]:
        p = f["properties"]
        aviso = {"numero": p.get("nro_aviso"), "nivel": _nivel(p.get("nivel")), "texto": _limpio(p.get("nivel")),
                 "fenomeno": _limpio(p.get("cod_fen")), "emitido": p.get("fecha_emi"),
                 "inicio": p.get("fech_ini"), "fin": p.get("fech_fin")}
        dentro = [d["id"] for d in lista if contiene(f.get("geometry"), d["lon"], d["lat"])]
        if dentro:
            aviso["distritos"] = dentro
            vigentes.append(aviso)
            for i in dentro:
                salida["por_distrito"][i]["avisos"].append(aviso)
    salida["avisos_meteorologicos"] = {"capa": "g_aviso:view_aviso", "vigentes_en_peru": len(capa["features"]),
                                       "que_afectan_arequipa": vigentes}

    # 3) Activación de quebradas en el departamento de Arequipa.
    capa = _wfs("g_acti_quebrada:view_av_activ_qdra", CQL_FILTER="nombdep='AREQUIPA'", count="300")
    quebradas = []
    for f in capa["features"]:
        p = f["properties"]
        quebradas.append({"fecha": str(p.get("fecha", ""))[:10], "provincia": _limpio(p.get("nombprov")),
                          "distrito": _limpio(p.get("nombdist")), "nivel": _limpio(p.get("nivel")),
                          "descripcion": _limpio(p.get("descripcion")), "recomendacion": _limpio(p.get("recomendacion"))})
    quebradas.sort(key=lambda q: (q["fecha"], q["provincia"], q["distrito"]), reverse=True)
    salida["quebradas_arequipa"] = {"capa": "g_acti_quebrada:view_av_activ_qdra", "lista": quebradas[:60],
                                    "total": len(quebradas)}
    return salida


# --------------------------------------------------------------- ENFEN ---

MESES = {m: i for i, m in enumerate(["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto",
                                     "septiembre", "octubre", "noviembre", "diciembre"], 1)}
MESES["setiembre"] = 9


def _sin_notas(texto: str) -> str:
    """Quita las llamadas a notas al pie que el PDF pega al texto: «20276» -> «2027», «extraordinaria2» -> «extraordinaria»."""
    texto = re.sub(r"\b(20\d\d)\d\b", r"\1", texto)
    return re.sub(r"(?<=[a-záéíóúñ])\d(?=[\s,.;])", "", texto)


def enfen() -> dict:
    """Último Comunicado Oficial ENFEN: número, fecha, estado de alerta y titular."""
    from pypdf import PdfReader  # se instala en el flujo de GitHub Actions

    html = _bajar(PAGINA_NINO).decode("utf-8", errors="replace")
    # Los comunicados oficiales se publican con el código 02204SENA-<n>.pdf
    numeros = [int(n) for n in re.findall(r"load/file/02204SENA-(\d+)\.pdf", html)]
    if not numeros:
        raise ValueError("no se encontró el enlace al comunicado")
    url = f"https://www.senamhi.gob.pe/load/file/02204SENA-{max(numeros)}.pdf"
    lector = PdfReader(io.BytesIO(_bajar(url)))
    texto = re.sub(r"\s+", " ", " ".join(p.extract_text() or "" for p in lector.pages))

    m = re.search(r"COMUNICADO OFICIAL ENFEN N\s*[°º]?\s*(\d+\s*-\s*\d{4})\s+(\d{1,2})\s+([a-záéíóú]+)\s+(\d{4})\s+(.+?)\s*\d?\s+"
                  r"([A-ZÁÉÍÓÚÑÜ0-9“”\"' ,;:.\-]{12,}?)\s+(?=[A-ZÁÉÍÓÚÑ][a-záéíóúñ])", texto)
    if not m:
        raise ValueError("no se reconoce el formato del comunicado")
    numero = m.group(1).replace(" ", "")
    mes = MESES.get(m.group(3).lower())
    fecha = dt.date(int(m.group(4)), mes, int(m.group(2))).isoformat() if mes else None
    estado = _limpio(m.group(5))
    titular = _limpio(m.group(6))
    resto = texto[m.end():]
    resumen = re.sub(r"\s*\(Figura \d+\)", "", resto.split("Figura 1.")[0]).strip()
    resumen = _sin_notas(resumen)
    lluvias = ""
    for viñeta in resto.split(""):
        v = _limpio(viñeta)
        if v.startswith("Las precipitaciones"):
            lluvias = _sin_notas(v)
            break
    if len(estado) > 80 or "alerta" not in estado.lower() and "vigilancia" not in estado.lower() and "no activo" not in estado.lower():
        raise ValueError(f"estado de alerta no reconocido: {estado[:80]}")
    return {"numero": numero, "fecha": fecha, "estado": estado, "titular": titular,
            "resumen": resumen[:900], "lluvias": lluvias[:700], "pdf": url, "pagina": PAGINA_NINO}
