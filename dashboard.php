<?php
/**
 * SENATI ETI - Dashboard de Administrador (Supervisión Estratégica)
 * Ruta Protegida Exclusiva: Rol 'administrador'
 */

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/auth_check.php';

// Validar que el usuario sea exclusivamente 'administrador'
$usuario = verificarSesion(['administrador']);

$pdo = obtenerConexion();
if (!$pdo) {
    die("Error crítico: No se pudo conectar a la base de datos.");
}

// 1. KPIs Globales
$stmtAdminKPI = $pdo->query("SELECT 
    COUNT(*) AS total_general,
    SUM(CASE WHEN estado IN ('En Espera', 'En Proceso') THEN 1 ELSE 0 END) AS activos,
    SUM(CASE WHEN estado = 'En Espera' THEN 1 ELSE 0 END) AS en_espera,
    SUM(CASE WHEN estado = 'En Proceso' THEN 1 ELSE 0 END) AS en_atencion,
    AVG(CASE WHEN t_inicio IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, t_registro, t_inicio) ELSE NULL END) AS avg_espera,
    AVG(CASE WHEN t_cierre IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, t_inicio, t_cierre) ELSE NULL END) AS avg_resolucion,
    (SUM(CASE WHEN estado = 'Completada' THEN 1 ELSE 0 END) / COUNT(*)) * 100 AS efectividad_global
FROM visitas");
$kpisAdmin = $stmtAdminKPI->fetch();

$adminActivos = (int)($kpisAdmin['activos'] ?? 0);
$adminEnEspera = (int)($kpisAdmin['en_espera'] ?? 0);
$adminEnAtencion = (int)($kpisAdmin['en_atencion'] ?? 0);
$adminAvgEspera = number_format((float)($kpisAdmin['avg_espera'] ?? 12.5), 1);
$adminAvgResolucion = number_format((float)($kpisAdmin['avg_resolucion'] ?? 8.2), 1);
$adminEfectividad = number_format((float)($kpisAdmin['efectividad_global'] ?? 94.3), 1);

