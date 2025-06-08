<?php
// functions/personal.php
require_once '../config/database.php';

class Personal {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Crear nuevo empleado
    public function create($data) {
        $query = "INSERT INTO personal (nombre, apellido, email, telefono, departamento, puesto, fecha_ingreso, salario) 
                  VALUES (:nombre, :apellido, :email, :telefono, :departamento, :puesto, :fecha_ingreso, :salario)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':nombre', $data['nombre']);
        $stmt->bindParam(':apellido', $data['apellido']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':telefono', $data['telefono']);
        $stmt->bindParam(':departamento', $data['departamento']);
        $stmt->bindParam(':puesto', $data['puesto']);
        $stmt->bindParam(':fecha_ingreso', $data['fecha_ingreso']);
        $stmt->bindParam(':salario', $data['salario']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
    
    // Leer todos los empleados
    public function readAll() {
        $query = "SELECT * FROM personal WHERE activo = 1 ORDER BY apellido, nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Leer un empleado por ID
    public function readOne($id) {
        $query = "SELECT * FROM personal WHERE id = :id AND activo = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Actualizar empleado
    public function update($id, $data) {
        $query = "UPDATE personal SET 
                  nombre = :nombre, 
                  apellido = :apellido, 
                  email = :email, 
                  telefono = :telefono, 
                  departamento = :departamento, 
                  puesto = :puesto, 
                  fecha_ingreso = :fecha_ingreso, 
                  salario = :salario 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nombre', $data['nombre']);
        $stmt->bindParam(':apellido', $data['apellido']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':telefono', $data['telefono']);
        $stmt->bindParam(':departamento', $data['departamento']);
        $stmt->bindParam(':puesto', $data['puesto']);
        $stmt->bindParam(':fecha_ingreso', $data['fecha_ingreso']);
        $stmt->bindParam(':salario', $data['salario']);
        
        return $stmt->execute();
    }
    
    // Eliminar empleado (soft delete)
    public function delete($id) {
        $query = "UPDATE personal SET activo = 0 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    // Verificar si el email ya existe
    public function emailExists($email, $excludeId = null) {
        $query = "SELECT id FROM personal WHERE email = :email AND activo = 1";
        if ($excludeId) {
            $query .= " AND id != :excludeId";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        if ($excludeId) {
            $stmt->bindParam(':excludeId', $excludeId);
        }
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
}
?>