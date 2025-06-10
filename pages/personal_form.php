<?php
// pages/personal_form.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

    // Datos adicionales para AD (solo para usuarios nuevos)
    $adData = [];
    if (!$isEdit) {
        $adData = [
            'tipo_usuario' => trim($_POST['tipo_usuario']),
            'password' => trim($_POST['password'])
        ];
    }

    // Validaciones básicas
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

    // Validaciones adicionales para AD (solo para usuarios nuevos)
    if (!$isEdit) {
        if (empty($adData['tipo_usuario'])) {
            $errors[] = "El tipo de usuario es obligatorio.";
        }
        if (empty($adData['password'])) {
            $errors[] = "La contraseña es obligatoria.";
        } elseif (strlen($adData['password']) < 8) {
            $errors[] = "La contraseña debe tener al menos 8 caracteres.";
        }
    }

    // Si no hay errores, procesar
    if (empty($errors)) {
        if (!$isEdit) {
            // === CREAR USUARIO NUEVO ===

            // 1. Generar nombre de usuario
            $nombreUsuario = strtolower($data['nombre'][0] . $data['apellido']);

            // 2. Crear usuario en Active Directory
            $adSuccess = false;
            $adOutput = '';

            // Ruta al script de PowerShell
            $scriptPath = realpath(__DIR__ . '/../scripts/crear_ad.ps1');

            if ($scriptPath) {
                // Preparar datos para AD
                $nombreUsuarioEscaped = escapeshellarg($nombreUsuario);
                $nombreCompletoEscaped = escapeshellarg($data['nombre'] . ' ' . $data['apellido']);
                $emailEscaped = escapeshellarg($data['email']);
                $tipoUsuarioEscaped = escapeshellarg($adData['tipo_usuario']);
                $passwordEscaped = escapeshellarg($adData['password']);

                // Construir y ejecutar el comando de PowerShell
                $comando = "powershell.exe -ExecutionPolicy Bypass -File \"{$scriptPath}\" " .
                    "-NombreUsuario {$nombreUsuarioEscaped} " .
                    "-NombreCompleto {$nombreCompletoEscaped} " .
                    "-Email {$emailEscaped} " .
                    "-TipoUsuario {$tipoUsuarioEscaped} " .
                    "-Password {$passwordEscaped}";

                $adOutput = shell_exec($comando . ' 2>&1');

                // Verificar si la creación fue exitosa
                if (stripos($adOutput, 'error') === false && !empty($adOutput)) {
                    $adSuccess = true;
                } else {
                    $errors[] = "Error al crear usuario en Active Directory: " . htmlspecialchars($adOutput);
                }
            } else {
                $errors[] = "Error: No se encontró el script 'crear_ad.ps1' en la ruta esperada.";
            }

            // 3. Si AD fue exitoso, crear empleado en BD y enviar credenciales
            if ($adSuccess && empty($errors)) {
                // Crear empleado en base de datos
                $newId = $personal->create($data);

                if ($newId) {
                    // Enviar credenciales por correo
                    require_once __DIR__ . '/../includes/send_email.php';

                    $emailSent = enviarCredenciales(
                        $data['nombre'],
                        $data['apellido'],
                        $data['email'],
                        $nombreUsuario,
                        $adData['password'],
                        $data['puesto']
                    );

                    if ($emailSent) {
                        $message = "¡Usuario creado exitosamente!<br>" .
                            "✓ Empleado registrado en base de datos<br>" .
                            "✓ Usuario creado en Active Directory<br>" .
                            "✓ Credenciales enviadas a: " . $data['email'] . "<br><br>" .
                            "<strong>Resultado AD:</strong><br><pre>" . htmlspecialchars($adOutput) . "</pre>";
                        $messageType = 'success';
                    } else {
                        $message = "Usuario creado pero hubo problemas con el envío del correo.<br>" .
                            "✓ Empleado registrado en base de datos<br>" .
                            "✓ Usuario creado en Active Directory<br>" .
                            "⚠ Error al enviar credenciales por correo<br><br>" .
                            "<strong>Resultado AD:</strong><br><pre>" . htmlspecialchars($adOutput) . "</pre>";
                        $messageType = 'warning';
                    }
                } else {
                    $errors[] = "Error al crear el empleado en la base de datos.";
                }
            }

        } else {
            // === EDITAR EMPLEADO EXISTENTE ===
            if ($personal->update($_GET['id'], $data)) {
                $message = "Empleado actualizado correctamente.";
                $messageType = 'success';
                $empleado = $personal->readOne($_GET['id']);
            } else {
                $message = "Error al actualizar el empleado.";
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
                    <?php echo $isEdit ? 'Editar Empleado' : 'Crear Nuevo Usuario'; ?>
                </h1>
                <a href="personal.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
    </div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'times-circle'); ?>"></i>
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
                <?php echo $isEdit ? 'Datos del Empleado' : 'Información del Nuevo Usuario'; ?>
            </h4>
            <?php if (!$isEdit): ?>
                <p class="text-muted mb-0">Este formulario creará el empleado en la base de datos, el usuario en Active Directory y enviará las credenciales por correo.</p>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <form method="POST" onsubmit="return validatePersonalForm()">

                <!-- Información Personal -->
                <div class="row">
                    <div class="col-12">
                        <h5 class="text-primary mb-3">
                            <i class="fas fa-user"></i> Información Personal
                        </h5>
                    </div>
                </div>

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
                            <?php if (!$isEdit): ?>
                                <small class="form-text text-muted">Las credenciales se enviarán a este correo</small>
                            <?php endif; ?>
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

                <!-- Información Laboral -->
                <div class="row mt-4">
                    <div class="col-12">
                        <h5 class="text-primary mb-3">
                            <i class="fas fa-briefcase"></i> Información Laboral
                        </h5>
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
                                   value="<?php echo isset($empleado['salario']) && $empleado['salario'] ? number_format($empleado['salario'], 2) : ($_POST['salario'] ?? ''); ?>"
                                   placeholder="0.00" onblur="formatSalary(this)" onfocus="cleanSalaryFormat(this)">
                        </div>
                    </div>
                </div>

                <?php if (!$isEdit): ?>
                    <!-- Configuración de Active Directory -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5 class="text-primary mb-3">
                                <i class="fas fa-server"></i> Configuración de Active Directory
                            </h5>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="tipo_usuario" class="form-label">
                                    <i class="fas fa-users"></i> Tipo de Usuario *
                                </label>
                                <select class="form-select" id="tipo_usuario" name="tipo_usuario" required>
                                    <option value="">Seleccionar tipo</option>
                                    <option value="cuates" <?php echo ($_POST['tipo_usuario'] ?? '') === 'cuates' ? 'selected' : ''; ?>>Cuates</option>
                                    <option value="no cuates" <?php echo ($_POST['tipo_usuario'] ?? '') === 'no cuates' ? 'selected' : ''; ?>>No Cuates</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock"></i> Contraseña *
                                </label>
                                <input type="password" class="form-control" id="password" name="password"
                                       placeholder="Mínimo 8 caracteres" required minlength="8">
                                <small class="form-text text-muted">Esta será la contraseña para el usuario de Active Directory</small>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Información importante:</strong><br>
                        • El nombre de usuario se generará automáticamente como: <code>primera_letra_nombre + apellido</code><br>
                        • Se creará el usuario en Active Directory con los datos proporcionados<br>
                        • Las credenciales se enviarán automáticamente al correo electrónico especificado
                    </div>
                <?php endif; ?>

                <div class="row mt-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between">
                            <a href="personal.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                <?php echo $isEdit ? 'Actualizar Empleado' : 'Crear Usuario Completo'; ?>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

<?php require_once '../includes/footer.php'; ?>