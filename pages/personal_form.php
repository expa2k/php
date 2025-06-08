<?php
// pages/personal_form.php
require_once '../includes/header.php';
require_once '../functions/personal.php';

$personal = new Personal();
$isEdit = isset($_GET['id']) && is_numeric($_GET['id']);
$empleado = null;
$errors = [];
$message = '';
$messageType = '';

// Si es edición, obtener datos del empleado
if ($isEdit) {
    $empleado = $personal->readOne($_GET['id']);
    if (!$empleado) {
        header('Location: personal.php');
        exit();
    }
}

// Procesar formulario
if ($_POST) {
    $data = [
        'nombre' => trim($_POST['nombre']),
        'apellido' => trim($_POST['apellido']),
        'email' => trim($_POST['email']),
        'telefono' => trim($_POST['telefono']),
        'departamento' => trim($_POST['departamento']),
        'puesto' => trim($_POST['puesto']),
        'fecha_ingreso' => $_POST['fecha_ingreso'] ?: null,
        'salario' => floatval(str_replace(',', '', $_POST['salario']))
    ];
    
    // Validaciones
    if (empty($data['nombre'])) {
        $errors[] = "El nombre es obligatorio.";
    }
    if (empty($data['apellido'])) {
        $errors[] = "El apellido es obligatorio.";
    }
    if (empty($data['email'])) {
        $errors[] = "El email es obligatorio.";
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El email no es válido.";
    } elseif ($personal->emailExists($data['email'], $isEdit ? $_GET['id'] : null)) {
        $errors[] = "Ya existe un empleado con este email.";
    }
    if (empty($data['departamento'])) {
        $errors[] = "El departamento es obligatorio.";
    }
    if (empty($data['puesto'])) {
        $errors[] = "El puesto es obligatorio.";
    }
    
    // Si no hay errores, procesar
    if (empty($errors)) {
        if ($isEdit) {
            if ($personal->update($_GET['id'], $data)) {
                $message = "Empleado actualizado correctamente.";
                $messageType = 'success';
                $empleado = $personal->readOne($_GET['id']); // Recargar datos
            } else {
                $message = "Error al actualizar el empleado.";
                $messageType = 'danger';
            }
        } else {
            $newId = $personal->create($data);
            if ($newId) {
                header('Location: personal_view.php?id=' . $newId . '&created=1');
                exit();
            } else {
                $message = "Error al crear el empleado.";
                $messageType = 'danger';
            }
        }
    }
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>
                <i class="fas fa-<?php echo $isEdit ? 'user-edit' : 'user-plus'; ?>"></i>
                <?php echo $isEdit ? 'Editar Empleado' : 'Nuevo Empleado'; ?>
            </h1>
            <a href="personal.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
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

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger" role="alert">
        <h6><i class="fas fa-exclamation-triangle"></i> Errores encontrados:</h6>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h4>
            <i class="fas fa-form"></i> 
            <?php echo $isEdit ? 'Datos del Empleado' : 'Información del Nuevo Empleado'; ?>
        </h4>
    </div>
    <div class="card-body">
        <form method="POST" onsubmit="return validatePersonalForm()">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">
                            <i class="fas fa-user"></i> Nombre *
                        </label>
                        <input type="text" class="form-control" id="nombre" name="nombre" 
                               value="<?php echo htmlspecialchars($empleado['nombre'] ?? $_POST['nombre'] ?? ''); ?>" 
                               required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="apellido" class="form-label">
                            <i class="fas fa-user"></i> Apellido *
                        </label>
                        <input type="text" class="form-control" id="apellido" name="apellido" 
                               value="<?php echo htmlspecialchars($empleado['apellido'] ?? $_POST['apellido'] ?? ''); ?>" 
                               required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i> Email *
                        </label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($empleado['email'] ?? $_POST['email'] ?? ''); ?>" 
                               required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="telefono" class="form-label">
                            <i class="fas fa-phone"></i> Teléfono
                        </label>
                        <input type="tel" class="form-control" id="telefono" name="telefono" 
                               value="<?php echo htmlspecialchars($empleado['telefono'] ?? $_POST['telefono'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="departamento" class="form-label">
                            <i class="fas fa-building"></i> Departamento *
                        </label>
                        <select class="form-control" id="departamento" name="departamento" required>
                            <option value="">Seleccionar departamento</option>
                            <?php 
                            $departamentos = ['IT', 'RRHH', 'Ventas', 'Marketing', 'Finanzas', 'Operaciones', 'Legal'];
                            $selectedDept = $empleado['departamento'] ?? $_POST['departamento'] ?? '';
                            foreach ($departamentos as $dept): 
                            ?>
                                <option value="<?php echo $dept; ?>" <?php echo $selectedDept === $dept ? 'selected' : ''; ?>>
                                    <?php echo $dept; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="puesto" class="form-label">
                            <i class="fas fa-briefcase"></i> Puesto *
                        </label>
                        <input type="text" class="form-control" id="puesto" name="puesto" 
                               value="<?php echo htmlspecialchars($empleado['puesto'] ?? $_POST['puesto'] ?? ''); ?>" 
                               required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="fecha_ingreso" class="form-label">
                            <i class="fas fa-calendar"></i> Fecha de Ingreso
                        </label>
                        <input type="date" class="form-control" id="fecha_ingreso" name="fecha_ingreso" 
                               value="<?php echo $empleado['fecha_ingreso'] ?? $_POST['fecha_ingreso'] ?? ''; ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="salario" class="form-label">
                            <i class="fas fa-dollar-sign"></i> Salario
                        </label>
                        <input type="text" class="form-control" id="salario" name="salario" 
                               value="<?php echo $empleado['salario'] ? number_format($empleado['salario'], 2) : ($_POST['salario'] ?? ''); ?>"
                               placeholder="0.00" onblur="formatSalary(this)" onfocus="cleanSalaryFormat(this)">
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between">
                        <a href="personal.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 
                            <?php echo $isEdit ? 'Actualizar' : 'Guardar'; ?> Empleado
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>