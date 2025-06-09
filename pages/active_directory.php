<?php
// pages/active_directory.php
require_once '../includes/header.php';
require_once '../functions/active_directory.php';

$adManager = new ActiveDirectoryManager();
$message = '';
$error = '';

// Manejar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    
    switch ($action) {
        case 'init_ad':
            $result = $adManager->initializeEnvironment();
            if ($result['success']) {
                $message = $result['message'] . "<br><details><summary>Ver detalles</summary><pre>" . print_r($result['results'], true) . "</pre></details>";
            } else {
                $error = $result['message'];
            }
            break;
            
        case 'install_ad':
            $result = $adManager->installAndConfigureAD();
            if ($result['success']) {
                $message = $result['message'] . "<br><details><summary>Ver detalles</summary><pre>" . htmlspecialchars($result['details']) . "</pre></details>";
            } else {
                $error = $result['message'];
            }
            break;
            
        case 'create_user':
            $username = $_POST['ad_username'] ?? '';
            $password = $_POST['ad_password'] ?? '';
            $group = $_POST['ad_group'] ?? '';
            $email = $_POST['ad_email'] ?? '';
            
            if (empty($username) || empty($password) || empty($group)) {
                $error = "Todos los campos son obligatorios para crear el usuario.";
            } else {
                $result = $adManager->createUser($username, $password, $group, $email);
                if ($result['success']) {
                    $message = $result['message'] . "<br><details><summary>Ver detalles</summary><pre>" . htmlspecialchars($result['details']) . "</pre></details>";
                } else {
                    $error = $result['message'];
                }
            }
            break;
            
        case 'get_user_info':
            $username = $_POST['search_username'] ?? '';
            if (empty($username)) {
                $error = "Ingrese un nombre de usuario para buscar.";
            } else {
                $result = $adManager->getUserInfo($username);
                if ($result['success']) {
                    $message = "Información del usuario:<br><pre>" . htmlspecialchars($result['data']) . "</pre>";
                } else {
                    $error = $result['message'];
                }
            }
            break;
            
        case 'list_users':
            $result = $adManager->listUsers();
            if ($result['success']) {
                $message = "Lista de usuarios:<br><pre>" . htmlspecialchars($result['data']) . "</pre>";
            } else {
                $error = $result['message'];
            }
            break;
            
        case 'create_ou':
            $result = $adManager->createOrganizationalUnits();
            if ($result['success']) {
                $message = $result['message'] . "<br><details><summary>Ver detalles</summary><pre>" . htmlspecialchars($result['details']) . "</pre></details>";
            } else {
                $error = $result['message'];
            }
            break;
            
        case 'config_app_policies':
            $result = $adManager->configureApplicationPolicies();
            if ($result['success']) {
                $message = $result['message'] . "<br><details><summary>Ver detalles</summary><pre>" . htmlspecialchars($result['details']) . "</pre></details>";
            } else {
                $error = $result['message'];
            }
            break;
            
        case 'config_security':
            $result = $adManager->configureSecurityPolicies();
            if ($result['success']) {
                $message = $result['message'] . "<br><details><summary>Ver detalles</summary><pre>" . htmlspecialchars($result['details']) . "</pre></details>";
            } else {
                $error = $result['message'];
            }
            break;
    }
}
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">
            <i class="fas fa-server"></i> Gestión de Active Directory
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Active Directory</li>
            </ol>
        </nav>
    </div>
</div>

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

