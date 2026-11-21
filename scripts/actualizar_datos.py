#!/usr/bin/env python3
"""Actualiza web/data/indicadores.json con datos reales.

Fuentes (todas públicas y sin clave):
  * NOAA CPC - Índice Oceánico El Niño (ONI), mensual por trimestres.
  * NOAA CPC - Temperatura semanal del mar (OISST) en las regiones Niño 1+2 y 3.4.
  * Open-Meteo Historical Forecast API - lluvia diaria/horaria del mismo modelo
    que usa el pronóstico en vivo, para calcular umbrales por percentiles.
  * Open-Meteo Flood API (GloFAS) - caudal modelado del río Chili 1991-2020,
    para calcular umbrales de caudal alto.

El navegador no puede leer los archivos de NOAA directamente (no envían CORS),
por eso este script los descarga en el servidor de GitHub Actions y deja un
JSON estático junto a la web. Solo usa la biblioteca estándar de Python.

Si una fuente falla, se conserva el último valor bueno y se marca como
"anterior"; nunca se inventa un dato.
"""

from __future__ import annotations

import datetime as dt
import json
import math
import re
import sys
import urllib.request
from pathlib import Path

RAIZ = Path(__file__).resolve().parent.parent
SALIDA = RAIZ / "web" / "data" / "indicadores.json"
LIMA = dt.timezone(dt.timedelta(hours=-5))
AGENTE = "AlertaNino-AQP/1.0 (proyecto academico)"

# Punto de referencia para la ciudad (Cercado) y para el río Chili.
CIUDAD = (-16.40, -71.53)
RIO_CHILI = (-16.40, -71.54)
# Los umbrales cambian poco: se recalculan como máximo una vez al mes.
VIGENCIA_UMBRALES = dt.timedelta(days=30)


def ahora() -> dt.datetime:
    return dt.datetime.now(LIMA).replace(microsecond=0)


def descargar(url: str, timeout: int = 90) -> str:
    req = urllib.request.Request(url, headers={"User-Agent": AGENTE})
    with urllib.request.urlopen(req, timeout=timeout) as r:
        return r.read().decode("utf-8", errors="replace")


def percentil(valores: list[float], p: float) -> float:
    """Percentil con interpolación lineal (equivalente a numpy 'linear')."""
    v = sorted(valores)
    if not v:
        raise ValueError("sin datos")
    k = (len(v) - 1) * p
    f, c = math.floor(k), math.ceil(k)
    if f == c:
        return v[int(k)]
    return v[f] + (v[c] - v[f]) * (k - f)


# ---------------------------------------------------------------- ENSO ---

def clasificar_oni(anom: float) -> dict:
    """Escala de NOAA: episodio cálido si ONI >= +0.5 °C."""
    if anom >= 2.0:
        return {"fase": "El Niño", "intensidad": "muy fuerte"}
    if anom >= 1.5:
        return {"fase": "El Niño", "intensidad": "fuerte"}
    if anom >= 1.0:
        return {"fase": "El Niño", "intensidad": "moderado"}
    if anom >= 0.5:
        return {"fase": "El Niño", "intensidad": "débil"}
    if anom <= -1.5:
        return {"fase": "La Niña", "intensidad": "fuerte"}
    if anom <= -1.0:
        return {"fase": "La Niña", "intensidad": "moderada"}
    if anom <= -0.5:
        return {"fase": "La Niña", "intensidad": "débil"}
    return {"fase": "Neutral", "intensidad": ""}


def clasificar_icen(anom: float) -> str:
    """Categorías que usa ENFEN para el ICEN (región Niño 1+2)."""
    if anom > 3.0:
        return "Cálida extraordinaria"
    if anom > 1.7:
        return "Cálida fuerte"
    if anom > 1.0:
        return "Cálida moderada"
    if anom > 0.4:
        return "Cálida débil"
    if anom >= -1.0:
        return "Neutra"
    if anom >= -1.2:
        return "Fría débil"
    if anom >= -1.4:
        return "Fría moderada"
    return "Fría fuerte"


def leer_oni() -> dict:
    texto = descargar("https://www.cpc.ncep.noaa.gov/data/indices/oni.ascii.txt")
    filas = []
    for linea in texto.splitlines():
        partes = linea.split()
        if len(partes) == 4 and partes[1].isdigit():
            filas.append({"trimestre": partes[0], "anio": int(partes[1]),
                          "anomalia": float(partes[3])})
    if not filas:
        raise ValueError("ONI sin filas")
    ultimo = filas[-1]
    return {
        "ultimo": ultimo,
        "clasificacion": clasificar_oni(ultimo["anomalia"]),
        "serie": filas[-36:],
        "fuente": "NOAA CPC, Oceanic Niño Index (ERSSTv5)",
        "url": "https://www.cpc.ncep.noaa.gov/products/analysis_monitoring/ensostuff/ONI_v5.php",
    }


MESES = {m: i for i, m in enumerate(
    ["JAN", "FEB", "MAR", "APR", "MAY", "JUN", "JUL", "AUG", "SEP", "OCT", "NOV", "DEC"], 1)}


