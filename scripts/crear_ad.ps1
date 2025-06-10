# Script para crear usuarios en Active Directory y asignarlos a OUs y grupos específicos
# Versión modificada para ser llamada desde PHP

param(
    [Parameter(Mandatory=$true)]
    [string]$NombreUsuario,
    
    [Parameter(Mandatory=$true)]
    [string]$NombreCompleto,
    
    [Parameter(Mandatory=$true)]
    [string]$Email,
    
    [Parameter(Mandatory=$true)]
    [ValidateSet("cuates", "no cuates")]
    [string]$TipoUsuario,
    
    [Parameter(Mandatory=$true)]
    [string]$Password,  # <-- CAMBIO: Se cambió de [SecureString] a [string]
    
    [string]$Dominio = "DC=17champions,DC=com"  # Cambia esto por tu dominio real
)

# Importar módulo de Active Directory
Import-Module ActiveDirectory

try {
    # Verificar si el usuario ya existe
    $usuarioExistente = Get-ADUser -Filter "SamAccountName -eq '$NombreUsuario'" -ErrorAction SilentlyContinue
    
    if ($usuarioExistente) {
        Write-Host "Error: El usuario '$NombreUsuario' ya existe en Active Directory."
        exit 1
    }

    # Definir OU y grupo según el tipo de usuario
    if ($TipoUsuario -eq "cuates") {
        $OU = "OU=cuates,$Dominio"
        $Grupo = "grupo1"
    }
    else {
        $OU = "OU=no cuates,$Dominio"
        $Grupo = "grupo2"
    }

    # Verificar si la OU existe, si no, crearla
    if (-not (Get-ADOrganizationalUnit -Filter "DistinguishedName -eq '$OU'" -ErrorAction SilentlyContinue)) {
        $parentPath = $Dominio
        $ouName = ($OU -split ',')[0] -replace 'OU='
        New-ADOrganizationalUnit -Name $ouName -Path $parentPath -ProtectedFromAccidentalDeletion $false
        Write-Host "OU '$ouName' creada correctamente."
    }

    # Verificar si el grupo existe, si no, crearlo
    if (-not (Get-ADGroup -Filter "Name -eq '$Grupo'" -ErrorAction SilentlyContinue)) {
        New-ADGroup -Name $Grupo -GroupScope Global -Path $OU
        Write-Host "Grupo '$Grupo' creado correctamente en la OU '$OU'."
    }
    
    # <-- CAMBIO: Convertir la contraseña de texto plano a SecureString
    $securePassword = ConvertTo-SecureString $Password -AsPlainText -Force

    # Crear el usuario en la OU correspondiente
    $parametrosUsuario = @{
        Name                  = $NombreCompleto
        SamAccountName        = $NombreUsuario
        UserPrincipalName     = "$NombreUsuario@$((Get-ADDomain).DNSRoot)"
        DisplayName           = $NombreCompleto
        EmailAddress          = $Email
        AccountPassword       = $securePassword # <-- Se usa la contraseña segura
        Enabled               = $true
        Path                  = $OU
        ChangePasswordAtLogon = $true
    }
    
    New-ADUser @parametrosUsuario
    Write-Host "Usuario '$NombreUsuario' creado exitosamente en la OU '$OU'."
    
    # Agregar usuario al grupo correspondiente
    Add-ADGroupMember -Identity $Grupo -Members $NombreUsuario
    Write-Host "Usuario '$NombreUsuario' agregado al grupo '$Grupo'."
    
} catch {
    Write-Host "Error al crear el usuario: $($_.Exception.Message)"
    exit 1
}