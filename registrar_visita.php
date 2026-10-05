<?php
/**
 * SENATI ETI - Módulo de Auto-registro de Visitantes (QR)
 * Formulario de Auto-atención y Emisión de Ticket Digital
 */

require_once __DIR__ . '/conexion.php';

$pdo = obtenerConexion();
$ticketGenerado = null;
$errorMsg = '';

// Procesar envío del formulario de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visitante = trim($_POST['visitante'] ?? '');
    $dni = trim($_POST['dni'] ?? '');
    $asunto_id = intval($_POST['asunto_id'] ?? 1);
    $prioridad = $_POST['prioridad'] ?? 'Media';
    $consulta = trim($_POST['consulta'] ?? '');

    if (!empty($visitante) && !empty($consulta)) {
        if ($pdo) {
            try {
                // Obtener correlativo
                $stmtCount = $pdo->query("SELECT COUNT(*) FROM visitas");
                $total = $stmtCount->fetchColumn();
                $codigo = 'VIS-' . (1001 + $total);

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

                // Obtener nombre del asunto
                $stmtAsunto = $pdo->prepare("SELECT nombre, tiempo_sla_minutos FROM asuntos WHERE id = :id");
                $stmtAsunto->execute([':id' => $asunto_id]);
                $asuntoData = $stmtAsunto->fetch();

                $ticketGenerado = [
                    'codigo' => $codigo,
                    'visitante' => $visitante,
                    'dni' => $dni,
                    'asunto' => $asuntoData['nombre'] ?? 'Consulta General',
                    'sla' => $asuntoData['tiempo_sla_minutos'] ?? 15,
                    'hora' => date('H:i:s'),
                    'fecha' => date('d/m/Y')
                ];
            } catch (PDOException $e) {
                error_log("[ERROR REGISTRO VISITA]: " . $e->getMessage());
                $errorMsg = 'Error al registrar la visita en la base de datos.';
            }
        } else {
            $errorMsg = 'No hay conexión con la base de datos MySQL.';
        }
    } else {
        $errorMsg = 'Por favor completa todos los campos requeridos (*).';
    }
}

