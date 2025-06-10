<?php
// includes/send_email.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Incluye las clases de PHPMailer - ajusta las rutas según tu estructura
require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

/**
 * Envía credenciales de acceso al correo del empleado
 */
function enviarCredenciales($nombre, $apellido, $email, $usuario, $password, $puesto) {
    $mail = new PHPMailer(true);

    try {
        // Configuración SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'carlosbojorquez1326@gmail.com';  // Correo remitente
        $mail->Password   = 'mcje dpwf fogp rvhg';           // Contraseña de aplicación
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Destinatario (dinámico - el que se ingresa en el formulario)
        $mail->setFrom('carlosbojorquez1326@gmail.com', 'Sistema RRHH');
        $mail->addAddress($email, "$nombre $apellido");

        // Contenido del correo
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Credenciales de acceso al sistema';

        $mail->Body = "
        <h2>Bienvenido(a) $nombre</h2>
        <p>Tus credenciales de acceso son:</p>
        <p><strong>Usuario:</strong> $usuario</p>
        <p><strong>Contraseña:</strong> $password</p>
        <p><strong>Puesto:</strong> $puesto</p>
        <p><em>Por favor, cambia tu contraseña al iniciar sesión.</em></p>
        ";

        $mail->send();
        return true;

    } catch (Exception $e) {
        return false;
    }
}
?>
