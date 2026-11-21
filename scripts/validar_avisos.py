#!/usr/bin/env python3
"""Valida web/data/avisos.json antes de publicar.

Cada aviso manual debe copiar un aviso oficial real y conservar su fuente:
  id, titulo, emisor, nivel_original, distritos (lista), mensaje,
  fuente_url (https), emitido y vence (ISO 8601 con zona horaria).
Si algo no cumple, el job falla y la web no se publica con datos dudosos.
"""

import datetime as dt
import json
import re
import sys
from pathlib import Path

RAIZ = Path(__file__).resolve().parent.parent
ARCHIVO = RAIZ / "web" / "data" / "avisos.json"
DATOS_JS = RAIZ / "web" / "js" / "datos.js"

OBLIGATORIOS = ["id", "titulo", "emisor", "nivel_original", "distritos", "mensaje", "fuente_url", "emitido", "vence"]


def main() -> int:
    avisos = json.loads(ARCHIVO.read_text(encoding="utf-8"))
    if not isinstance(avisos, list):
        print("avisos.json debe ser una lista")
        return 1
    nombres = set(re.findall(r'nombre: "([^"]+)"', DATOS_JS.read_text(encoding="utf-8")))
    errores, ids = [], set()
    for n, a in enumerate(avisos, 1):
        pre = f"aviso {n} ({a.get('id', 'sin id')})"
        for campo in OBLIGATORIOS:
            if not a.get(campo):
                errores.append(f"{pre}: falta '{campo}'")
        if a.get("id") in ids:
            errores.append(f"{pre}: id repetido")
        ids.add(a.get("id"))
        if not str(a.get("fuente_url", "")).startswith("https://"):
            errores.append(f"{pre}: la fuente debe ser una URL https")
        if not 15 <= len(a.get("mensaje", "")) <= 3000:
            errores.append(f"{pre}: el mensaje debe tener entre 15 y 3000 caracteres")
        if len(a.get("titulo", "")) > 160:
            errores.append(f"{pre}: título de más de 160 caracteres")
        for d in a.get("distritos", []):
            if d not in nombres:
                errores.append(f"{pre}: distrito desconocido '{d}'")
        try:
            emitido = dt.datetime.fromisoformat(a["emitido"])
            vence = dt.datetime.fromisoformat(a["vence"])
            if emitido.tzinfo is None or vence.tzinfo is None:
                errores.append(f"{pre}: las fechas deben incluir zona horaria (p. ej. -05:00)")
            elif vence <= emitido:
                errores.append(f"{pre}: 'vence' debe ser posterior a 'emitido'")
        except (KeyError, ValueError):
            errores.append(f"{pre}: fechas inválidas")
    for e in errores:
        print("ERROR", e)
    print(f"{len(avisos)} avisos revisados, {len(errores)} errores")
    return 1 if errores else 0


if __name__ == "__main__":
    sys.exit(main())
