<?php
/**
 * SENATI ETI - Procesador de Inicio de Sesión
 * Valida credenciales contra MySQL usando PDO y password_verify()
 */

require_once __DIR__ . '/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($username) || empty($password)) {
    header('Location: login.php?error=campos_vacios');
    exit;
}

$pdo = obtenerConexion();
if (!$pdo) {
    header('Location: login.php?error=db_error');
    exit;
}

try {
    // Consulta preparada contra la tabla usuarios
    $stmt = $pdo->prepare("SELECT id_usuario, username, password, rol, nombre, empleado_id, activo 
                           FROM usuarios 
                           WHERE username = :username AND activo = 1 
                           LIMIT 1");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Regenerar ID de sesión para prevenir Session Fixation
        session_regenerate_id(true);

        $_SESSION['usuario'] = [
            'id_usuario'  => (int)$user['id_usuario'],
            'username'    => $user['username'],
            'rol'         => $user['rol'],
            'nombre'      => $user['nombre'],
            'empleado_id' => $user['empleado_id'] ? (int)$user['empleado_id'] : null
        ];

        // Redirección basada en roles (RBAC)
        if ($user['rol'] === 'administrador') {
            header('Location: dashboard.php');
        } elseif ($user['rol'] === 'empleado') {
            header('Location: empleado.php');
        } else {
            header('Location: index.php');
        }
        exit;
    } else {
        // Credenciales erróneas
        header('Location: login.php?error=credenciales_invalidas');
        exit;
    }

} catch (PDOException $e) {
    error_log("[ERROR LOGIN]: " . $e->getMessage());
    header('Location: login.php?error=db_error');
    exit;
}
?>
