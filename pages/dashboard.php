<?php
// pages/dashboard.php
require_once '../includes/header.php';
require_once '../functions/personal.php';
require_once '../functions/active_directory.php';

$personal = new Personal();
$totalEmpleados = count($personal->readAll());

// Instanciar ActiveDirectoryManager
$adManager = new ActiveDirectoryManager();

$message = '';
$error = '';
$adMessage = '';
$adError = '';

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
    
    // Acciones de Docker
    if (in_array($action, ['start', 'stop', 'remove'])) {
        $service = $_POST['docker_service'] ?? null;

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
        } else {
            $error = "Acción no válida.";
        }
    }
    
    // Acciones de Active Directory
    elseif ($action === 'init_ad') {
        $result = $adManager->initializeEnvironment();
        if ($result['success']) {
            $adMessage = $result['message'] . "<br><pre>" . print_r($result['results'], true) . "</pre>";
        } else {
            $adError = $result['message'];
        }
    }
    elseif ($action === 'create_user') {
        $username = $_POST['ad_username'] ?? '';
        $password = $_POST['ad_password'] ?? '';
        $group = $_POST['ad_group'] ?? '';
        $email = $_POST['ad_email'] ?? '';
        
        if (empty($username) || empty($password) || empty($group)) {
            $adError = "Todos los campos son obligatorios para crear el usuario.";
        } else {
            $result = $adManager->createUser($username, $password, $group, $email);
            if ($result['success']) {
                $adMessage = $result['message'] . "<br><pre>" . htmlspecialchars($result['details']) . "</pre>";
            } else {
                $adError = $result['message'];
            }
        }
    }
    elseif ($action === 'get_user_info') {
        $username = $_POST['search_username'] ?? '';
        if (empty($username)) {
            $adError = "Ingrese un nombre de usuario para buscar.";
        } else {
            $result = $adManager->getUserInfo($username);
            if ($result['success']) {
                $adMessage = "Información del usuario:<br><pre>" . htmlspecialchars($result['data']) . "</pre>";
            } else {
                $adError = $result['message'];
            }
        }
    }
    elseif ($action === 'list_users') {
        $result = $adManager->listUsers();
        if ($result['success']) {
            $adMessage = "Lista de usuarios:<br><pre>" . htmlspecialchars($result['data']) . "</pre>";
        } else {
            $adError = $result['message'];
        }
    }
}

// Obtener estados actuales de Docker
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
    <div class="col-md-3">
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
    
    <div class="col-md-3">
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

    <div class="col-md-3">
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

    <div class="col-md-3">
        <div class="dashboard-card text-center">
            <div class="card-icon">
                <i class="fas fa-server"></i>
            </div>
            <h4>AD</h4>
            <p>Active Directory</p>
            <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#adModal">
                <i class="fas fa-network-wired"></i> Gestionar
            </button>
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

<!-- Sección para levantar/gestionar contenedores Docker -->
<div class="row mt-5">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-cubes"></i> Gestión de Contenedores Docker</h4>
            </div>
            <div class="card-body">

                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

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

