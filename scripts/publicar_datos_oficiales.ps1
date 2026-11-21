# Lee SENAMHI y ENFEN desde esta PC (en Perú) y publica los datos en GitHub.
# SENAMHI no responde a los servidores de GitHub, por eso esta lectura se hace aquí.
# Uso:  powershell -ExecutionPolicy Bypass -File scripts\publicar_datos_oficiales.ps1
$ErrorActionPreference = "Stop"
Set-Location (Split-Path $PSScriptRoot -Parent)
$env:Path = [Environment]::GetEnvironmentVariable('Path','Machine') + ';' + [Environment]::GetEnvironmentVariable('Path','User')

python scripts/actualizar_datos.py
$estado = python -c "import json;d=json.load(open('web/data/indicadores.json',encoding='utf-8'));print(d['estado_fuentes'].get('senamhi'),d['estado_fuentes'].get('enfen'))"
Write-Host "SENAMHI / ENFEN: $estado"
if ($estado -notmatch '^actualizado') { Write-Host "SENAMHI no respondió; no se publica nada."; exit 1 }

git pull --ff-only -q
git add web/data/indicadores.json
git diff --cached --quiet
if ($LASTEXITCODE -eq 0) { Write-Host "Sin cambios."; exit 0 }
git commit -q -m "datos: lectura oficial SENAMHI y ENFEN desde Peru"
git push -q
Write-Host "Publicado. GitHub Pages se actualiza en 1-2 minutos."
