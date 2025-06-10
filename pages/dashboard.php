<?php
// pages/dashboard.php
require_once '../includes/header.php';
require_once '../functions/personal.php';
set_time_limit(300); // Aumentado el tiempo de ejecución para los scripts
$personal = new Personal();
$totalEmpleados = count($personal->readAll());

$message = '';
$error = '';

// Configuración SSH para Docker
$sshUser = "ruben";
$sshHost = "192.168.1.25";
$sshKey = "C:\\Users\\Administrator\\.ssh\\id_ed25519";

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
    
    // --- LÓGICA PARA CREAR USUARIO EN ACTIVE DIRECTORY ---
    if ($action === 'create_ad_user') {
        $nombreUsuario = escapeshellarg($_POST['nombre_usuario']);
        $nombreCompleto = escapeshellarg($_POST['nombre_completo']);
        $email = escapeshellarg($_POST['email']);
        $tipoUsuario = escapeshellarg($_POST['tipo_usuario']);
        $password = escapeshellarg($_POST['password']);
        $scriptPath = realpath(__DIR__ . '/../scripts/crear_ad.ps1');

        if ($scriptPath) {
            $comando = "powershell.exe -ExecutionPolicy Bypass -File \"{$scriptPath}\" " .
                       "-NombreUsuario {$nombreUsuario} " .
                       "-NombreCompleto {$nombreCompleto} " .
                       "-Email {$email} " .
                       "-TipoUsuario {$tipoUsuario} " .
                       "-Password {$password}";

            $salida = shell_exec($comando . ' 2>&1');
            
            if (stripos($salida, 'error') !== false || stripos($salida, 'Exception') !== false) {
                $error = "<strong>Error al crear usuario AD:</strong><br><pre>" . htmlspecialchars($salida) . "</pre>";
            } else {
                $message = "<strong>Resultado de creación de usuario AD:</strong><br><pre>" . htmlspecialchars($salida) . "</pre>";
            }
        } else {
            $error = "Error: No se encontró el script 'crear_ad.ps1'.";
        }
    }
    
    // --- LÓGICA PARA CONFIGURAR SERVIDOR FTP ---
    elseif ($action === 'configure_ftp') {
        $sitioFTP = escapeshellarg($_POST['sitio_ftp']);
        $rutaFTP = escapeshellarg($_POST['ruta_ftp']);
        $puerto = !empty($_POST['puerto_ftp']) ? escapeshellarg($_POST['puerto_ftp']) : null;
        $scriptPath = realpath(__DIR__ . '/../scripts/crear_ftp.ps1');

        if ($scriptPath) {
            $comando = "powershell.exe -ExecutionPolicy Bypass -File \"{$scriptPath}\" " .
                       "-SitioFTP {$sitioFTP} " .
                       "-RutaFTP {$rutaFTP}";
            
            if ($puerto) {
                $comando .= " -Puerto {$puerto}";
            }

            $salida = shell_exec($comando . ' 2>&1');
            
            if (stripos($salida, 'error') !== false || stripos($salida, 'Exception') !== false) {
                $error = "<strong>Error al configurar el servidor FTP:</strong><br><pre>" . htmlspecialchars($salida) . "</pre>";
            } else {
                $message = "<strong>Resultado de la configuración del FTP:</strong><br><pre>" . htmlspecialchars($salida) . "</pre>";
            }
        } else {
            $error = "Error: No se encontró el script 'configurar_ftp_ad.ps1'.";
        }
    }
    
    // --- LÓGICA PARA GESTIONAR DOCKER ---
    elseif ($action === 'start' && isset($_POST['docker_service'])) {
        $service = $_POST['docker_service'];
        $containerName = ($service === 'apache') ? 'mi_apache_container' : 'mi_postgres_container';
        if (isContainerRunning($containerName)) {
            $error = "El contenedor {$containerName} ya está en ejecución.";
        } else {
            if (containerExists($containerName)) {
                $output = runSSHCommand("docker start {$containerName}");
                $message = "Contenedor {$containerName} iniciado:<br><pre>" . htmlspecialchars($output) . "</pre>";
            } else {
                $command = ($service === 'apache') ? "docker run -d --name {$containerName} httpd" : "docker run -d --name {$containerName} -e POSTGRES_PASSWORD=mi_password postgres";
                $output = runSSHCommand($command);
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
            if (isContainerRunning($containerName)) runSSHCommand("docker stop {$containerName}");
            $output = runSSHCommand("docker rm {$containerName}");
            $message = "Contenedor {$containerName} eliminado:<br><pre>" . htmlspecialchars($output) . "</pre>";
        } else {
            $error = "El contenedor {$containerName} no existe.";
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
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="dashboard-card text-center h-100">
            <div class="card-icon"><i class="fas fa-user-plus"></i></div>
            <h4>Nuevo Empleado (DB)</h4>
            <p>Agregar a la base de datos local</p>
            <a href="personal_form.php" class="btn btn-success btn-sm mt-auto"><i class="fas fa-plus"></i> Agregar</a>
        </div>
    </div>
    
    
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="dashboard-card text-center h-100">
            <div class="card-icon"><i class="fas fa-server"></i></div>
            <h4>Configurar FTP</h4>
            <p>Crear sitio FTP con auth de AD</p>
            <button type="button" class="btn btn-secondary btn-sm mt-auto" data-bs-toggle="modal" data-bs-target="#configurarFTPModal">
                <i class="fas fa-cogs"></i> Configurar Servidor
            </button>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="dashboard-card text-center h-100">
            <div class="card-icon"><i class="fas fa-users"></i></div>
            <h4><?php echo $totalEmpleados; ?></h4>
            <p>Total de Empleados</p>
            <a href="personal.php" class="btn btn-primary btn-sm mt-auto"><i class="fas fa-eye"></i> Ver Todos</a>
        </div>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>


<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-cubes"></i> Gestión de Contenedores Docker</h4>
            </div>
            <div class="card-body">

                <form method="POST" class="row g-3 align-items-center mb-4">
                    <div class="col-auto">
                         <label for="docker_service" class="visually-hidden">Servicio Docker</label>
                    </div>
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
                        <button type="submit" name="action" value="start" class="btn btn-primary">Levantar Contenedor</button>
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


<div class="modal fade" id="crearUsuarioADModal" tabindex="-1" aria-labelledby="crearUsuarioADModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="crearUsuarioADModalLabel"><i class="fas fa-user-tie"></i> Crear Nuevo Usuario en Active Directory</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="dashboard.php" method="POST">
        <div class="modal-body">
            <p class="text-muted mb-4">Completa el formulario para registrar un nuevo usuario en AD.</p>
            <input type="hidden" name="action" value="create_ad_user">
            <div class="mb-3">
                <label for="ad_nombre_usuario" class="form-label">Nombre de Usuario (SamAccountName)</label>
                <input type="text" class="form-control" id="ad_nombre_usuario" name="nombre_usuario" placeholder="ej. juan.perez" required>
            </div>
            <div class="mb-3">
                <label for="ad_nombre_completo" class="form-label">Nombre Completo</label>
                <input type="text" class="form-control" id="ad_nombre_completo" name="nombre_completo" placeholder="ej. Juan Pérez" required>
            </div>
            <div class="mb-3">
                <label for="ad_email" class="form-label">Correo Electrónico</label>
                <input type="email" class="form-control" id="ad_email" name="email" placeholder="ej. juan.perez@dominio.com" required>
            </div>
            <div class="mb-3">
                <label for="ad_tipo_usuario" class="form-label">Tipo de Usuario</label>
                <select class="form-select" id="ad_tipo_usuario" name="tipo_usuario" required>
                    <option value="cuates">Cuates</option>
                    <option value="no cuates">No Cuates</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="ad_password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="ad_password" name="password" placeholder="••••••••" required>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Crear Usuario</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="configurarFTPModal" tabindex="-1" aria-labelledby="configurarFTPModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="configurarFTPModalLabel"><i class="fas fa-server"></i> Configurar Servidor FTP con Active Directory</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="dashboard.php" method="POST">
        <div class="modal-body">
            <p class="text-muted mb-4">Completa el formulario para configurar un nuevo sitio FTP.</p>
            <input type="hidden" name="action" value="configure_ftp">
            <div class="mb-3">
                <label for="ftp_sitio_ftp" class="form-label">Nombre del Sitio FTP</label>
                <input type="text" class="form-control" id="ftp_sitio_ftp" name="sitio_ftp" placeholder="ej. MiSitioFTP" value="Default FTP Site" required>
            </div>
            <div class="mb-3">
                <label for="ftp_ruta_ftp" class="form-label">Ruta Física (Directorio Raíz)</label>
                <input type="text" class="form-control" id="ftp_ruta_ftp" name="ruta_ftp" placeholder="ej. C:\ftp_root" value="C:\Users\Administrator\Documents\ftp_users" required>
            </div>
            <div class="mb-3">
                <label for="ftp_puerto_ftp" class="form-label">Puerto (Opcional)</label>
                <input type="number" class="form-control" id="ftp_puerto_ftp" name="puerto_ftp" placeholder="Dejar en blanco para usar el puerto 21">
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-cogs"></i> Configurar Ahora</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>