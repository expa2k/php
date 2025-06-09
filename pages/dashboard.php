<?php
// pages/dashboard.php
require_once '../includes/header.php';
require_once '../functions/personal.php';
require_once '../functions/active_directory.php';

$personal = new Personal();
$totalEmpleados = count($personal->readAll());

$message = '';
$error = '';

// Configuración SSH
$sshUser = "carlos";
$sshHost = "192.168.100.79";
$sshKey = "C:\\Users\\lourd\\.ssh\\id_ed25519";  // CAMBIAR POR TU RUTA REAL

// Función para ejecutar comandos remotos por SSH
function runSSHCommand($command) {
    global $sshUser, $sshHost, $sshKey;
    $sshCommand = "ssh -i \"$sshKey\" -o StrictHostKeyChecking=no -o ConnectTimeout=10 -o BatchMode=yes {$sshUser}@{$sshHost} \"{$command}\" 2>&1";
    return shell_exec($sshCommand);
}

// Función para verificar si un contenedor está corriendo
function isContainerRunning($containerName) {
    $command = "docker ps --filter \"name={$containerName}\" --filter \"status=running\" --format \"{{.Names}}\"";
    $output = runSSHCommand($command);
    return trim($output ?: '') === $containerName;
}

function containerExists($containerName) {
    $command = "docker ps -a --filter \"name={$containerName}\" --format \"{{.Names}}\"";
    $output = runSSHCommand($command);
    return trim($output ?: '') === $containerName;
}

// Manejar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    $service = $_POST['docker_service'] ?? null;

    // Gestión de contenedores Docker
    if ($action === 'start' && $service) {
        $containerName = ($service === 'apache') ? 'mi_apache_container' : 'mi_postgres_container';
        
        if (isContainerRunning($containerName)) {
            $error = "El contenedor {$containerName} ya está en ejecución.";
        } else {
            if (containerExists($containerName)) {
                $output = runSSHCommand("docker start {$containerName}");
                $message = "Contenedor {$containerName} iniciado:<br><pre>" . htmlspecialchars($output) . "</pre>";
            } else {
                if ($service === 'apache') {
                    $output = runSSHCommand("docker run -d --name {$containerName} httpd");
                } else {
                    $output = runSSHCommand("docker run -d --name {$containerName} -e POSTGRES_PASSWORD=mi_password postgres");
                }
                $message = "Contenedor {$containerName} creado y levantado:<br><pre>" . htmlspecialchars($output) . "</pre>";
            }
        }
    } elseif ($action === 'stop' && isset($_POST['container_name'])) {
        $containerName = $_POST['container_name'];
        if (isContainerRunning($containerName)) {
            $output = runSSHCommand("docker stop {$containerName}");
            $message = "Contenedor {$containerName} detenido:<br><pre>" . htmlspecialchars($output) . "</pre>";
        } else {
            $error = "El contenedor {$containerName} no está en ejecución.";
        }
    } elseif ($action === 'remove' && isset($_POST['container_name'])) {
        $containerName = $_POST['container_name'];
        if (containerExists($containerName)) {
            if (isContainerRunning($containerName)) {
                runSSHCommand("docker stop {$containerName}");
            }
            $output = runSSHCommand("docker rm {$containerName}");
            $message = "Contenedor {$containerName} eliminado:<br><pre>" . htmlspecialchars($output) . "</pre>";
        } else {
            $error = "El contenedor {$containerName} no existe.";
        }
    }
    
    // Gestión de Active Directory
    elseif ($action === 'install_ad') {
        $result = installAD();
        if ($result['success']) {
            $message = "Active Directory instalado correctamente:<br><pre>" . htmlspecialchars($result['output']) . "</pre>";
        } else {
            $error = "Error al instalar Active Directory:<br><pre>" . htmlspecialchars($result['output']) . "</pre>";
        }
    } elseif ($action === 'setup_ad') {
        $result = setupADStructure();
        if ($result['success']) {
            $message = "Estructura de AD configurada correctamente:<br><pre>" . htmlspecialchars($result['output']) . "</pre>";
        } else {
            $error = "Error al configurar estructura de AD:<br><pre>" . htmlspecialchars($result['output']) . "</pre>";
        }
    } elseif ($action === 'create_ad_user') {
        $username = $_POST['ad_username'] ?? '';
        $password = $_POST['ad_password'] ?? '';
        $group = $_POST['ad_group'] ?? 'grupo1';
        
        if (empty($username) || empty($password)) {
            $error = "Nombre de usuario y contraseña son requeridos.";
        } else {
            $result = createADUser($username, $password, $group);
            if ($result['success']) {
                $message = "Usuario de AD creado correctamente:<br><pre>" . htmlspecialchars($result['output']) . "</pre>";
            } else {
                $error = "Error al crear usuario de AD:<br><pre>" . htmlspecialchars($result['output']) . "</pre>";
            }
        }
    } elseif ($action === 'configure_apps') {
        $result = configureApps();
        if ($result['success']) {
            $message = "Permisos de aplicaciones configurados:<br><pre>" . htmlspecialchars($result['output']) . "</pre>";
        } else {
            $error = "Error al configurar aplicaciones:<br><pre>" . htmlspecialchars($result['output']) . "</pre>";
        }
    } elseif ($action === 'configure_security') {
        $result = configureSecurity();
        if ($result['success']) {
            $message = "Configuración de seguridad aplicada:<br><pre>" . htmlspecialchars($result['output']) . "</pre>";
        } else {
            $error = "Error en configuración de seguridad:<br><pre>" . htmlspecialchars($result['output']) . "</pre>";
        }
    } elseif ($action === 'configure_storage') {
        $result = configureStorage();
        if ($result['success']) {
            $message = "Configuración de almacenamiento aplicada:<br><pre>" . htmlspecialchars($result['output']) . "</pre>";
        } else {
            $error = "Error en configuración de almacenamiento:<br><pre>" . htmlspecialchars($result['output']) . "</pre>";
        }
    } else {
        $error = "Acción no válida.";
    }
}

