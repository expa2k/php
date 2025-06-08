<?php
// pages/change_password.php
require_once '../includes/header.php';
require_once '../functions/auth.php';

$auth = new Auth();
$message = '';
$messageType = '';
$errors = [];

// Verificar que el usuario esté logueado
if (!$auth->isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}

// Procesar cambio de contraseña
if ($_POST) {
    $currentPassword = trim($_POST['current_password'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    
    // Validaciones
    if (empty($currentPassword)) {
        $errors[] = "La contraseña actual es requerida";
    }
    
    if (empty($newPassword)) {
        $errors[] = "La nueva contraseña es requerida";
    }
    
    if (empty($confirmPassword)) {
        $errors[] = "La confirmación de contraseña es requerida";
    }
    
    if ($newPassword !== $confirmPassword) {
        $errors[] = "Las contraseñas nuevas no coinciden";
    }
    
    if (strlen($newPassword) < 6) {
        $errors[] = "La nueva contraseña debe tener al menos 6 caracteres";
    }
    
    if ($currentPassword === $newPassword) {
        $errors[] = "La nueva contraseña debe ser diferente a la actual";
    }
    
    // Si no hay errores, procesar el cambio
    if (empty($errors)) {
        $username = $_SESSION['username'];
        
        if ($auth->changePassword($username, $currentPassword, $newPassword)) {
            $message = "Contraseña cambiada exitosamente";
            $messageType = "success";
            
            // Limpiar el formulario
            $_POST = [];
        } else {
            $message = "Error: La contraseña actual es incorrecta";
            $messageType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 1.5rem;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
            transform: translateY(-1px);
        }
        .alert {
            border: none;
            border-radius: 10px;
        }
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
        }
        .input-group {
            position: relative;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <h4 class="mb-0">
                            <i class="fas fa-key me-2"></i>
                            Cambiar Contraseña
                        </h4>
                    </div>
                    <div class="card-body p-4">
                        
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                                <?php echo htmlspecialchars($message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" id="changePasswordForm">
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Contraseña Actual
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="current_password" name="current_password" 
                                           required placeholder="Ingresa tu contraseña actual">
                                    <span class="password-toggle" onclick="togglePassword('current_password')">
                                        <i class="far fa-eye" id="current_password_eye"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label">
                                    <i class="fas fa-key me-2"></i>Nueva Contraseña
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="new_password" name="new_password" 
                                           required placeholder="Ingresa tu nueva contraseña" minlength="6">
                                    <span class="password-toggle" onclick="togglePassword('new_password')">
                                        <i class="far fa-eye" id="new_password_eye"></i>
                                    </span>
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    La contraseña debe tener al menos 6 caracteres
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">
                                    <i class="fas fa-check-double me-2"></i>Confirmar Nueva Contraseña
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                           required placeholder="Confirma tu nueva contraseña">
                                    <span class="password-toggle" onclick="togglePassword('confirm_password')">
                                        <i class="far fa-eye" id="confirm_password_eye"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    Cambiar Contraseña
                                </button>
                                <a href="dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Volver al Dashboard
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const eye = document.getElementById(fieldId + '_eye');
            
            if (field.type === 'password') {
                field.type = 'text';
                eye.classList.remove('fa-eye');
                eye.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                eye.classList.remove('fa-eye-slash');
                eye.classList.add('fa-eye');
            }
        }

        // Validación en tiempo real
        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
                return false;
            }
            
            if (newPassword.length < 6) {
                e.preventDefault();
                alert('La contraseña debe tener al menos 6 caracteres');
                return false;
            }
        });

        // Validación visual en tiempo real
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && newPassword !== confirmPassword) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });

        // Auto-hide alerts después de 5 segundos
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>

<?php require_once '../includes/footer.php'; ?>