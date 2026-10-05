<?php
/**
 * SENATI ETI - Módulo de Auto-registro de Visitantes (QR)
 * Formulario reactivo con Fetch API y SweetAlert2 (Sin recargas de página)
 */

require_once __DIR__ . '/conexion.php';

$pdo = obtenerConexion();
$asuntos = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT id, nombre, tiempo_sla_minutos, descripcion FROM asuntos WHERE activo = 1 ORDER BY id ASC");
        $asuntos = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("[ERROR CARGA ASUNTOS]: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro de Visita - Senati ETI</title>

  <!-- Google Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap 5 (CSS Nativo) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <!-- SweetAlert2 (CSS Oficial) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

  <!-- Estilos Corporativos SENATI -->
  <link rel="stylesheet" href="css/senati-theme.css">

  <style>
    body {
      background: radial-gradient(circle at 50% 20%, #ffffff 0%, #f1f5f9 100%);
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }
    .register-card {
      max-width: 520px;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0, 56, 130, 0.06);
    }
  </style>
</head>
<body class="d-flex flex-column min-vh-100 justify-content-between p-3 p-md-4">

  <!-- Encabezado Minimalista -->
  <header class="text-center pt-2">
    <div class="d-inline-flex align-items-center gap-2 px-3 py-1.5 rounded-pill bg-white border shadow-sm">
      <span class="badge bg-primary px-2.5 py-1 text-uppercase fw-bold" style="background-color: #003882 !important; letter-spacing: 0.08em; font-size: 0.75rem;">
        SENATI ETI
      </span>
      <span class="text-secondary small fw-medium">Auto-atención Presencial</span>
    </div>
  </header>

  <!-- Contenedor Principal: Formulario de Registro -->
  <main class="container d-flex justify-content-center my-auto py-3">
    <div class="card register-card bg-white p-4 p-md-5 border w-100">
      
      <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center text-primary rounded-circle bg-light border mx-auto mb-2" style="width: 48px; height: 48px; color: #003882 !important;">
          <i class="bi bi-person-lines-fill fs-4"></i>
        </div>
        <h1 class="h4 fw-bold text-dark mb-1" style="letter-spacing: -0.02em;">Registro de Visita</h1>
        <p class="text-muted small mb-0">Completa tus datos para emitir tu ticket digital en ventanilla</p>
      </div>

      <!-- Formulario Asíncrono (Fetch API) -->
      <form id="formRegistroVisita" novalidate>
        
        <div class="mb-3">
          <label for="visitante" class="form-label small fw-semibold text-secondary">
            Nombres y Apellidos <span class="text-danger">*</span>
          </label>
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
            <input type="text" class="form-control form-control-sm border-start-0" id="visitante" name="visitante" required placeholder="Ej: Diana Sánchez Paredes">
          </div>
        </div>

        <div class="row g-2 mb-3">
          <div class="col-sm-6">
            <label for="dni" class="form-label small fw-semibold text-secondary">DNI o Documento</label>
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-card-heading"></i></span>
              <input type="text" class="form-control form-control-sm border-start-0" id="dni" name="dni" maxlength="15" placeholder="8 dígitos">
            </div>
          </div>
          <div class="col-sm-6">
            <label for="prioridad" class="form-label small fw-semibold text-secondary">Tipo de Atención</label>
            <select class="form-select form-select-sm" id="prioridad" name="prioridad">
              <option value="Media" selected>Ordinaria (Estándar)</option>
              <option value="Alta">Preferencial (Adulto M./Gestante)</option>
              <option value="Urgente">Urgente (Soporte Crítico)</option>
            </select>
          </div>
        </div>

        <div class="mb-3">
          <label for="asunto_id" class="form-label small fw-semibold text-secondary">
            Trámite o Asunto <span class="text-danger">*</span>
          </label>
          <select class="form-select form-select-sm" id="asunto_id" name="asunto_id" required>
            <?php if (!empty($asuntos)): ?>
              <?php foreach ($asuntos as $as): ?>
                <option value="<?php echo $as['id']; ?>">
                  <?php echo htmlspecialchars($as['nombre']); ?> (Aprox. <?php echo $as['tiempo_sla_minutos']; ?> min)
                </option>
              <?php endforeach; ?>
            <?php else: ?>
              <option value="1">Matrícula (Aprox. 15 min)</option>
              <option value="2">Pagos (Aprox. 10 min)</option>
              <option value="3">Tutoría (Aprox. 20 min)</option>
              <option value="4">Consultas de Notas (Aprox. 15 min)</option>
              <option value="5">Otros (Aprox. 15 min)</option>
            <?php endif; ?>
          </select>
        </div>

        <div class="mb-4">
          <label for="consulta" class="form-label small fw-semibold text-secondary">
            Motivo o Detalle de la Consulta <span class="text-danger">*</span>
          </label>
          <textarea class="form-control form-control-sm" id="consulta" name="consulta" rows="3" required placeholder="Describe brevemente el motivo de tu visita..."></textarea>
        </div>

        <button type="submit" class="btn btn-senati-primary w-100 py-2.5 fw-semibold d-flex align-items-center justify-content-center gap-2 mb-2 shadow-sm" style="border-radius: 8px;">
          <i class="bi bi-ticket-perforated"></i>
          <span>Solicitar Turno y Emitir Ticket</span>
        </button>

      </form>

      <!-- Botón de retorno al Tótem QR -->
      <div class="text-center mt-3 pt-3 border-top">
        <a href="index.php" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1.5">
          <i class="bi bi-arrow-left"></i>
          <span>Cancelar y volver al Tótem QR</span>
        </a>
      </div>

    </div>
  </main>

  <footer class="text-center pb-2 text-muted small">
    SENATI ETI • Dirección Zonal Lima Callao
  </footer>

  <!-- SweetAlert2 Oficial JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- Bootstrap 5 Bundle JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Script SENATI UX (Fetch + SweetAlert2) -->
  <script src="js/senati-ux.js"></script>
</body>
</html>
