<?php
// Archivo PHP para manejar tu script de Active Directory

function executeADScript($action, $username = "", $password = "", $group = "grupo1") {
    // Ruta del script PowerShell
    $scriptPath = __DIR__ . '/../scripts/ad_script_adapted.ps1';
    
    // Construir comando PowerShell
    $command = "powershell.exe -ExecutionPolicy Bypass -File \"$scriptPath\" -Action $action";
    
    // Agregar parámetros según la acción
    if ($action == 'create_user') {
        $command .= " -Username \"$username\" -Password \"$password\" -Group \"$group\"";
    }
    
    // Ejecutar comando
    $output = shell_exec($command . ' 2>&1');
    
    return [
        'success' => (strpos($output, 'creado correctamente') !== false && strpos($output, 'Error') === false),
        'output' => $output
    ];
}

// Función específica para crear usuario
function createADUser($username, $password, $group = "grupo1") {
    return executeADScript('create_user', $username, $password, $group);
}

// Otras funciones de configuración
function installAD() {
    return executeADScript('install');
}

function setupADStructure() {
    return executeADScript('setup');
}

function configureApps() {
    return executeADScript('configure_apps');
}

function configureSecurity() {
    return executeADScript('configure_security');
}

function configureStorage() {
    return executeADScript('configure_storage');
}
?>