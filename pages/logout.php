<?php
// pages/logout.php
require_once '../functions/auth.php';

// Crear instancia de Auth
$auth = new Auth();

// Ejecutar logout
if ($auth->logout()) {
    // Logout exitoso, redirigir al login
    header('Location: ../index.php?message=logout_success');
    exit();
} else {
    // Si hay algún error (poco probable)
    header('Location: ../pages/dashboard.php?error=logout_failed');
    exit();
}
?>