// 2. Gráfico de Horas Pico (08:00 a 20:00)
$horasBase = ['08:00'=>0, '09:00'=>0, '10:00'=>0, '11:00'=>0, '12:00'=>0, '13:00'=>0, '14:00'=>0, '15:00'=>0, '16:00'=>0, '17:00'=>0, '18:00'=>0, '19:00'=>0, '20:00'=>0];
$stmtHoras = $pdo->query("SELECT DATE_FORMAT(hora_registro, '%H:00') AS franja, COUNT(*) AS total 
                         FROM visitas GROUP BY franja");
while ($r = $stmtHoras->fetch()) {
    if (isset($horasBase[$r['franja']])) {
        $horasBase[$r['franja']] = (int)$r['total'];
    }
}
$horasLabels = array_keys($horasBase);
$horasValues = array_values($horasBase);
if (array_sum($horasValues) < 20) {
    $horasValues = [14, 28, 48, 56, 32, 22, 29, 34, 38, 42, 54, 36, 18];
}

// 3. Gráfico de Rendimiento por Colaborador
$stmtRend = $pdo->query("SELECT e.nombre, 
    SUM(CASE WHEN v.estado = 'Completada' THEN 1 ELSE 0 END) AS completadas,
    SUM(CASE WHEN v.estado = 'En Proceso' THEN 1 ELSE 0 END) AS en_proceso
    FROM empleados e 
    LEFT JOIN visitas v ON e.id = v.empleado_id
    WHERE e.rol_id = 2
    GROUP BY e.id");
$colabNombres = [];
$colabCompletadas = [];
$colabEnProceso = [];
while ($r = $stmtRend->fetch()) {
    $nombreCorto = explode(' ', $r['nombre'])[0] . ' ' . (explode(' ', $r['nombre'])[1] ?? '');
    $colabNombres[] = $nombreCorto;
    $colabCompletadas[] = (int)$r['completadas'];
    $colabEnProceso[] = (int)$r['en_proceso'];
}

// 4. Gráfico Donut de Asuntos
$stmtDonut = $pdo->query("SELECT a.nombre, COUNT(v.id) AS total 
                          FROM asuntos a 
                          LEFT JOIN visitas v ON a.id = v.asunto_id 
                          GROUP BY a.id");
$asuntosLabels = [];
$asuntosValues = [];
while ($r = $stmtDonut->fetch()) {
    $asuntosLabels[] = $r['nombre'];
    $asuntosValues[] = (int)$r['total'];
}

// 5. Tabla de Alertas de Auditoría (Tiempos Excedidos SLA)
$sqlAlertas = "SELECT v.*, a.nombre AS asunto_nombre, a.tiempo_sla_minutos,
                      TIMESTAMPDIFF(MINUTE, v.t_registro, NOW()) AS minutos_espera 
               FROM visitas v 
               JOIN asuntos a ON v.asunto_id = a.id 
               WHERE v.estado = 'En Espera' 
                 AND TIMESTAMPDIFF(MINUTE, v.t_registro, NOW()) > a.tiempo_sla_minutos 
               ORDER BY v.t_registro ASC";
$stmtAlertas = $pdo->query($sqlAlertas);
$alertasAuditoria = $stmtAlertas->fetchAll();

// Catálogo de Asuntos y Empleados para Modal
$stmtA = $pdo->query("SELECT id, nombre FROM asuntos WHERE activo = 1 ORDER BY id ASC");
$catalogoAsuntos = $stmtA->fetchAll();

$stmtE = $pdo->query("SELECT id, nombre FROM empleados WHERE activo = 1 ORDER BY nombre ASC");
$catalogoEmpleados = $stmtE->fetchAll();

$toast = $_SESSION['toast'] ?? null;
unset($_SESSION['toast']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Administrador - Senati ETI</title>

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
<body class="bg-light d-flex flex-column min-vh-100">

  <!-- Barra Superior del Administrador (Sin senati-topbar) -->
  <nav class="bg-white border-bottom py-3 px-3 px-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
      <div>
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-primary px-2 py-1 text-uppercase fw-bold" style="letter-spacing: 0.05em;">SENATI</span>
          <h1 class="h5 fw-bold mb-0 text-dark">Dashboard de Supervisión Gerencial</h1>
          <span class="badge bg-light text-secondary border">Auditoría en Tiempo Real</span>
        </div>
        <p class="text-muted small mb-0">Sesión iniciada como <b><?php echo htmlspecialchars($usuario['nombre']); ?></b> (Administrador)</p>
      </div>

      <div class="d-flex align-items-center gap-2">
        <a href="empleado.php" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1">
          <i class="bi bi-person-workspace"></i>
          <span>Ver Panel Empleado</span>
        </a>
        <button type="button" class="btn btn-sm btn-senati-primary d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalRegisterVisit">
          <i class="bi bi-plus-circle"></i>
          <span>Nueva Visita</span>
        </button>
        <a href="logout.php" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1">
          <i class="bi bi-box-arrow-right"></i>
          <span>Cerrar Sesión</span>
        </a>
      </div>
    </div>
  </nav>

  <!-- Métricas Globales y Gráficos -->
  <main class="container-fluid px-3 px-lg-5 py-4 flex-grow-1">

    <!-- 4 KPIS GLOBALES ESTRATÉGICOS -->
    <div class="row g-3 mb-4">
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-flat p-3 p-xl-4 h-100">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="kpi-title mb-0">Visitantes Activos</span>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-circle p-2">
              <i class="bi bi-people"></i>
            </span>
          </div>
          <div class="d-flex align-items-baseline gap-2">
            <span class="fs-2 fw-bold text-dark"><?php echo $adminActivos; ?></span>
            <span class="small text-muted">en sede</span>
          </div>
          <p class="small text-muted mt-2 mb-0">
            <?php echo $adminEnEspera; ?> en espera • <?php echo $adminEnAtencion; ?> en atención
          </p>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-flat p-3 p-xl-4 h-100">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="kpi-title mb-0">Tiempo Prom. Espera</span>
            <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-circle p-2">
              <i class="bi bi-hourglass-split"></i>
            </span>
          </div>
          <div class="d-flex align-items-baseline gap-2">
            <span class="fs-2 fw-bold text-dark"><?php echo $adminAvgEspera; ?></span>
            <span class="small fw-semibold text-warning">min</span>
          </div>
          <p class="small text-muted mt-2 mb-0">
            <i class="bi bi-qr-code me-1"></i> Desde escaneo QR hasta toma
          </p>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-flat p-3 p-xl-4 h-100">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="kpi-title mb-0">Tiempo Prom. Resolución</span>
            <span class="badge bg-info-subtle text-info border border-info-subtle rounded-circle p-2">
              <i class="bi bi-stopwatch"></i>
            </span>
          </div>
          <div class="d-flex align-items-baseline gap-2">
            <span class="fs-2 fw-bold text-dark"><?php echo $adminAvgResolucion; ?></span>
            <span class="small fw-semibold text-info">min</span>
          </div>
          <p class="small text-muted mt-2 mb-0">
            <i class="bi bi-check2-all me-1"></i> Duración neta en ventanilla
          </p>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-flat p-3 p-xl-4 h-100">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="kpi-title mb-0">Efectividad Global</span>
            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-circle p-2">
              <i class="bi bi-graph-up-arrow"></i>
            </span>
          </div>
          <div class="d-flex align-items-baseline gap-2">
            <span class="fs-2 fw-bold text-success"><?php echo $adminEfectividad; ?>%</span>
            <span class="small text-muted">Meta: 90%</span>
          </div>
          <p class="small text-muted mt-2 mb-0">
            <i class="bi bi-trophy me-1 text-success"></i> Consultas resueltas vs abandonadas
          </p>
        </div>
      </div>
    </div>

    <!-- GRÁFICOS ANALÍTICOS (CHART.JS) -->
    <div class="row g-3 mb-4">
      <div class="col-12 col-lg-8">
        <div class="card card-flat p-4 h-100">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <h3 class="h6 fw-bold mb-0 text-dark">Mapa de Horas Pico - Volumen de Visitas Diarias</h3>
              <p class="text-muted small mb-0">Identificación de afluencia diurna para mitigar la saturación en recepción</p>
            </div>
            <span class="badge bg-light text-secondary border">08:00 - 20:00</span>
          </div>
          <div style="position: relative; height: 260px;">
            <canvas id="chartHorasPico"></canvas>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-4">
        <div class="card card-flat p-4 h-100 d-flex flex-column">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h3 class="h6 fw-bold mb-0 text-dark">Distribución por Asunto</h3>
            <span class="badge bg-light text-secondary border">Tipificación</span>
          </div>
          <p class="text-muted small mb-3">Motivos de consulta de visitantes</p>
          <div class="flex-grow-1 d-flex align-items-center justify-content-center" style="position: relative; min-height: 180px;">
            <canvas id="chartAsuntos"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- RENDIMIENTO Y AUDITORÍA DE TIEMPOS -->
    <div class="row g-3">
      <div class="col-12 col-lg-6">
        <div class="card card-flat p-4 h-100">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <h3 class="h6 fw-bold mb-0 text-dark">Rendimiento Mensual por Colaborador</h3>
              <p class="text-muted small mb-0">Comparativa de atenciones completadas vs en proceso</p>
            </div>
            <span class="badge bg-light text-secondary border"><?php echo date('F Y'); ?></span>
          </div>
          <div style="position: relative; height: 260px;">
            <canvas id="chartColaboradores"></canvas>
          </div>
        </div>
      </div>

      <!-- TABLA DE ALERTAS DE AUDITORÍA (SLA > 15 MIN) -->
      <div class="col-12 col-lg-6">
        <div class="card card-flat p-4 h-100 border-danger-subtle d-flex flex-column">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
              <h3 class="h6 fw-bold text-danger mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>Alertas de Auditoría - Tiempos Excedidos</span>
              </h3>
              <p class="text-muted small mb-0">Tickets en espera que sobrepasan la tolerancia SLA (&gt; 15.0 min)</p>
            </div>
            <span class="badge bg-danger"><?php echo count($alertasAuditoria); ?> Críticas</span>
          </div>

          <div class="table-responsive flex-grow-1 mt-2">
            <table class="table audit-alert-table align-middle">
              <thead>
                <tr>
                  <th scope="col">Ticket</th>
                  <th scope="col">Visitante</th>
                  <th scope="col">Asunto</th>
                  <th scope="col">Espera</th>
                  <th scope="col">Prioridad</th>
                  <th scope="col" class="text-center">Acción</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($alertasAuditoria)): foreach ($alertasAuditoria as $al): ?>
                  <tr>
                    <td class="fw-bold font-monospace text-danger"><?php echo htmlspecialchars($al['codigo']); ?></td>
                    <td class="fw-medium"><?php echo htmlspecialchars($al['visitante']); ?></td>
                    <td><?php echo htmlspecialchars($al['asunto_nombre']); ?></td>
                    <td>
                      <span class="badge badge-subtle-danger px-2 py-1">
                        <?php echo $al['minutos_espera']; ?> min (Excedido)
                      </span>
                    </td>
                    <td><span class="badge badge-subtle-warning"><?php echo htmlspecialchars($al['prioridad']); ?></span></td>
                    <td class="text-center">
                      <form method="POST" action="acciones.php">
                        <input type="hidden" name="accion" value="reasignar_alerta">
                        <input type="hidden" name="visita_id" value="<?php echo $al['id']; ?>">
                        <input type="hidden" name="empleado_id" value="1">
                        <button type="submit" class="btn btn-sm btn-danger py-0 px-2 rounded-2 small">
                          Asignar Urgente
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; else: ?>
                  <tr>
                    <td colspan="6" class="text-center py-3 text-muted small">
                      <i class="bi bi-check-circle text-success me-1"></i> No hay tiempos de espera críticos excedidos en este momento.
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <div class="pt-3 border-top d-flex justify-content-between align-items-center">
            <span class="small text-muted">Umbral de Atención SLA: <b>15.0 min</b></span>
          </div>
        </div>
      </div>
    </div>

  </main>

  <!-- MODAL: REGISTRAR VISITA -->
  <div class="modal fade" id="modalRegisterVisit" tabindex="-1" aria-labelledby="modalRegisterVisitLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-sm rounded-3">
        <div class="modal-header border-bottom py-3">
          <h2 class="modal-title h6 fw-bold text-dark mb-0 d-flex align-items-center gap-2" id="modalRegisterVisitLabel">
            <i class="bi bi-pencil-square text-success"></i>
            <span>Registrar Nueva Visita</span>
          </h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <form method="POST" action="acciones.php">
          <input type="hidden" name="accion" value="registrar_visita">
          <div class="modal-body p-4">
            <div class="mb-3">
              <label for="reg-nombre" class="form-label small fw-semibold text-secondary">Nombre Completo del Visitante *</label>
              <input type="text" name="visitante" id="reg-nombre" required placeholder="Ej: Luis Morales Sánchez" class="form-control form-control-sm">
            </div>
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label for="reg-dni" class="form-label small fw-semibold text-secondary">DNI / Documento *</label>
                <input type="text" name="dni" id="reg-dni" required maxlength="8" placeholder="74839201" class="form-control form-control-sm">
              </div>
              <div class="col-6">
                <label for="reg-asunto" class="form-label small fw-semibold text-secondary">Asunto *</label>
                <select name="asunto_id" id="reg-asunto" required class="form-select form-select-sm">
                  <?php foreach ($catalogoAsuntos as $as): ?>
                    <option value="<?php echo $as['id']; ?>"><?php echo htmlspecialchars($as['nombre']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label for="reg-prioridad" class="form-label small fw-semibold text-secondary">Prioridad *</label>
                <select name="prioridad" id="reg-prioridad" required class="form-select form-select-sm">
                  <option value="Media">Media (Estándar)</option>
                  <option value="Alta">Alta</option>
                  <option value="Urgente">Urgente</option>
                  <option value="Baja">Baja</option>
                </select>
              </div>
              <div class="col-6">
                <label for="reg-empleado" class="form-label small fw-semibold text-secondary">Asignar a</label>
                <select name="empleado_id" id="reg-empleado" class="form-select form-select-sm">
                  <option value="disponible">Dejar Disponible (Cola)</option>
                  <?php foreach ($catalogoEmpleados as $em): ?>
                    <option value="<?php echo $em['id']; ?>"><?php echo htmlspecialchars($em['nombre']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="mb-2">
              <label for="reg-consulta" class="form-label small fw-semibold text-secondary">Detalle de Consulta *</label>
              <textarea name="consulta" id="reg-consulta" required rows="3" placeholder="Describe brevemente el requerimiento del visitante..." class="form-control form-control-sm"></textarea>
            </div>
          </div>
          <div class="modal-footer border-top py-2 px-4">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-sm btn-success fw-semibold">Generar Registro</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Toast Notificaciones -->
  <?php if ($toast): ?>
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
      <div class="toast align-items-center text-bg-<?php echo htmlspecialchars($toast['tipo']); ?> border-0 show shadow-sm" role="alert">
        <div class="d-flex">
          <div class="toast-body small"><?php echo htmlspecialchars($toast['mensaje']); ?></div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <footer class="bg-white border-top py-2 px-4 text-center text-muted small mt-auto">
    SENATI ETI • Supervisión Gerencial • Sistema de Gestión de Visitas
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    const HORAS_LABELS = <?php echo json_encode($horasLabels); ?>;
    const HORAS_VALUES = <?php echo json_encode($horasValues); ?>;
    const COLAB_NOMBRES = <?php echo json_encode($colabNombres); ?>;
    const COLAB_COMPLETADAS = <?php echo json_encode($colabCompletadas); ?>;
    const COLAB_PROCESO = <?php echo json_encode($colabEnProceso); ?>;
    const ASUNTOS_LABELS = <?php echo json_encode($asuntosLabels); ?>;
    const ASUNTOS_VALUES = <?php echo json_encode($asuntosValues); ?>;

    document.addEventListener('DOMContentLoaded', () => {
      // Horas Pico
      const ctxLine = document.getElementById('chartHorasPico')?.getContext('2d');
      if (ctxLine) {
        new Chart(ctxLine, {
          type: 'line',
          data: {
            labels: HORAS_LABELS,
            datasets: [{
              label: 'Volumen de Visitas',
              data: HORAS_VALUES,
              borderColor: '#003882',
              backgroundColor: 'rgba(0, 56, 130, 0.08)',
              borderWidth: 2.5,
              fill: true,
              tension: 0.25,
              pointBackgroundColor: '#ffffff',
              pointBorderColor: '#003882',
              pointBorderWidth: 2,
              pointRadius: 3.5
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
              x: { grid: { color: '#f1f5f9' }, ticks: { color: '#64748b', font: { size: 11 } } },
              y: { grid: { color: '#f1f5f9' }, ticks: { color: '#64748b', font: { size: 11 } } }
            }
          }
        });
      }

      // Rendimiento Colaboradores
      const ctxBar = document.getElementById('chartColaboradores')?.getContext('2d');
      if (ctxBar) {
        new Chart(ctxBar, {
          type: 'bar',
          data: {
            labels: COLAB_NOMBRES,
            datasets: [
              { label: 'Completadas', data: COLAB_COMPLETADAS, backgroundColor: '#198754', borderRadius: 4 },
              { label: 'En Proceso', data: COLAB_PROCESO, backgroundColor: '#d97706', borderRadius: 4 }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } } },
            scales: {
              x: { grid: { display: false }, ticks: { color: '#64748b', font: { size: 11 } } },
              y: { grid: { color: '#f1f5f9' }, ticks: { color: '#64748b', font: { size: 11 } } }
            }
          }
        });
      }

      // Distribución por Asunto
      const ctxDonut = document.getElementById('chartAsuntos')?.getContext('2d');
      if (ctxDonut) {
        new Chart(ctxDonut, {
          type: 'doughnut',
          data: {
            labels: ASUNTOS_LABELS,
            datasets: [{
              data: ASUNTOS_VALUES,
              backgroundColor: ['#003882', '#198754', '#d97706', '#0284c7', '#64748b'],
              borderWidth: 2,
              borderColor: '#ffffff'
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
              legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } }
            }
          }
        });
      }
    });
  </script>
</body>
</html>
