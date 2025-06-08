<?php
// pages/personal.php
require_once '../includes/header.php';
require_once '../functions/personal.php';

$personal = new Personal();
$empleados = $personal->readAll();

$message = '';
$messageType = '';

// Procesar eliminación
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $empleado = $personal->readOne($_GET['delete']);
    if ($empleado && $personal->delete($_GET['delete'])) {
        $message = "Empleado {$empleado['nombre']} {$empleado['apellido']} eliminado correctamente.";
        $messageType = 'success';
        $empleados = $personal->readAll(); // Recargar lista
    } else {
        $message = "Error al eliminar el empleado.";
        $messageType = 'danger';
    }
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-users"></i> Gestión de Personal</h1>
            <a href="personal_form.php" class="btn btn-success">
                <i class="fas fa-user-plus"></i> Nuevo Empleado
            </a>
        </div>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h4><i class="fas fa-list"></i> Lista de Empleados (<?php echo count($empleados); ?>)</h4>
    </div>
    <div class="card-body">
        <?php if (empty($empleados)): ?>
            <div class="text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay empleados registrados</h5>
                <p class="text-muted">Comienza agregando tu primer empleado</p>
                <a href="personal_form.php" class="btn btn-success">
                    <i class="fas fa-user-plus"></i> Agregar Primer Empleado
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre Completo</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Departamento</th>
                            <th>Puesto</th>
                            <th>Fecha Ingreso</th>
                            <th>Salario</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empleados as $empleado): ?>
                        <tr>
                            <td><?php echo $empleado['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellido']); ?></strong>
                            </td>
                            <td>
                                <a href="mailto:<?php echo htmlspecialchars($empleado['email']); ?>">
                                    <?php echo htmlspecialchars($empleado['email']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($empleado['telefono'] ?: 'N/A'); ?></td>
                            <td>
                                <span class="badge bg-secondary">
                                    <?php echo htmlspecialchars($empleado['departamento']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($empleado['puesto']); ?></td>
                            <td>
                                <?php 
                                if ($empleado['fecha_ingreso']) {
                                    echo date('d/m/Y', strtotime($empleado['fecha_ingreso']));
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                if ($empleado['salario']) {
                                    echo '$' . number_format($empleado['salario'], 2, '.', ',');
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="personal_view.php?id=<?php echo $empleado['id']; ?>" 
                                       class="btn btn-info btn-sm" title="Ver">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="personal_form.php?id=<?php echo $empleado['id']; ?>" 
                                       class="btn btn-warning btn-sm" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?php echo $empleado['id']; ?>" 
                                       class="btn btn-danger btn-sm" title="Eliminar"
                                       onclick="return confirmDelete('<?php echo htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellido']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>