# Script para configurar servidor FTP con autenticación Active Directory

param(
    [Parameter(Mandatory=$true)]
    [string]$SitioFTP = "Default FTP Site",
    
    [Parameter(Mandatory=$true)]
    [string]$RutaFTP = "C:\Users\Administrator\Documents\ftp_users",
    
    [string]$Puerto = "21",
    
    [string]$DominioAD = $env:USERDOMAIN
)

# Importar módulos necesarios
Import-Module WebAdministration
Import-Module ActiveDirectory

try {
    Write-Host "Configurando servidor FTP con autenticación Active Directory..." -ForegroundColor Cyan
    
    # Verificar si IIS está instalado
    $iisFeature = Get-WindowsFeature -Name IIS-WebServerRole
    if ($iisFeature.InstallState -ne "Installed") {
        Write-Host "Instalando IIS..." -ForegroundColor Yellow
        Enable-WindowsOptionalFeature -Online -FeatureName IIS-WebServerRole -All
    }
    
    # Verificar si FTP está instalado
    $ftpFeature = Get-WindowsFeature -Name IIS-FTPServer
    if ($ftpFeature.InstallState -ne "Installed") {
        Write-Host "Instalando servidor FTP..." -ForegroundColor Yellow
        Enable-WindowsOptionalFeature -Online -FeatureName IIS-FTPServer -All
        Enable-WindowsOptionalFeature -Online -FeatureName IIS-FTPSvc -All
    }
    
    # Crear directorio FTP si no existe
    if (-not (Test-Path $RutaFTP)) {
        New-Item -Path $RutaFTP -ItemType Directory -Force
        Write-Host "Directorio FTP creado: $RutaFTP" -ForegroundColor Green
    }
    
    # Configurar permisos del directorio FTP
    $acl = Get-Acl $RutaFTP
    $accessRule = New-Object System.Security.AccessControl.FileSystemAccessRule("IIS_IUSRS", "FullControl", "ContainerInherit,ObjectInherit", "None", "Allow")
    $acl.SetAccessRule($accessRule)
    Set-Acl $RutaFTP $acl
    
    # Crear sitio FTP
    if (Get-WebSite -Name $SitioFTP -ErrorAction SilentlyContinue) {
        Remove-WebSite -Name $SitioFTP
        Write-Host "Sitio FTP existente eliminado" -ForegroundColor Yellow
    }
    
    New-WebFtpSite -Name $SitioFTP -Port $Puerto -PhysicalPath $RutaFTP
    Write-Host "Sitio FTP '$SitioFTP' creado en puerto $Puerto" -ForegroundColor Green
    
    # Configurar autenticación básica
    Set-WebConfiguration -Filter "/system.ftpServer/security/authentication/basicAuthentication" -Value @{enabled="true"} -PSPath "IIS:" -Location "$SitioFTP"
    Set-WebConfiguration -Filter "/system.ftpServer/security/authentication/anonymousAuthentication" -Value @{enabled="false"} -PSPath "IIS:" -Location "$SitioFTP"
    
    # Configurar autorización para usuarios de dominio
    Add-WebConfiguration -Filter "/system.ftpServer/security/authorization" -Value @{accessType="Allow"; users="*"; permissions="Read,Write"} -PSPath "IIS:" -Location "$SitioFTP"
    
    # Configurar SSL (opcional)
    $sslPolicy = @{
        controlChannelPolicy = "SslAllow"
        dataChannelPolicy = "SslAllow"
    }
    Set-WebConfiguration -Filter "/system.ftpServer/security/ssl" -Value $sslPolicy -PSPath "IIS:" -Location "$SitioFTP"
    
    Write-Host "Servidor FTP configurado exitosamente con autenticación Active Directory" -ForegroundColor Green
    Write-Host "Sitio: $SitioFTP" -ForegroundColor Cyan
    Write-Host "Puerto: $Puerto" -ForegroundColor Cyan
    Write-Host "Ruta: $RutaFTP" -ForegroundColor Cyan
    Write-Host "Dominio: $DominioAD" -ForegroundColor Cyan
    
} catch {
    Write-Host "Error al configurar servidor FTP: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}