// Obtener estados actuales de contenedores
$apacheRunning = isContainerRunning('mi_apache_container');
$apacheExists = containerExists('mi_apache_container');

$postgresRunning = isContainerRunning('mi_postgres_container');
$postgresExists = containerExists('mi_postgres_container');

?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </h1>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="dashboard-card text-center">
            <div class="card-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <h4>Nuevo</h4>
            <p>Agregar Empleado</p>
            <a href="personal_form.php" class="btn btn-success btn-sm">
                <i class="fas fa-plus"></i> Agregar
            </a>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="dashboard-card text-center">
            <div class="card-icon">
                <i class="fas fa-cogs"></i>
            </div>
            <h4>Config</h4>
            <p>Configuración</p>
            <a href="change_password.php" class="btn btn-warning btn-sm">
                <i class="fas fa-key"></i> Cambiar Contraseña
            </a>
        </div>
    </div>

    <div class="col-md-4">
        <div class="dashboard-card text-center">
            <div class="card-icon">
                <i class="fas fa-users"></i>
            </div>
            <h4><?php echo $totalEmpleados; ?></h4>
            <p>Total de Empleados</p>
            <a href="personal.php" class="btn btn-primary btn-sm">
                <i class="fas fa-eye"></i> Ver Todos
            </a>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-clock"></i> Actividad Reciente</h4>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    Bienvenido al Sistema de Gestión de RRHH. Desde aquí puedes administrar todo el personal de la organización.
                </div>
                
                <h5>Funcionalidades Disponibles:</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <i class="fas fa-users text-primary"></i> 
                        <strong>Gestión de Personal:</strong> Crear, editar, visualizar y eliminar registros de empleados
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-key text-warning"></i> 
                        <strong>Seguridad:</strong> Cambiar contraseña de acceso al sistema
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-database text-info"></i> 
                        <strong>Base de Datos:</strong> Todos los datos se almacenan de forma segura
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-server text-success"></i> 
                        <strong>Active Directory:</strong> Gestión completa de usuarios y políticas de dominio
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Mensajes de notificación -->
<?php if ($message || $error): ?>
<div class="row mt-4">
    <div class="col-12">
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Sección de Active Directory -->
<div class="row mt-5">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-server text-success"></i> Gestión de Active Directory</h4>
            </div>
            <div class="card-body">
                
                <!-- Configuración Inicial -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5><i class="fas fa-download"></i> Configuración Inicial</h5>
                        <div class="d-grid gap-2">
                            <form method="POST" style="display:inline;">
                                <button type="submit" name="action" value="install_ad" class="btn btn-primary btn-block">
                                    <i class="fas fa-download"></i> Instalar Active Directory
                                </button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <button type="submit" name="action" value="setup_ad" class="btn btn-info btn-block">
                                    <i class="fas fa-sitemap"></i> Configurar Estructura (OUs y Grupos)
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h5><i class="fas fa-shield-alt"></i> Configuración de Políticas</h5>
                        <div class="d-grid gap-2">
                            <form method="POST" style="display:inline;">
                                <button type="submit" name="action" value="configure_apps" class="btn btn-warning btn-block">
                                    <i class="fas fa-ban"></i> Configurar Permisos de Apps
                                </button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <button type="submit" name="action" value="configure_security" class="btn btn-danger btn-block">
                                    <i class="fas fa-lock"></i> Configurar Seguridad y Auditoría
                                </button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <button type="submit" name="action" value="configure_storage" class="btn btn-secondary btn-block">
                                    <i class="fas fa-hdd"></i> Configurar Cuotas de Almacenamiento
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- Crear Usuario de AD -->
                <div class="row">
                    <div class="col-12">
                        <h5><i class="fas fa-user-plus"></i> Crear Usuario de Active Directory</h5>
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="action" value="create_ad_user">
                            
                            <div class="col-md-4">
                                <label for="ad_username" class="form-label">Nombre de Usuario</label>
                                <input type="text" class="form-control" id="ad_username" name="ad_username" required>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="ad_password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="ad_password" name="ad_password" required>
                                <div class="form-text">Mínimo 8 caracteres, incluir mayúsculas, minúsculas, números y símbolos</div>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="ad_group" class="form-label">Grupo</label>
                                <select class="form-select" id="ad_group" name="ad_group" required>
                                    <option value="grupo1">Grupo1 (Cuates)</option>
                                    <option value="grupo2">Grupo2 (No Cuates)</option>
                                </select>
                            </div>
                            
                            <div class="col-12">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-user-plus"></i> Crear Usuario de AD
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <hr>

                <!-- Información sobre grupos -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white">
                                <h6><i class="fas fa-users"></i> Grupo1 (Cuates)</h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-0">
                                    <li><i class="fas fa-clock text-info"></i> Horario: 8:00 AM - 3:00 PM</li>
                                    <li><i class="fas fa-hdd text-warning"></i> Cuota: 5 MB</li>
                                    <li><i class="fas fa-ban text-danger"></i> Solo puede usar Notepad</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card border-warning">
                            <div class="card-header bg-warning text-dark">
                                <h6><i class="fas fa-users"></i> Grupo2 (No Cuates)</h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-0">
                                    <li><i class="fas fa-clock text-info"></i> Horario: 3:00 PM - 2:00 AM</li>
                                    <li><i class="fas fa-hdd text-warning"></i> Cuota: 10 MB</li>
                                    <li><i class="fas fa-ban text-danger"></i> Notepad bloqueado</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sección para levantar/gestionar contenedores Docker -->
