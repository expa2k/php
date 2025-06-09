<?php
// functions/active_directory.php

class ActiveDirectoryManager {
    private $scriptPath;
    private $logFile;
    
    public function __construct($scriptPath = 'scripts/ad_script.ps1') {
        $this->scriptPath = $scriptPath;
        $this->logFile = 'logs/ad_operations.log';
        
        // Crear directorio de logs si no existe
        if (!file_exists(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }
    
    /**
     * Ejecuta un comando PowerShell y retorna el resultado
     */
    private function executePowerShell($command) {
        $fullCommand = "powershell.exe -ExecutionPolicy Bypass -Command \"" . addslashes($command) . "\"";
        
        // Log del comando ejecutado
        $this->logOperation("Ejecutando: " . $command);
        
        $output = shell_exec($fullCommand);
        
        // Log del resultado
        $this->logOperation("Resultado: " . ($output ?? 'Sin salida'));
        
        return $output;
    }
    
    /**
     * Registra operaciones en el log
     */
    private function logOperation($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$message}" . PHP_EOL;
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Instala y configura Active Directory
     */
    public function installAndConfigureAD() {
        try {
            $script = "
                function Instalar-ActiveDirectory(){
                    if(-not((Get-WindowsFeature -Name AD-Domain-Services).Installed)){
                        Install-WindowsFeature -Name AD-Domain-Services -IncludeManagementTools
                        return 'Active Directory instalado correctamente'
                    }
                    else{
                        return 'Active Directory ya se encuentra instalado'
                    }
                }
                
                function Configurar-DominioAD(){
                    if((Get-WmiObject Win32_ComputerSystem).Domain -eq '15champions.com'){
                        return 'El dominio ya se encuentra configurado'
                    }
                    else{
                        Import-Module ADDSDeployment
                        Install-ADDSForest -DomainName '15champions.com' -DomainNetbiosName '15CHAMPIONS' -InstallDNS -Force
                        return 'Dominio configurado correctamente'
                    }
                }
                
                \$resultado1 = Instalar-ActiveDirectory
                \$resultado2 = Configurar-DominioAD
                Write-Output \"\$resultado1||\$resultado2\"
            ";
            
            $result = $this->executePowerShell($script);
            
            return [
                'success' => true,
                'message' => 'Active Directory configurado correctamente',
                'details' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al configurar Active Directory: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Crea las unidades organizativas y grupos predeterminados
     */
    public function createOrganizationalUnits() {
        try {
            $script = "
                function Crear-UnidadesOrganizativas(){
                    \$resultados = @()
                    try {
                        # Crear unidades organizativas si no existen
                        if (-not (Get-ADOrganizationalUnit -Filter \"Name -eq 'Cuates'\" -ErrorAction SilentlyContinue)) {
                            New-ADOrganizationalUnit -Name 'Cuates' -Path 'DC=15champions,DC=com'
                            \$resultados += 'Unidad organizativa Cuates creada correctamente'
                        }
                        else {
                            \$resultados += 'La unidad organizativa Cuates ya existe'
                        }
                        
                        if (-not (Get-ADOrganizationalUnit -Filter \"Name -eq 'no cuates'\" -ErrorAction SilentlyContinue)) {
                            New-ADOrganizationalUnit -Name 'no cuates' -Path 'DC=15champions,DC=com'
                            \$resultados += 'Unidad organizativa no cuates creada correctamente'
                        }
                        else {
                            \$resultados += 'La unidad organizativa no cuates ya existe'
                        }
                        
                        # Crear grupos si no existen
                        if (-not (Get-ADGroup -Filter \"Name -eq 'grupo1'\" -ErrorAction SilentlyContinue)) {
                            New-ADGroup -Name 'grupo1' -SamAccountName 'grupo1' -GroupScope Global -GroupCategory Security -Path 'OU=Cuates,DC=15champions,DC=com'
                            \$resultados += 'Grupo grupo1 (Cuates) creado correctamente'
                        }
                        else {
                            \$resultados += 'El grupo grupo1 ya existe'
                        }
                        
                        if (-not (Get-ADGroup -Filter \"Name -eq 'grupo2'\" -ErrorAction SilentlyContinue)) {
                            New-ADGroup -Name 'grupo2' -SamAccountName 'grupo2' -GroupScope Global -GroupCategory Security -Path 'OU=no cuates,DC=15champions,DC=com'
                            \$resultados += 'Grupo grupo2 (no cuates) creado correctamente'
                        }
                        else {
                            \$resultados += 'El grupo grupo2 ya existe'
                        }
                        
                        return \$resultados -join '||'
                    }
                    catch {
                        return \"Error: \" + \$Error[0].ToString()
                    }
                }
                
                Crear-UnidadesOrganizativas
            ";
            
            $result = $this->executePowerShell($script);
            
            return [
                'success' => true,
                'message' => 'Unidades organizativas y grupos configurados correctamente',
                'details' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al crear unidades organizativas: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Crea un usuario en Active Directory
     * @param string $username Nombre de usuario
     * @param string $password Contraseña del usuario
     * @param string $group Grupo al que pertenecerá (grupo1 para Cuates, grupo2 para no cuates)
     * @param string $email Email del usuario (opcional)
     */
    public function createUser($username, $password, $group, $email = null) {
        try {
            // Validar parámetros
            if (empty($username) || empty($password) || empty($group)) {
                return [
                    'success' => false,
                    'message' => 'Todos los campos son obligatorios'
                ];
            }
            
            if (!in_array($group, ['grupo1', 'grupo2'])) {
                return [
                    'success' => false,
                    'message' => 'Grupo inválido. Debe ser grupo1 (Cuates) o grupo2 (no cuates)'
                ];
            }
            
            // Escapar caracteres especiales
            $username = addslashes($username);
            $password = addslashes($password);
            $email = $email ? addslashes($email) : $username . '@15champions.com';
            
            $script = "
                function Es-ContrasenaValida(\$contrasena) {
                    return (\$contrasena.Length -ge 8 -and
                            \$contrasena -match '[A-Z]' -and
                            \$contrasena -match '[a-z]' -and
                            \$contrasena -match '\\d' -and
                            \$contrasena -match '[^a-zA-Z\\d]')
                }
                
                function Configurar-Horarios(\$nombreUsuario, \$grupo){
                    try {
                        if(\$grupo -eq 'grupo1'){
                            # Horas de 8am a 3pm para Cuates
                            [byte[]]\$horasGrupoUno = @(0,128,63,0,128,63,0,128,63,0,128,63,0,128,63,0,128,63,0,128,63)
                            Get-ADUser -Identity \$nombreUsuario | Set-ADUser -Replace @{logonhours = \$horasGrupoUno}
                            return 'Horario del grupo1 configurado para ' + \$nombreUsuario
                        }
                        elseif(\$grupo -eq 'grupo2'){
                            # Horas de 3pm a 2am para no cuates
                            [byte[]]\$horasGrupoDos = @(255,1,192,255,1,192,255,1,192,255,1,192,255,1,192,255,1,192,255,1,192) 
                            Get-ADUser -Identity \$nombreUsuario | Set-ADUser -Replace @{logonhours = \$horasGrupoDos}
                            return 'Horario del grupo2 configurado para ' + \$nombreUsuario
                        }
                        else{
                            return 'Grupo inválido'
                        }
                    }
                    catch {
                        return 'Error configurando horarios: ' + \$Error[0].ToString()
                    }
                }
                
                function Crear-Usuario(){
                    try {
                        \$nombreUsuario = '{$username}'
                        \$contrasena = '{$password}'
                        \$grupo = '{$group}'
                        \$email = '{$email}'
                        
                        if(-not(Es-ContrasenaValida -contrasena \$contrasena)){
                            return 'Error: La contraseña no cumple con los requisitos de seguridad'
                        }
                        
                        # Verificar si el usuario ya existe
                        if (Get-ADUser -Filter \"SamAccountName -eq '\$nombreUsuario'\" -ErrorAction SilentlyContinue) {
                            return 'Error: El usuario ya existe'
                        }
                        
                        # Determinar la OU correcta según el grupo
                        \$ou = if (\$grupo -eq 'grupo1') { 'OU=Cuates,DC=15champions,DC=com' } else { 'OU=no cuates,DC=15champions,DC=com' }
                        
                        New-ADUser -Name \$nombreUsuario -GivenName \$nombreUsuario -Surname \$nombreUsuario -SamAccountName \$nombreUsuario -UserPrincipalName \"\$nombreUsuario@15champions.com\" -EmailAddress \$email -Path \$ou -ChangePasswordAtLogon \$true -AccountPassword (ConvertTo-SecureString \$contrasena -AsPlainText -Force) -Enabled \$true
                        Add-ADGroupMember -Identity \$grupo -Members \$nombreUsuario
                        \$horarioResult = Configurar-Horarios -nombreUsuario \$nombreUsuario -grupo \$grupo
                        
                        return \"Usuario creado correctamente||Grupo: \$grupo||Email: \$email||\$horarioResult\"
                    }
                    catch {
                        return 'Error: ' + \$Error[0].ToString()
                    }
                }
                
                Crear-Usuario
            ";
            
            $result = $this->executePowerShell($script);
            
            if (strpos($result, 'Error:') !== false) {
                return [
                    'success' => false,
                    'message' => $result
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Usuario creado correctamente en Active Directory',
                'details' => $result,
                'user_data' => [
                    'username' => $username,
                    'group' => $group,
                    'group_name' => $group === 'grupo1' ? 'Cuates' : 'no cuates',
                    'email' => $email,
                    'domain' => '15champions.com'
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al crear usuario: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Configura las políticas de aplicaciones para los grupos
     */
    public function configureApplicationPolicies() {
        try {
            $script = "
                function Configurar-PermisosAplicaciones(){ 
                    \$resultados = @()
                    try {
                        # Bloquear bloc de notas para el grupo2 (no cuates)
                        if(Get-GPO -Name 'Bloquear solo notepad' -ErrorAction SilentlyContinue){
                            \$resultados += 'La regla para el grupo2 ya se encuentra creada'
                        }
                        else{
                            New-GPO -Name 'Bloquear solo notepad' | Out-Null
                            New-GPLink -Name 'Bloquear solo notepad' -Target 'OU=no cuates,DC=15champions,DC=com'

                            Set-GPRegistryValue -Name 'Bloquear solo notepad' -Key 'HKCU\\Software\\Microsoft\\Windows\\CurrentVersion\\Policies\\Explorer' -ValueName 'DisallowRun' -Type DWord -Value 1
                            Set-GPRegistryValue -Name 'Bloquear solo notepad' -Key 'HKCU\\Software\\Microsoft\\Windows\\CurrentVersion\\Policies\\Explorer\\DisallowRun' -ValueName '1' -Type String -Value 'notepad.exe'
                            Set-GPPermissions -Name 'Bloquear solo notepad' -TargetName 'grupo2' -TargetType Group -PermissionLevel GpoApply

                            \$resultados += 'Regla para el grupo2 (no cuates) creada correctamente'
                        }

                        # Bloquear todo menos bloc de notas para el grupo1 (Cuates)
                        if(Get-GPO -Name 'Permitir solo notepad' -ErrorAction SilentlyContinue){
                            \$resultados += 'La regla para el grupo1 ya se encuentra creada'
                        }
                        else{
                            New-GPO -Name 'Permitir solo notepad' | Out-Null
                            New-GPLink -Name 'Permitir solo notepad' -Target 'OU=Cuates,DC=15champions,DC=com'

                            Set-GPRegistryValue -Name 'Permitir solo notepad' -Key 'HKCU\\Software\\Microsoft\\Windows\\CurrentVersion\\Policies\\Explorer' -ValueName 'RestrictRun' -Type DWord -Value 1
                            Set-GPRegistryValue -Name 'Permitir solo notepad' -Key 'HKCU\\Software\\Microsoft\\Windows\\CurrentVersion\\Policies\\Explorer\\RestrictRun' -ValueName '1' -Type String -Value 'notepad.exe'
                            Set-GPPermissions -Name 'Permitir solo notepad' -TargetName 'grupo1' -TargetType Group -PermissionLevel GpoApply

                            \$resultados += 'Regla para el grupo1 (Cuates) creada correctamente'
                        }
                        
                        return \$resultados -join '||'
                    }
                    catch {
                        return 'Error: ' + \$Error[0].ToString()
                    }
                }
                
                Configurar-PermisosAplicaciones
            ";
            
            $result = $this->executePowerShell($script);
            
            return [
                'success' => true,
                'message' => 'Políticas de aplicaciones configuradas correctamente',
                'details' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al configurar políticas: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Configura auditoría y políticas de contraseñas seguras
     */
    public function configureSecurityPolicies() {
        try {
            $script = "
                function Configurar-Auditoria(){
                    try {
                        \$nombreGpo = 'Auditoria dominio'
                        if (-not (Get-GPO -Name \$nombreGpo -ErrorAction SilentlyContinue)) {
                            New-GPO -Name \$nombreGpo
                            New-GPLink -Name \$nombreGpo -Target 'DC=15champions,DC=com'

                            Set-GPRegistryValue -Name \$nombreGpo -Key 'HKLM\\Software\\Policies\\Microsoft\\Windows\\System\\Audit' -ValueName 'Auditar' -Type DWord -Value 1

                            AuditPol /set /subcategory:'Acceso de servicio del directorio' /success:enable /failure:enable
                            AuditPol /set /subcategory:'Cambios de servicio de directorio' /success:enable /failure:enable

                            return 'Configuración de auditoría realizada correctamente'
                        }
                        else {
                            return 'La regla de auditoría ya se encuentra creada'
                        }
                    }
                    catch {
                        return 'Error en auditoría: ' + \$Error[0].ToString()
                    }
                }

                function Configurar-ContrasenasSeguras(){
                    try {
                        Set-ADDefaultDomainPasswordPolicy -Identity '15champions.com' -MinPasswordLength 8 -ComplexityEnabled \$true -PasswordHistoryCount 1 -MinPasswordAge '1.00:00:00' -MaxPasswordAge '30.00:00:00'
                        return 'Regla de contraseñas seguras configurada correctamente'
                    }
                    catch {
                        return 'Error en contraseñas: ' + \$Error[0].ToString()
                    }
                }
                
                \$auditoria = Configurar-Auditoria
                \$contrasenas = Configurar-ContrasenasSeguras
                Write-Output \"\$auditoria||\$contrasenas\"
            ";
            
            $result = $this->executePowerShell($script);
            
            return [
                'success' => true,
                'message' => 'Políticas de seguridad configuradas correctamente',
                'details' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al configurar políticas de seguridad: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtiene información de un usuario específico
     */
    public function getUserInfo($username) {
        try {
            $username = addslashes($username);
            
            $script = "
                try {
                    \$user = Get-ADUser -Identity '{$username}' -Properties * -ErrorAction Stop
                    \$group = if (\$user.MemberOf -match 'grupo1') { 'grupo1 (Cuates)' } elseif (\$user.MemberOf -match 'grupo2') { 'grupo2 (no cuates)' } else { 'Sin grupo' }
                    
                    Write-Output \"Nombre: \" + \$user.Name
                    Write-Output \"Email: \" + \$user.EmailAddress
                    Write-Output \"Grupo: \" + \$group
                    Write-Output \"Habilitado: \" + \$user.Enabled
                    Write-Output \"Último acceso: \" + \$user.LastLogonDate
                }
                catch {
                    Write-Output 'Error: Usuario no encontrado'
                }
            ";
            
            $result = $this->executePowerShell($script);
            
            return [
                'success' => true,
                'data' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener información del usuario: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Lista todos los usuarios del dominio
     */
    public function listUsers() {
        try {
            $script = "
                try {
                    \$users = Get-ADUser -Filter * -Properties MemberOf, EmailAddress, Enabled, LastLogonDate
                    
                    foreach (\$user in \$users) {
                        \$group = if (\$user.MemberOf -match 'grupo1') { 'Cuates' } elseif (\$user.MemberOf -match 'grupo2') { 'no cuates' } else { 'Sin grupo' }
                        Write-Output \$user.Name + '||' + \$user.SamAccountName + '||' + \$group + '||' + \$user.Enabled + '||' + \$user.EmailAddress
                    }
                }
                catch {
                    Write-Output 'Error: ' + \$Error[0].ToString()
                }
            ";
            
            $result = $this->executePowerShell($script);
            
            return [
                'success' => true,
                'data' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al listar usuarios: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Inicializa todo el entorno de Active Directory
     */
    public function initializeEnvironment() {
        try {
            $results = [];
            
            // 1. Instalar y configurar AD
            $adResult = $this->installAndConfigureAD();
            $results['ad_installation'] = $adResult;
            
            if ($adResult['success']) {
                // 2. Crear unidades organizativas
                $ouResult = $this->createOrganizationalUnits();
                $results['organizational_units'] = $ouResult;
                
                if ($ouResult['success']) {
                    // 3. Configurar políticas de aplicaciones
                    $appResult = $this->configureApplicationPolicies();
                    $results['application_policies'] = $appResult;
                    
                    // 4. Configurar políticas de seguridad
                    $secResult = $this->configureSecurityPolicies();
                    $results['security_policies'] = $secResult;
                }
            }
            
            return [
                'success' => true,
                'message' => 'Entorno de Active Directory inicializado correctamente',
                'results' => $results
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al inicializar el entorno: ' . $e->getMessage()
            ];
        }
    }
}
?>