<!-- Modal para Active Directory -->
<div class="modal fade" id="adModal" tabindex="-1" aria-labelledby="adModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="adModalLabel">
                    <i class="fas fa-server"></i> Gestión de Active Directory
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                
                <?php if ($adMessage): ?>
                    <div class="alert alert-success"><?php echo $adMessage; ?></div>
                <?php endif; ?>
                <?php if ($adError): ?>
                    <div class="alert alert-danger"><?php echo $adError; ?></div>
                <?php endif; ?>

                <!-- Pestañas para diferentes funciones -->
                <ul class="nav nav-tabs" id="adTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="init-tab" data-bs-toggle="tab" data-bs-target="#init" type="button" role="tab">
                            <i class="fas fa-play-circle"></i> Inicializar AD
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab">
                            <i class="fas fa-user-plus"></i> Crear Usuario
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="search-tab" data-bs-toggle="tab" data-bs-target="#search" type="button" role="tab">
                            <i class="fas fa-search"></i> Buscar Usuario
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="list-tab" data-bs-toggle="tab" data-bs-target="#list" type="button" role="tab">
                            <i class="fas fa-list"></i> Listar Usuarios
                        </button>
                    </li>
                </ul>

                <div class="tab-content mt-3" id="adTabsContent">
                    <!-- Inicializar AD -->
                    <div class="tab-pane fade show active" id="init" role="tabpanel">
                        <div class="card">
                            <div class="card-body">
                                <h5><i class="fas fa-cogs"></i> Inicializar Active Directory</h5>
                                <p>Esta opción instalará y configurará Active Directory con:</p>
                                <ul>
                                    <li>Dominio: <strong>15champions.com</strong></li>
                                    <li>Unidades Organizativas: <strong>Cuates</strong> y <strong>no cuates</strong></li>
                                    <li>Grupos: <strong>grupo1 (Cuates)</strong> y <strong>grupo2 (no cuates)</strong></li>
                                    <li>Políticas de aplicaciones y seguridad</li>
                                </ul>
                                <form method="POST">
                                    <input type="hidden" name="action" value="init_ad">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-rocket"></i> Inicializar Active Directory
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Crear Usuario -->
                    <div class="tab-pane fade" id="users" role="tabpanel">
                        <div class="card">
                            <div class="card-body">
                                <h5><i class="fas fa-user-plus"></i> Crear Nuevo Usuario</h5>
                                <form method="POST" class="row">
                                    <input type="hidden" name="action" value="create_user">
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="ad_username" class="form-label">Nombre de Usuario</label>
                                        <input type="text" class="form-control" id="ad_username" name="ad_username" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="ad_password" class="form-label">Contraseña</label>
                                        <input type="password" class="form-control" id="ad_password" name="ad_password" required>
                                        <div class="form-text">Mínimo 8 caracteres, mayúsculas, minúsculas, números y símbolos</div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="ad_group" class="form-label">Grupo</label>
                                        <select class="form-select" id="ad_group" name="ad_group" required>
                                            <option value="">Seleccionar grupo...</option>
                                            <option value="grupo1">grupo1 (Cuates) - Horario: 8am-3pm</option>
                                            <option value="grupo2">grupo2 (no cuates) - Horario: 3pm-2am</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="ad_email" class="form-label">Email (opcional)</label>
                                        <input type="email" class="form-control" id="ad_email" name="ad_email">
                                        <div class="form-text">Si no se especifica, será usuario@15champions.com</div>
                                    </div>
                                    
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-user-plus"></i> Crear Usuario
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Buscar Usuario -->
                    <div class="tab-pane fade" id="search" role="tabpanel">
                        <div class="card">
                            <div class="card-body">
                                <h5><i class="fas fa-search"></i> Buscar Usuario</h5>
                                <form method="POST" class="row">
                                    <input type="hidden" name="action" value="get_user_info">
                                    
                                    <div class="col-md-8 mb-3">
                                        <label for="search_username" class="form-label">Nombre de Usuario</label>
                                        <input type="text" class="form-control" id="search_username" name="search_username" required>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3 d-flex align-items-end">
                                        <button type="submit" class="btn btn-info">
                                            <i class="fas fa-search"></i> Buscar
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Listar Usuarios -->
                    <div class="tab-pane fade" id="list" role="tabpanel">
                        <div class="card">
                            <div class="card-body">
                                <h5><i class="fas fa-list"></i> Lista de Usuarios</h5>
                                <form method="POST">
                                    <input type="hidden" name="action" value="list_users">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-list"></i> Obtener Lista de Usuarios
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Mantener la pestaña activa después del submit
document.addEventListener('DOMContentLoaded', function() {
    // Si hay mensaje de AD, mostrar el modal
    <?php if ($adMessage || $adError): ?>
        var adModal = new bootstrap.Modal(document.getElementById('adModal'));
        adModal.show();
    <?php endif; ?>
});
</script>

<?php require_once '../includes/footer.php'; ?>