def leer_semanal() -> dict:
    texto = descargar("https://www.cpc.ncep.noaa.gov/data/indices/wksst9120.for")
    semanas = []
    for linea in texto.splitlines():
        m = re.match(r"\s*(\d{2})([A-Z]{3})(\d{4})(.*)", linea)
        if not m or m.group(2) not in MESES:
            continue
        nums = [float(x) for x in re.findall(r"-?\d+\.\d", m.group(4))]
        if len(nums) != 8:
            continue
        fecha = dt.date(int(m.group(3)), MESES[m.group(2)], int(m.group(1)))
        semanas.append({"semana": fecha.isoformat(),
                        "nino12": nums[0], "nino12_anom": nums[1],
                        "nino34": nums[4], "nino34_anom": nums[5]})
    if len(semanas) < 13:
        raise ValueError("datos semanales insuficientes")
    # Aproximación del ICEN: media de las últimas 13 semanas (~3 meses) de la
    # anomalía en Niño 1+2. El ICEN oficial de ENFEN usa medias mensuales ERSST.
    recientes = semanas[-13:]
    icen = round(sum(s["nino12_anom"] for s in recientes) / len(recientes), 2)
    return {
        "ultima": semanas[-1],
        "icen_aprox": icen,
        "icen_categoria": clasificar_icen(icen),
        "icen_desde": recientes[0]["semana"],
        "serie": semanas[-52:],
        "fuente": "NOAA CPC, OISST semanal (climatología 1991-2020)",
        "url": "https://www.cpc.ncep.noaa.gov/data/indices/wksst9120.for",
    }


# ------------------------------------------------------------ umbrales ---

def umbrales_lluvia() -> dict:
    hoy = ahora().date()
    fin = dt.date(hoy.year - 1, 12, 31)
    inicio = dt.date(2022, 1, 1)
    url = ("https://historical-forecast-api.open-meteo.com/v1/forecast"
           f"?latitude={CIUDAD[0]}&longitude={CIUDAD[1]}"
           f"&start_date={inicio}&end_date={fin}"
           "&daily=precipitation_sum&hourly=precipitation&timezone=America%2FLima")
    datos = json.loads(descargar(url, timeout=120))
    diaria = [x for x in datos["daily"]["precipitation_sum"] if x is not None]
    horaria = [x for x in datos["hourly"]["precipitation"] if x is not None]
    dias_lluvia = [x for x in diaria if x >= 1.0]
    horas_lluvia = [x for x in horaria if x >= 0.5]
    if len(dias_lluvia) < 50:
        raise ValueError("muy pocos días con lluvia para calcular percentiles")
    return {
        "diaria_mm": {
            "p90": round(percentil(dias_lluvia, 0.90), 1),
            "p95": round(percentil(dias_lluvia, 0.95), 1),
            "p99": round(percentil(dias_lluvia, 0.99), 1),
        },
        "horaria_mm": {
            "p95": round(percentil(horas_lluvia, 0.95), 1),
            "p99": round(percentil(horas_lluvia, 0.99), 1),
        },
        "dias_con_lluvia": len(dias_lluvia),
        "periodo": f"{inicio.year}-{fin.year}",
        "metodo": ("Percentiles de días con lluvia >= 1 mm del mismo modelo de "
                   "pronóstico (Open-Meteo best_match), punto Arequipa Cercado."),
        "calculado": ahora().isoformat(),
    }


def umbrales_rio() -> dict:
    url = ("https://flood-api.open-meteo.com/v1/flood"
           f"?latitude={RIO_CHILI[0]}&longitude={RIO_CHILI[1]}"
           "&start_date=1991-01-01&end_date=2020-12-31&daily=river_discharge")
    datos = json.loads(descargar(url, timeout=120))
    q = [x for x in datos["daily"]["river_discharge"] if x is not None]
    if len(q) < 3000:
        raise ValueError("serie de caudal incompleta")
    return {
        "p50": round(percentil(q, 0.50), 1),
        "p90": round(percentil(q, 0.90), 1),
        "p99": round(percentil(q, 0.99), 1),
        "maximo": round(max(q), 1),
        "periodo": "1991-2020",
        "punto": {"lat": datos["latitude"], "lon": datos["longitude"]},
        "metodo": "Percentiles del caudal diario modelado por GloFAS (Open-Meteo Flood API).",
        "calculado": ahora().isoformat(),
    }


def vigente(seccion: dict | None) -> bool:
    try:
        calc = dt.datetime.fromisoformat(seccion["calculado"])
        return ahora() - calc < VIGENCIA_UMBRALES
    except (TypeError, KeyError, ValueError):
        return False


# ---------------------------------------------------------------- main ---

def main() -> int:
    previo = {}
    if SALIDA.exists():
        try:
            previo = json.loads(SALIDA.read_text(encoding="utf-8"))
        except json.JSONDecodeError:
            previo = {}

    salida = {"generado": ahora().isoformat(), "estado_fuentes": {}}
    tareas = {
        "oni": (leer_oni, False),
        "semanal": (leer_semanal, False),
        "umbrales_lluvia": (umbrales_lluvia, True),
        "umbrales_rio": (umbrales_rio, True),
    }
    fallos = 0
    for clave, (funcion, es_umbral) in tareas.items():
        anterior = previo.get(clave)
        if es_umbral and vigente(anterior):
            salida[clave] = anterior
            salida["estado_fuentes"][clave] = "vigente"
            continue
        try:
            salida[clave] = funcion()
            salida["estado_fuentes"][clave] = "actualizado"
        except Exception as e:  # noqa: BLE001 - registrar y seguir con el resto
            fallos += 1
            print(f"[aviso] {clave}: {e}", file=sys.stderr)
            if anterior:
                salida[clave] = anterior
                salida["estado_fuentes"][clave] = "anterior"
            else:
                salida["estado_fuentes"][clave] = "sin datos"

    SALIDA.parent.mkdir(parents=True, exist_ok=True)
    SALIDA.write_text(json.dumps(salida, ensure_ascii=False, indent=1), encoding="utf-8")
    print(f"Escrito {SALIDA} ({fallos} fuentes con fallo)")
    # Solo falla el job si no hay ningún dato utilizable.
    return 1 if all(v == "sin datos" for v in salida["estado_fuentes"].values()) else 0


if __name__ == "__main__":
    sys.exit(main())
