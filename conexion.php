<?php
/**
 * SENATI ETI - Sistema de Gestión de Visitas
 * Clase de Conexión a Base de Datos (PDO Orientado a Objetos)
 * Seguridad: Consultas Preparadas (Prevención contra Inyección SQL)
 */

class Database {
    private string $host = "localhost";
    private string $db_name = "dashboardt8_bd";
    private string $username = "root";
    private string $password = "";
    private string $charset = "utf8mb4";
    private ?PDO $conn = null;

    /**
     * Establece y retorna la instancia activa de PDO.
     * Implementa manejo estricto de excepciones mediante try-catch.
     * 
     * @return PDO|null
     * @throws PDOException
     */
    public function conectar(): ?PDO {
        $this->conn = null;

        $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
        
        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lanza excepciones ante errores SQL
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Arreglos asociativos por defecto
            PDO::ATTR_EMULATE_PREPARES   => false,                  // Consultas preparadas nativas en el motor
            PDO::ATTR_PERSISTENT         => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
        ];

        try {
            $this->conn = new PDO($dsn, $this->username, $this->password, $opciones);
            return $this->conn;
        } catch (PDOException $e) {
            // Registro seguro del error sin exponer credenciales críticas en producción
            error_log("[ERROR BD Senati ETI]: " . $e->getMessage());
            
            // Retornamos null y permitimos a la interfaz manejar el estado o mostrar mensaje controlado
            return null;
        }
    }

    /**
     * Método auxiliar para verificar el estado de la conexión en interfaces de diagnóstico.
     * 
     * @return array
     */
    public function verificarEstado(): array {
        $conexion = $this->conectar();
        if ($conexion instanceof PDO) {
            return [
                'conectado' => true,
                'servidor' => $this->host,
                'base_datos' => $this->db_name,
                'mensaje' => 'Conexión exitosa a MySQL mediante PDO.'
            ];
        }
        return [
            'conectado' => false,
            'servidor' => $this->host,
            'base_datos' => $this->db_name,
            'mensaje' => 'No se pudo conectar a la base de datos. Verifique que MySQL esté iniciado en XAMPP.'
        ];
    }
}

// Instancia global reutilizable para inclusiones directas
function obtenerConexion(): ?PDO {
    static $db = null;
    if ($db === null) {
        $db = new Database();
    }
    return $db->conectar();
}
?>
