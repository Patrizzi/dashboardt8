<?php
/**
 * SENATI ETI - Endpoint Asíncrono para Actualizar Estado de Visitas (AJAX / Fetch API)
 * Procesa: Tomar Ticket, Completar Atención, Cancelar/Abandonar y Reasignar
 * Respuesta estrictamente en formato JSON para manipulación reactiva del DOM y SweetAlert2
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Validar autenticación de sesión
if (!isset($_SESSION['usuario']['id_usuario'])) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Sesión expirada o no autenticada. Inicia sesión nuevamente.'
    ]);
    exit;
}

$currentUser = $_SESSION['usuario'];
$empleadoId = $currentUser['empleado_id'] ?? 1;

// Permitir solo peticiones POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Método no permitido. Utilice POST.'
    ]);
    exit;
}

// Recibir JSON crudo o FormData
$inputJson = file_get_contents('php://input');
$data = json_decode($inputJson, true);

$accion = $data['accion'] ?? $_POST['accion'] ?? '';
$ticketId = intval($data['ticket_id'] ?? $_POST['ticket_id'] ?? 0);
$respuesta = trim($data['respuesta'] ?? $_POST['respuesta'] ?? '');
$nuevoEmpleadoId = intval($data['empleado_id'] ?? $_POST['empleado_id'] ?? $empleadoId);

if ($ticketId <= 0) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Identificador de ticket no válido.'
    ]);
    exit;
}

$pdo = obtenerConexion();
if (!$pdo) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al conectar con la base de datos MySQL.'
    ]);
    exit;
}

try {
    // Verificar existencia del ticket
    $stmtCheck = $pdo->prepare("SELECT v.*, a.nombre as asunto_nombre FROM visitas v JOIN asuntos a ON v.asunto_id = a.id WHERE v.id = :id LIMIT 1");
    $stmtCheck->execute([':id' => $ticketId]);
    $ticket = $stmtCheck->fetch();

    if (!$ticket) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'El ticket solicitado no fue encontrado.'
        ]);
        exit;
    }

    $mensajeExito = '';

    // ============================================================
    // CASO 1: TOMAR TICKET (Desde la cola disponible)
    // ============================================================
    if ($accion === 'tomar') {
        $stmtUpdate = $pdo->prepare("UPDATE visitas 
                                     SET empleado_id = :empleado_id, 
                                         estado = 'En Proceso', 
                                         t_inicio = COALESCE(t_inicio, NOW()) 
                                     WHERE id = :id");
        $stmtUpdate->execute([
            ':empleado_id' => $empleadoId,
            ':id' => $ticketId
        ]);
        $mensajeExito = "El ticket {$ticket['codigo']} ha sido asignado a tu ventanilla. ¡Atención iniciada!";
    }

    // ============================================================
    // CASO 2: COMPLETAR ATENCIÓN
    // ============================================================
    elseif ($accion === 'completar') {
        if (empty($respuesta)) {
            $respuesta = "Atención finalizada satisfactoriamente en ventanilla.";
        }

        $stmtUpdate = $pdo->prepare("UPDATE visitas 
                                     SET estado = 'Completada', 
                                         respuesta = :respuesta, 
                                         t_cierre = NOW() 
                                     WHERE id = :id");
        $stmtUpdate->execute([
            ':respuesta' => $respuesta,
            ':id' => $ticketId
        ]);
        $mensajeExito = "¡Excelente! La visita {$ticket['codigo']} ha sido completada y registrada en el historial.";
    }

    // ============================================================
    // CASO 3: CANCELAR / ABANDONAR TICKET (Con confirmación previa)
    // ============================================================
    elseif ($accion === 'abandonar' || $accion === 'cancelar') {
        $motivo = !empty($respuesta) ? $respuesta : 'Ticket marcado como abandonado por el visitante.';
        
        $stmtUpdate = $pdo->prepare("UPDATE visitas 
                                     SET estado = 'Cancelada', 
                                         respuesta = :motivo, 
                                         t_cierre = NOW() 
                                     WHERE id = :id");
        $stmtUpdate->execute([
            ':motivo' => $motivo,
            ':id' => $ticketId
        ]);
        $mensajeExito = "El ticket {$ticket['codigo']} fue marcado como cancelado/abandonado.";
    }

    // ============================================================
    // CASO 4: DERIVAR / REASIGNAR A OTRO COLABORADOR
    // ============================================================
    elseif ($accion === 'derivar') {
        $stmtUpdate = $pdo->prepare("UPDATE visitas 
                                     SET empleado_id = :empleado_id, 
                                         estado = 'En Proceso' 
                                     WHERE id = :id");
        $stmtUpdate->execute([
            ':empleado_id' => $nuevoEmpleadoId,
            ':id' => $ticketId
        ]);
        $mensajeExito = "El ticket {$ticket['codigo']} fue derivado al colaborador con éxito.";
    }

    else {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => "Acción desconocida: '{$accion}'."
        ]);
        exit;
    }

    // Obtener métricas KPI actualizadas para refrescar el DOM sin recargar la página
    $stmtKPI = $pdo->prepare("SELECT 
        SUM(CASE WHEN estado IN ('En Espera', 'En Proceso') AND (empleado_id = :empId1 OR empleado_id IS NULL) THEN 1 ELSE 0 END) AS mis_activos,
        SUM(CASE WHEN estado = 'En Espera' THEN 1 ELSE 0 END) AS total_en_espera,
        SUM(CASE WHEN estado = 'En Proceso' AND empleado_id = :empId2 THEN 1 ELSE 0 END) AS mis_en_proceso,
        SUM(CASE WHEN estado = 'Completada' AND empleado_id = :empId3 AND fecha_registro = CURDATE() THEN 1 ELSE 0 END) AS mis_completadas_hoy,
        (SELECT COUNT(*) FROM visitas WHERE empleado_id IS NULL AND estado = 'En Espera') AS cola_disponible
    FROM visitas");
    
    $stmtKPI->execute([
        ':empId1' => $empleadoId,
        ':empId2' => $empleadoId,
        ':empId3' => $empleadoId
    ]);
    $kpis = $stmtKPI->fetch();

    echo json_encode([
        'status' => 'success',
        'message' => $mensajeExito,
        'accion' => $accion,
        'ticket_id' => $ticketId,
        'codigo' => $ticket['codigo'],
        'kpis' => [
            'activos' => (int)($kpis['mis_activos'] ?? 0),
            'enEspera' => (int)($kpis['total_en_espera'] ?? 0),
            'enProceso' => (int)($kpis['mis_en_proceso'] ?? 0),
            'completadasHoy' => (int)($kpis['mis_completadas_hoy'] ?? 0),
            'colaDisponible' => (int)($kpis['cola_disponible'] ?? 0)
        ]
    ]);
    exit;

} catch (PDOException $e) {
    error_log("[ERROR AJAX ACTUALIZAR ESTADO]: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error interno al actualizar el estado de la visita en la base de datos.'
    ]);
    exit;
}
