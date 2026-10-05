<?php
/**
 * SENATI ETI - Endpoint Asíncrono de Registro de Visitas (AJAX / Fetch API)
 * Emite tickets digitales y responde estrictamente en formato JSON
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion.php';

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

$visitante = trim($data['visitante'] ?? $_POST['visitante'] ?? '');
$dni = trim($data['dni'] ?? $_POST['dni'] ?? '');
$asunto_id = intval($data['asunto_id'] ?? $_POST['asunto_id'] ?? 1);
$prioridad = $data['prioridad'] ?? $_POST['prioridad'] ?? 'Media';
$consulta = trim($data['consulta'] ?? $_POST['consulta'] ?? '');

// Validación de campos obligatorios
if (empty($visitante) || empty($consulta)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'El nombre del visitante y el motivo de consulta son obligatorios.'
    ]);
    exit;
}

// Validar prioridades permitidas
$prioridadesValidas = ['Baja', 'Media', 'Alta', 'Urgente'];
if (!in_array($prioridad, $prioridadesValidas)) {
    $prioridad = 'Media';
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
    // 1. Obtener correlativo secuencial
    $stmtCount = $pdo->query("SELECT COUNT(*) FROM visitas");
    $total = (int)$stmtCount->fetchColumn();
    $codigo = 'VIS-' . (1001 + $total);

    // 2. Insertar visita
    $sql = "INSERT INTO visitas 
            (codigo, visitante, dni, asunto_id, consulta, prioridad, estado, empleado_id, fecha_registro, hora_registro, t_registro) 
            VALUES 
            (:codigo, :visitante, :dni, :asunto_id, :consulta, :prioridad, 'En Espera', NULL, CURDATE(), CURTIME(), NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':codigo' => $codigo,
        ':visitante' => $visitante,
        ':dni' => $dni,
        ':asunto_id' => $asunto_id,
        ':consulta' => $consulta,
        ':prioridad' => $prioridad
    ]);

    $idInsertado = (int)$pdo->lastInsertId();

    // 3. Obtener metadatos del trámite/asunto
    $stmtAsunto = $pdo->prepare("SELECT nombre, tiempo_sla_minutos FROM asuntos WHERE id = :id LIMIT 1");
    $stmtAsunto->execute([':id' => $asunto_id]);
    $asuntoData = $stmtAsunto->fetch();

    $nombreAsunto = $asuntoData['nombre'] ?? 'Consulta General';
    $slaMinutos = (int)($asuntoData['tiempo_sla_minutos'] ?? 15);

    // 4. Calcular posición en cola de espera
    $stmtCola = $pdo->query("SELECT COUNT(*) FROM visitas WHERE estado = 'En Espera'");
    $posicionCola = (int)$stmtCola->fetchColumn();

    // Respuesta JSON estructurada
    echo json_encode([
        'status' => 'success',
        'message' => '¡Tu ticket de atención ha sido generado con éxito!',
        'data' => [
            'id' => $idInsertado,
            'codigo' => $codigo,
            'visitante' => htmlspecialchars($visitante),
            'dni' => htmlspecialchars($dni),
            'asunto' => htmlspecialchars($nombreAsunto),
            'prioridad' => $prioridad,
            'sla' => $slaMinutos,
            'posicionCola' => $posicionCola,
            'fecha' => date('d/m/Y'),
            'hora' => date('H:i:s')
        ]
    ]);
    exit;

} catch (PDOException $e) {
    error_log("[ERROR AJAX REGISTRO VISITA]: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'No se pudo guardar la visita en la base de datos. Intenta nuevamente.'
    ]);
    exit;
}
