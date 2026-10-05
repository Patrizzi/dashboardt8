<?php
/**
 * SENATI ETI - Controlador de Acciones y Operaciones de Base de Datos
 * Manejo seguro mediante Consultas Preparadas (PDO)
 */

require_once __DIR__ . '/conexion.php';

session_start();

$accion = $_REQUEST['accion'] ?? '';
$pdo = obtenerConexion();

if (!$pdo) {
    $_SESSION['toast'] = [
        'tipo' => 'danger',
        'mensaje' => 'Error: No se pudo conectar a la base de datos MySQL (dashboardt8_bd).'
    ];
    header('Location: index.php');
    exit;
}

// -------------------------------------------------------------
// 1. ACCIÓN: REGISTRAR VISITA (Manual por Empleado o Tótem QR)
// -------------------------------------------------------------
if ($accion === 'registrar_visita') {
    $visitante = trim($_POST['visitante'] ?? '');
    $dni = trim($_POST['dni'] ?? '');
    $asunto_id = intval($_POST['asunto_id'] ?? 1);
    $prioridad = $_POST['prioridad'] ?? 'Media';
    $consulta = trim($_POST['consulta'] ?? '');
    $empleado_id_input = $_POST['empleado_id'] ?? '';
    
    $empleado_id = ($empleado_id_input === 'disponible' || empty($empleado_id_input)) ? null : intval($empleado_id_input);
    $estado = $empleado_id ? 'En Proceso' : 'En Espera';

    // Generar código correlativo VIS-XXXX
    $stmtCount = $pdo->query("SELECT COUNT(*) FROM visitas");
    $total = $stmtCount->fetchColumn();
    $codigo = 'VIS-' . (1001 + $total);

    $sql = "INSERT INTO visitas 
            (codigo, visitante, dni, asunto_id, consulta, prioridad, estado, empleado_id, fecha_registro, hora_registro, t_registro, t_inicio) 
            VALUES 
            (:codigo, :visitante, :dni, :asunto_id, :consulta, :prioridad, :estado, :empleado_id, CURDATE(), CURTIME(), NOW(), :t_inicio)";

    $stmt = $pdo->prepare($sql);
    $t_inicio = $empleado_id ? date('Y-m-d H:i:s') : null;

    $stmt->execute([
        ':codigo' => $codigo,
        ':visitante' => $visitante,
        ':dni' => $dni,
        ':asunto_id' => $asunto_id,
        ':consulta' => $consulta,
        ':prioridad' => $prioridad,
        ':estado' => $estado,
        ':empleado_id' => $empleado_id,
        ':t_inicio' => $t_inicio
    ]);

    $_SESSION['toast'] = [
        'tipo' => 'success',
        'mensaje' => "Ticket {$codigo} generado con éxito para {$visitante}."
    ];
    header('Location: index.php?vista=empleado');
    exit;
}

// -------------------------------------------------------------
// 2. ACCIÓN: TOMAR TICKET (Regla de Carga Laboral: Cola Compartida)
// -------------------------------------------------------------
if ($accion === 'tomar_ticket') {
    $visita_id = intval($_POST['visita_id'] ?? 0);
    $empleado_id = intval($_POST['empleado_id'] ?? 1);

    if ($visita_id > 0) {
        $sql = "UPDATE visitas 
                SET empleado_id = :empleado_id, estado = 'En Proceso', t_inicio = NOW() 
                WHERE id = :id AND (empleado_id IS NULL OR estado = 'En Espera')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':empleado_id' => $empleado_id,
            ':id' => $visita_id
        ]);

        $_SESSION['toast'] = [
            'tipo' => 'success',
            'mensaje' => "Has tomado el ticket exitosamente. ¡Atención iniciada!"
        ];
    }
    header('Location: index.php?vista=empleado&tab=asignadas');
    exit;
}

// -------------------------------------------------------------
// 3. ACCIÓN: ATENDER / RESPONDER Y COMPLETAR TICKET
// -------------------------------------------------------------
if ($accion === 'atender_ticket') {
    $visita_id = intval($_POST['visita_id'] ?? 0);
    $respuesta = trim($_POST['respuesta'] ?? '');
    $estado = $_POST['estado'] ?? 'Completada';

    if ($visita_id > 0) {
        $sql = "UPDATE visitas 
                SET respuesta = :respuesta, 
                    estado = :estado, 
                    t_cierre = CASE WHEN :estado = 'Completada' THEN NOW() ELSE t_cierre END 
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':respuesta' => $respuesta,
            ':estado' => $estado,
            ':id' => $visita_id
        ]);

        $_SESSION['toast'] = [
            'tipo' => 'success',
            'mensaje' => "Ticket actualizado con estado '{$estado}'."
        ];
    }
    header('Location: index.php?vista=empleado&tab=asignadas');
    exit;
}

// -------------------------------------------------------------
// 4. ACCIÓN: REASIGNAR ALERTA DE AUDITORÍA CRÍTICA (ADMIN)
// -------------------------------------------------------------
if ($accion === 'reasignar_alerta') {
    $visita_id = intval($_POST['visita_id'] ?? 0);
    $empleado_id = intval($_POST['empleado_id'] ?? 1); // Por defecto Carlos R. EMP001

    if ($visita_id > 0) {
        $sql = "UPDATE visitas 
                SET empleado_id = :empleado_id, estado = 'En Proceso', t_inicio = NOW() 
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':empleado_id' => $empleado_id,
            ':id' => $visita_id
        ]);

        $_SESSION['toast'] = [
            'tipo' => 'warning',
            'mensaje' => "Ticket en alerta reasignado de manera prioritaria."
        ];
    }
    header('Location: index.php?vista=admin');
    exit;
}

// -------------------------------------------------------------
// 5. ACCIÓN: EXPORTAR REPORTE A CSV (Excel Compatible con UTF-8 BOM)
// -------------------------------------------------------------
if ($accion === 'exportar_csv') {
    $tipo = $_GET['tipo'] ?? 'mes';
    $filename = "SenatiETI_Reporte_{$tipo}_" . date('Y-m-d') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename={$filename}");

    $output = fopen('php://output', 'w');
    // Escribir BOM UTF-8 para visualización correcta en Excel
    fputs($output, "\xEF\xBB\xBF");

    fputcsv($output, ['Codigo', 'Visitante', 'DNI', 'Asunto', 'Prioridad', 'Estado', 'Fecha', 'HoraRegistro', 'Colaborador', 'Consulta', 'Respuesta']);

    $sql = "SELECT v.codigo, v.visitante, v.dni, a.nombre AS asunto, v.prioridad, v.estado, v.fecha_registro, v.hora_registro, 
                   COALESCE(e.nombre, 'Sin Asignar') AS colaborador, v.consulta, COALESCE(v.respuesta, '') AS respuesta
            FROM visitas v
            JOIN asuntos a ON v.asunto_id = a.id
            LEFT JOIN empleados e ON v.empleado_id = e.id ";

    if ($tipo === 'mes') {
        $sql .= " WHERE v.fecha_registro >= DATE_FORMAT(NOW(), '%Y-%m-01')";
    } elseif ($tipo === 'trimestre') {
        $sql .= " WHERE v.fecha_registro >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
    }

    $sql .= " ORDER BY v.t_registro DESC";

    $stmt = $pdo->query($sql);
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Redirección por defecto
header('Location: index.php');
exit;
