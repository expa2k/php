<?php
// pages/dashboard.php
require_once '../includes/header.php';
require_once '../functions/personal.php';

$personal = new Personal();
$totalEmpleados = count($personal->readAll());
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
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>4">
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
    
    <div class="col-md-