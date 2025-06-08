<?php
// functions/auth.php - Versión con debugging
require_once dirname(__DIR__) . '/config/database.php';

class Auth {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->startSession();
    }
    
    private function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function login($username, $password) {
        // Debugging: Log de datos recibidos
        error_log("=== LOGIN ATTEMPT ===");
        error_log("Username recibido: '" . $username . "'");
        error_log("Password recibido: '" . $password . "'");
        error_log("Username length: " . strlen($username));
        error_log("Password length: " . strlen($password));
        
        // Limpiar espacios en blanco
        $username = trim($username);
        $password = trim($password);
        
        error_log("Username después de trim: '" . $username . "'");
        error_log("Password después de trim: '" . $password . "'");
        
        $query = "SELECT id, username, password, email FROM usuarios_admin WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        error_log("Query ejecutado, rowCount: " . $stmt->rowCount());
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("Usuario encontrado en BD:");
            error_log("- ID: " . $row['id']);
            error_log("- Username: '" . $row['username'] . "'");
            error_log("- Email: " . $row['email']);
            error_log("- Password: " . $row['password']);
            
            // CAMBIO PRINCIPAL: Verificar contraseña en texto plano
            $passwordMatch = ($password === $row['password']);
            error_log("password match result: " . ($passwordMatch ? 'TRUE' : 'FALSE'));
            
            if ($passwordMatch) {
                error_log("LOGIN EXITOSO");
                $this->startSession();
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['email'] = $row['email'];
                return true;
            } else {
                error_log("LOGIN FALLIDO - Contraseña incorrecta");
                error_log("Password ingresado: '" . $password . "'");
                error_log("Password en BD: '" . $row['password'] . "'");
                return false;
            }
        } else {
            error_log("LOGIN FALLIDO - Usuario no encontrado");
            return false;
        }
    }
    
    public function logout() {
        $this->startSession();
        session_unset();
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        $this->startSession();
        return isset($_SESSION['user_id']);
    }
    
    public function changePassword($username, $oldPassword, $newPassword) {
        // Verificar contraseña actual (también cambiar aquí para texto plano)
        $query = "SELECT password FROM usuarios_admin WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($oldPassword === $row['password']) { // Cambio aquí también
                // Actualizar contraseña (guardar en texto plano)
                $updateQuery = "UPDATE usuarios_admin SET password = :password WHERE username = :username";
                $updateStmt = $this->conn->prepare($updateQuery);
                $updateStmt->bindParam(':password', $newPassword); // Sin hash
                $updateStmt->bindParam(':username', $username);
                
                if ($updateStmt->execute()) {
                    return true;
                }
            }
        }
        return false;
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ../index.php');
            exit();
        }
    }
}
?>