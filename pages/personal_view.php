<?php
// pages/personal_view.php
require_once '../includes/header.php';
require_once '../functions/personal.php';

$personal = new Personal();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: personal.php');
    exit();
}

$empleado = $personal->readOne($_GET['id']);
if (!$empleado) {
    header('Location: personal.php');
    exit();
}

$created = isset($_GET['created']);
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>
                <i class="fas fa-user"></i> 
                <?php echo htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellido']); ?>
            </h1>
            <div class="btn-group">
                <a href="personal_form.php?id=<?php echo $empleado['id']; ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <a href="personal.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
    </div>
</div>

<?php if ($created): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i>
        ¡Empleado creado exitosamente! Aquí puedes ver todos sus detalles.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-info-circle"></i> Información Personal</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted">
                                <i class="fas fa-user"></i> Nombre Completo
                            </label>
                            <div class="fw-bold fs-5">
                                <?php echo htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellido']); ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted">
                                <i class="fas fa-envelope"></i> Email
                            </label>
                            <div>
                                <a href="mailto:<?php echo htmlspecialchars($empleado['email']); ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($empleado['email']); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted">
                                <i class="fas fa-phone"></i> Teléfono
                            </label>
                            <div>
                                <?php echo $empleado['telefono'] ? htmlspecialchars($empleado['telefono']) : '<span class="text-muted">No especificado</span>'; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted">
                                <i class="fas fa-calendar"></i> Fecha de Ingreso
                            </label>
                            <div>
                                <?php 
                                if ($empleado['fecha_ingreso']) {
                                    $fecha = new DateTime($empleado['fecha_ingreso']);
                                    echo $fecha->format('d/m/Y');
                                    
                                    // Calcular antigüedad
                                    $hoy = new DateTime();
                                    $antiguedad = $hoy->diff($fecha);
                                    echo ' <small class="text-muted">(' . $antiguedad->y . ' años, ' . $antiguedad->m . ' meses)</small>';
                                } else {
                                    echo '<span class="text-muted">No especificada</span>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-briefcase"></i> Información Laboral</h4>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted">
                        <i class="fas fa-building"></i> Departamento
                    </label>
                    <div>
                        <span class="badge bg-primary fs-6">
                            <?php echo htmlspecialchars($empleado['departamento']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted">
                        <i class="fas fa-user-tie"></i> Puesto
                    </label>
                    <div class="fw-bold">
                        <?php echo htmlspecialchars($empleado['puesto']); ?>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted">
                        <i class="fas fa-dollar-sign"></i> Salario
                    </label>
                    <div class="fw-bold text-success fs-5">
                        <?php 
                        if ($empleado['salario']) {
                            echo '$' . number_format($empleado['salario'], 2, '.', ',');
                        } else {
                            echo '<span class="text-muted">No especificado</span>';
                        }
                        ?>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted">
                        <i class="fas fa-toggle-on"></i> Estado
                    </label>
                    <div>
                        <span class="badge bg-success">Activo</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h4><i class="fas fa-cogs"></i> Acciones</h4>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="personal_form.php?id=<?php echo $empleado['id']; ?>" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Editar Información
                    </a>
                    <button type="button" class="btn btn-info" onclick="window.print()">
                        <i class="fas fa-print"></i> Imprimir Perfil
                    </button>
                    <a href="personal.php?delete=<?php echo $empleado['id']; ?>" 
                       class="btn btn-danger"
                       onclick="return confirmDelete('<?php echo htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellido']); ?>')">
                        <i class="fas fa-trash"></i> Eliminar Empleado
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-clock"></i> Información del Sistema</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <small class="text-muted">
                            <i class="fas fa-plus-circle"></i> Creado: 
                            <?php echo date('d/m/Y H:i', strtotime($empleado['created_at'])); ?>
                        </small>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">
                            <i class="fas fa-edit"></i> Última actualización: 
                            <?php echo date('d/m/Y H:i', strtotime($empleado['updated_at'])); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>