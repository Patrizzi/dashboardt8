<?php
/**
 * SENATI ETI - Endpoint Asíncrono de Autenticación (AJAX / Fetch API)
 * Respuesta estrictamente en formato JSON
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Permitir solo peticiones POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Método no permitido. Utilice POST.'
    ]);
    exit;
}

// Obtener datos desde JSON crudo o FormData
$inputJson = file_get_contents('php://input');
$data = json_decode($inputJson, true);

$username = trim($data['username'] ?? $_POST['username'] ?? '');
$password = trim($data['password'] ?? $_POST['password'] ?? '');

// Validación de campos vacíos
if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Por favor completa todos los campos del formulario.'
    ]);
    exit;
}

$pdo = obtenerConexion();
if (!$pdo) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error de conexión con la base de datos MySQL (dashboardt8_bd).'
    ]);
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
        // Regenerar ID de sesión para mitigar Session Fixation
        session_regenerate_id(true);

        $_SESSION['usuario'] = [
            'id_usuario'  => (int)$user['id_usuario'],
            'username'    => $user['username'],
            'rol'         => $user['rol'],
            'nombre'      => $user['nombre'],
            'empleado_id' => $user['empleado_id'] ? (int)$user['empleado_id'] : null
        ];

        // Definir ruta de redirección según rol
        $redirectUrl = ($user['rol'] === 'administrador') ? 'dashboard.php' : 'empleado.php';

        echo json_encode([
            'status' => 'success',
            'message' => '¡Bienvenido(a), ' . htmlspecialchars($user['nombre']) . '!',
            'rol' => $user['rol'],
            'redirect' => $redirectUrl,
            'user' => [
                'id' => (int)$user['id_usuario'],
                'username' => $user['username'],
                'nombre' => $user['nombre'],
                'rol' => $user['rol']
            ]
        ]);
        exit;
    } else {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => 'Usuario o contraseña incorrectos. Por favor, verifica tus datos.'
        ]);
        exit;
    }
} catch (PDOException $e) {
    error_log("[ERROR AJAX LOGIN]: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Ocurrió un error interno en el servidor al autenticar.'
    ]);
    exit;
}
