# Actualiza APP_URL en .env con la IPv4 LAN activa (excluye 127.x y APIPA).
# Uso (desde la raíz del proyecto):
#   powershell -ExecutionPolicy Bypass -File scripts/windows/update-lan-url.ps1

$ErrorActionPreference = 'Stop'

$projectRoot = Resolve-Path (Join-Path $PSScriptRoot '..\..')
$envFile = Join-Path $projectRoot '.env'

if (-not (Test-Path $envFile)) {
    Write-Error "No se encontró .env en $projectRoot. Copie .env.example primero."
}

$ip = Get-NetIPAddress -AddressFamily IPv4 |
    Where-Object {
        $_.IPAddress -notlike '127.*' -and
        $_.IPAddress -notlike '169.254.*' -and
        $_.PrefixOrigin -ne 'WellKnown'
    } |
    Sort-Object -Property InterfaceMetric |
    Select-Object -First 1 -ExpandProperty IPAddress

if (-not $ip) {
    Write-Error 'No se detectó una IPv4 de red local. Verifique que Ethernet/Wi‑Fi esté conectada.'
}

$newUrl = "http://${ip}:8082"
$content = Get-Content $envFile -Raw -Encoding UTF8

if ($content -match '(?m)^APP_URL=.*$') {
    $content = $content -replace '(?m)^APP_URL=.*$', "APP_URL=$newUrl"
} else {
    $content += "`nAPP_URL=$newUrl`n"
}

Set-Content -Path $envFile -Value $content.TrimEnd() + "`n" -Encoding UTF8 -NoNewline

Write-Host "APP_URL actualizado a: $newUrl"
Write-Host ""
Write-Host "Abra en el navegador (mismo PC o celular en la misma red):"
Write-Host "  $newUrl"
Write-Host ""
Write-Host "APP_USE_REQUEST_URL=true permite usar otra IP/host si ya entra por HTTP."
Write-Host "Si no carga desde otro equipo: ejecute open-firewall-port-8082.ps1 como administrador."
