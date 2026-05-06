# Abre el puerto TCP 8082 entrante para Apache/Laragon (SJ_LegalSuite).
# Ejecutar en PowerShell como Administrador (clic derecho → Ejecutar como administrador).

$ruleName = "Laragon HTTP — SJ LegalSuite puerto 8082"

$existing = Get-NetFirewallRule -DisplayName $ruleName -ErrorAction SilentlyContinue
if ($existing) {
    Write-Host "Ya existe la regla: $ruleName"
    exit 0
}

New-NetFirewallRule -DisplayName $ruleName `
    -Direction Inbound `
    -Action Allow `
    -Protocol TCP `
    -LocalPort 8082 `
    -Profile Private, Domain `
    -Description "Permite acceso LAN a SJ_LegalSuite (Apache VirtualHost :8082)."

Write-Host "Regla creada: $ruleName (perfil Privado y Dominio)."
Write-Host "Si sigue sin cargar: red Wi‑Fi del servidor como Perfil Privado, no Publico."