<div class="row mt-5">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-cubes"></i> Gestión de Contenedores Docker</h4>
            </div>
            <div class="card-body">

                <form method="POST" class="row g-3 align-items-center mb-4">
                    <input type="hidden" name="action" value="start">
                    <div class="col-auto">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="docker_service" id="apache" value="apache" required>
                            <label class="form-check-label" for="apache">Apache</label>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="docker_service" id="postgres" value="postgres" required>
                            <label class="form-check-label" for="postgres">PostgreSQL</label>
                        </div>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Levantar Contenedor</button>
                    </div>
                </form>

                <h5>Estado Actual de Contenedores:</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Contenedor</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Apache -->
                        <tr>
                            <td>mi_apache_container</td>
                            <td>
                                <?php
                                    if (!$apacheExists) echo '<span class="badge bg-secondary">No existe</span>';
                                    elseif ($apacheRunning) echo '<span class="badge bg-success">En ejecución</span>';
                                    else echo '<span class="badge bg-warning text-dark">Detenido</span>';
                                ?>
                            </td>
                            <td>
                                <form method="POST" style="display:inline-block;">
                                    <input type="hidden" name="container_name" value="mi_apache_container">
                                    <button type="submit" name="action" value="stop" class="btn btn-sm btn-warning" <?php echo $apacheRunning ? '' : 'disabled'; ?>>Detener</button>
                                </form>
                                <form method="POST" style="display:inline-block;">
                                    <input type="hidden" name="container_name" value="mi_apache_container">
                                    <button type="submit" name="action" value="remove" class="btn btn-sm btn-danger" <?php echo $apacheExists ? '' : 'disabled'; ?>>Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <!-- Postgres -->
                        <tr>
                            <td>mi_postgres_container</td>
                            <td>
                                <?php
                                    if (!$postgresExists) echo '<span class="badge bg-secondary">No existe</span>';
                                    elseif ($postgresRunning) echo '<span class="badge bg-success">En ejecución</span>';
                                    else echo '<span class="badge bg-warning text-dark">Detenido</span>';
                                ?>
                            </td>
                            <td>
                                <form method="POST" style="display:inline-block;">
                                    <input type="hidden" name="container_name" value="mi_postgres_container">
                                    <button type="submit" name="action" value="stop" class="btn btn-sm btn-warning" <?php echo $postgresRunning ? '' : 'disabled'; ?>>Detener</button>
                                </form>
                                <form method="POST" style="display:inline-block;">
                                    <input type="hidden" name="container_name" value="mi_postgres_container">
                                    <button type="submit" name="action" value="remove" class="btn btn-sm btn-danger" <?php echo $postgresExists ? '' : 'disabled'; ?>>Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>