<!-- Configuración Inicial -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-rocket"></i> Configuración Inicial del Dominio</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-primary">
                            <div class="card-body text-center">
                                <i class="fas fa-play-circle fa-3x text-primary mb-3"></i>
                                <h5>Inicialización Completa</h5>
                                <p>Instala AD, crea OUs, configura grupos y políticas</p>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="action" value="init_ad">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-rocket"></i> Inicializar Todo
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-info">
                            <div class="card-body text-center">
                                <i class="fas fa-download fa-3x text-info mb-3"></i>
                                <h5>Solo Instalar AD</h5>
                                <p>Instala y configura Active Directory únicamente</p>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="action" value="install_ad">
                                    <button type="submit" class="btn btn-info btn-lg">
                                        <i class="fas fa-download"></i> Instalar AD
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Gestión de Usuarios -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-user-plus"></i> Crear Usuario</h4>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="create_user">
                    
                    <div class="mb-3">
                        <label for="ad_username" class="form-label">Nombre de Usuario <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="ad_username" name="ad_username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="ad_password" class="form-label">Contraseña <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="ad_password" name="ad_password" required>
                        <div class="form-text">
                            Mínimo 8 caracteres, mayúsculas, minúsculas, números y símbolos
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="ad_group" class="form-label">Grupo <span class="text-danger">*</span></label>
                        <select class="form-select" id="ad_group" name="ad_group" required>
                            <option value="">Seleccionar grupo...</option>
                            <option value="grupo1">grupo1 (Cuates) - Horario: 8am-3pm</option>
                            <option value="grupo2">grupo2 (no cuates) - Horario: 3pm-2am</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="ad_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="ad_email" name="ad_email">
                        <div class="form-text">
                            Si se deja vacío, se usará: usuario@15champions.com
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-user-plus"></i> Crear Usuario
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-search"></i> Buscar Usuario</h4>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="get_user_info">
                    
                    <div class="mb-3">
                        <label for="search_username" class="form-label">Nombre de Usuario</label>
                        <input type="text" class="form-control" id="search_username" name="search_username" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </form>
                
                <hr>
                
                <form method="post" class="d-inline">
                    <input type="hidden" name="action" value="list_users">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="fas fa-list"></i> Listar Todos los Usuarios
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Configuración Avanzada -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-cogs"></i> Configuración Avanzada</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card border-warning h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-sitemap fa-3x text-warning mb-3"></i>
                                <h5>Unidades Organizativas</h5>
                                <p>Crear OUs y grupos del dominio</p>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="action" value="create_ou">
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-plus"></i> Crear OUs
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card border-success h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-desktop fa-3x text-success mb-3"></i>
                                <h5>Políticas de Aplicaciones</h5>
                                <p>Configurar permisos de aplicaciones por grupo</p>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="action" value="config_app_policies">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-shield-alt"></i> Configurar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card border-danger h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-lock fa-3x text-danger mb-3"></i>
                                <h5>Políticas de Seguridad</h5>
                                <p>Configurar auditoría y contraseñas seguras</p>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="action" value="config_security">
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-lock"></i> Configurar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Información del Dominio -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-info-circle"></i> Información del Dominio</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="bg-light p-3 rounded text-center">
                            <h6 class="text-muted">Dominio</h6>
                            <strong>15champions.com</strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="bg-light p-3 rounded text-center">
                            <h6 class="text-muted">NetBIOS</h6>
                            <strong>15CHAMPIONS</strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="bg-light p-3 rounded text-center">
                            <h6 class="text-muted">Grupos</h6>
                            <strong>grupo1 (Cuates)<br>grupo2 (no cuates)</strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="bg-light p-3 rounded text-center">
                            <h6 class="text-muted">Horarios</h6>
                            <strong>8am-3pm / 3pm-2am</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Notas importantes -->
<div class="row">
    <div class="col-12">
        <div class="alert alert-info">
            <h5><i class="fas fa-exclamation-triangle"></i> Notas Importantes:</h5>
            <ul class="mb-0">
                <li><strong>Contraseñas:</strong> Deben tener mínimo 8 caracteres con mayúsculas, minúsculas, números y símbolos especiales</li>
                <li><strong>Grupo1 (Cuates):</strong> Solo pueden usar Bloc de Notas, horario de 8am a 3pm</li>
                <li><strong>Grupo2 (no cuates):</strong> Pueden usar todo excepto Bloc de Notas, horario de 3pm a 2am</li>
                <li><strong>Dominio:</strong> 15champions.com con DNS integrado</li>
                <li><strong>Auditoría:</strong> Se registran todos los accesos y cambios en el directorio</li>
            </ul>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>