// Cargar catálogo de asuntos
$asuntos = [];
if ($pdo) {
    $stmtA = $pdo->query("SELECT id, nombre, tiempo_sla_minutos FROM asuntos WHERE activo = 1 ORDER BY id ASC");
    $asuntos = $stmtA->fetchAll();
} else {
    $asuntos = [
        ['id' => 1, 'nombre' => 'Matrícula', 'tiempo_sla_minutos' => 15],
        ['id' => 2, 'nombre' => 'Pagos', 'tiempo_sla_minutos' => 10],
        ['id' => 3, 'nombre' => 'Tutoría', 'tiempo_sla_minutos' => 20],
        ['id' => 4, 'nombre' => 'Consultas de Notas', 'tiempo_sla_minutos' => 15],
        ['id' => 5, 'nombre' => 'Otros', 'tiempo_sla_minutos' => 15]
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Auto-registro de Visitante - Senati ETI</title>

  <!-- Google Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap 5 (CSS Nativo) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <!-- Estilos Corporativos SENATI -->
  <link rel="stylesheet" href="css/senati-theme.css">
</head>
<body class="bg-light min-vh-100 d-flex flex-column justify-content-between py-4">

  <div class="container py-3" style="max-width: 580px;">

    <?php if ($ticketGenerado): ?>
      <!-- VOUCHER / TICKET DIGITAL CONFIRMADO -->
      <div class="card card-flat p-4 p-md-5 bg-white text-center shadow-sm">
        <div class="d-inline-flex align-items-center justify-content-center bg-success text-white rounded-circle p-3 mb-3 mx-auto" style="width: 56px; height: 56px;">
          <i class="bi bi-check-lg fs-3"></i>
        </div>
        
        <h1 class="h4 fw-bold text-dark mb-1">¡Turno Generado con Éxito!</h1>
        <p class="text-muted small mb-4">Tu ticket ha ingresado a la cola de atención en sede.</p>

        <!-- Cuadro Destacado del Ticket -->
        <div class="bg-light p-4 rounded-3 border mb-4 text-center">
          <span class="text-uppercase small fw-bold text-muted d-block mb-1">Código de Ticket</span>
          <div class="display-5 fw-bold font-monospace text-primary mb-2">
            <?php echo htmlspecialchars($ticketGenerado['codigo']); ?>
          </div>
          <div class="d-flex justify-content-center gap-3 small text-secondary">
            <span><i class="bi bi-calendar3 me-1"></i><?php echo $ticketGenerado['fecha']; ?></span>
            <span><i class="bi bi-clock me-1"></i><?php echo $ticketGenerado['hora']; ?></span>
          </div>
        </div>

        <!-- Detalles de Consulta -->
        <div class="text-start small text-secondary bg-white p-3 rounded-2 border mb-4">
          <div class="row g-2">
            <div class="col-6"><b>Visitante:</b> <?php echo htmlspecialchars($ticketGenerado['visitante']); ?></div>
            <div class="col-6"><b>DNI:</b> <?php echo htmlspecialchars($ticketGenerado['dni'] ?: 'No registrado'); ?></div>
            <div class="col-12"><b>Trámite / Asunto:</b> <span class="badge bg-primary px-2 py-1"><?php echo htmlspecialchars($ticketGenerado['asunto']); ?></span></div>
          </div>
        </div>

        <div class="alert alert-info py-2 px-3 small mb-4 d-flex align-items-center text-start">
          <i class="bi bi-info-circle-fill text-info me-2 fs-6"></i>
          <div>Por favor toma asiento en la sala de espera. Un asesor te llamará en pantalla por tu código <b><?php echo htmlspecialchars($ticketGenerado['codigo']); ?></b>.</div>
        </div>

        <div class="d-flex gap-2 justify-content-center">
          <a href="registrar_visita.php" class="btn btn-outline-secondary btn-sm px-3">
            <i class="bi bi-plus-circle me-1"></i> Registrar otra visita
          </a>
          <a href="index.php" class="btn btn-senati-primary btn-sm px-4">
            <i class="bi bi-house me-1"></i> Volver al Inicio
          </a>
        </div>
      </div>

    <?php else: ?>
      <!-- FORMULARIO DE REGISTRO DE VISITA -->
      <div class="card card-flat p-4 p-md-5 bg-white shadow-sm">
        
        <div class="text-center mb-4">
          <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-3 px-3 py-1 mb-2 fw-bold text-uppercase small" style="letter-spacing: 0.08em;">
            SENATI ETI
          </div>
          <h1 class="h4 fw-bold text-dark mb-1">Registro de Visita</h1>
          <p class="text-muted small mb-0">Completa tus datos para obtener tu turno de atención</p>
        </div>

        <?php if (!empty($errorMsg)): ?>
          <div class="alert alert-danger py-2 px-3 small d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>
            <div><?php echo htmlspecialchars($errorMsg); ?></div>
          </div>
        <?php endif; ?>

        <form action="registrar_visita.php" method="POST">
          <div class="mb-3">
            <label for="visitante" class="form-label small fw-semibold text-secondary">Nombre y Apellidos Completos *</label>
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
              <input type="text" name="visitante" id="visitante" required placeholder="Ej: Patricia Benavides Torres" class="form-control form-control-sm border-start-0">
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-12 col-sm-6">
              <label for="dni" class="form-label small fw-semibold text-secondary">DNI / Documento</label>
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-card-heading"></i></span>
                <input type="text" name="dni" id="dni" maxlength="15" placeholder="87654321" class="form-control form-control-sm border-start-0">
              </div>
            </div>
            <div class="col-12 col-sm-6">
              <label for="asunto_id" class="form-label small fw-semibold text-secondary">Trámite o Asunto *</label>
              <select name="asunto_id" id="asunto_id" required class="form-select form-select-sm">
                <?php foreach ($asuntos as $as): ?>
                  <option value="<?php echo $as['id']; ?>"><?php echo htmlspecialchars($as['nombre']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label for="prioridad" class="form-label small fw-semibold text-secondary">Condición de Atención *</label>
            <select name="prioridad" id="prioridad" required class="form-select form-select-sm">
              <option value="Media" selected>Normal (Turno Estándar)</option>
              <option value="Alta">Trámite Urgente (Plazos institucionales)</option>
              <option value="Urgente">Atención Preferencial (Adulto mayor / Salud / Inclusión)</option>
              <option value="Baja">Consulta Informativa Breve</option>
            </select>
          </div>

          <div class="mb-4">
            <label for="consulta" class="form-label small fw-semibold text-secondary">Motivo o Detalle de la Consulta *</label>
            <textarea name="consulta" id="consulta" required rows="3" placeholder="Describe brevemente el trámite que deseas realizar..." class="form-control form-control-sm"></textarea>
          </div>

          <button type="submit" class="btn btn-senati-primary w-100 py-2.5 fw-semibold d-flex align-items-center justify-content-center gap-2 mb-3">
            <i class="bi bi-ticket-perforated"></i>
            <span>Obtener Ticket de Atención</span>
          </button>

          <div class="text-center pt-2">
            <a href="index.php" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1">
              <i class="bi bi-arrow-left"></i>
              <span>Volver a la pantalla del QR</span>
            </a>
          </div>
        </form>

      </div>
    <?php endif; ?>

  </div>

  <footer class="text-center text-muted small py-2">
    SENATI ETI • Sistema de Gestión de Visitas
  </footer>

</body>
</html>
