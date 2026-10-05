<?php
/**
 * SENATI ETI - Panel de Empleado (Gestión Operativa)
 * Ruta Protegida: Rol 'empleado' o 'administrador'
 */

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/auth_check.php';

// Validar autenticación de sesión
$usuario = verificarSesion(['empleado', 'administrador']);

$pdo = obtenerConexion();
if (!$pdo) {
    die("Error crítico: No se pudo conectar a la base de datos.");
}

$empleadoId = $usuario['empleado_id'] ?? 1;

// Parámetros de Pestañas y Filtros
$tab = $_GET['tab'] ?? 'asignadas';
$filtroAsunto = $_GET['asunto'] ?? '';
$filtroEstado = $_GET['estado'] ?? '';
$filtroPrioridad = $_GET['prioridad'] ?? '';
$filtroFecha = $_GET['fecha'] ?? '';

// 1. Catálogo de Asuntos
$stmtAsuntos = $pdo->query("SELECT * FROM asuntos WHERE activo = 1 ORDER BY id ASC");
$catalogoAsuntos = $stmtAsuntos->fetchAll();

// 2. KPIs del Empleado Activo
$sqlEmpKPI = "SELECT 
    COUNT(*) AS total,
    SUM(CASE WHEN estado = 'Completada' THEN 1 ELSE 0 END) AS completadas,
    SUM(CASE WHEN estado = 'En Proceso' THEN 1 ELSE 0 END) AS en_proceso,
    SUM(CASE WHEN asunto_id = 1 THEN 1 ELSE 0 END) AS matricula,
    SUM(CASE WHEN asunto_id != 1 THEN 1 ELSE 0 END) AS otros
FROM visitas 
WHERE empleado_id = :emp_id";
$stmtEmpKPI = $pdo->prepare($sqlEmpKPI);
$stmtEmpKPI->execute([':emp_id' => $empleadoId]);
$kpisEmp = $stmtEmpKPI->fetch();

$empTotal = (int)($kpisEmp['total'] ?? 0);
$empCompletadas = (int)($kpisEmp['completadas'] ?? 0);
$empEnProceso = (int)($kpisEmp['en_proceso'] ?? 0);
$empMatricula = (int)($kpisEmp['matricula'] ?? 0);
$empOtros = (int)($kpisEmp['otros'] ?? 0);
$empEfectividad = ($empTotal > 0) ? round(($empCompletadas / $empTotal) * 100) : 0;

// 3. Conteo de Visitas Disponibles
$stmtDispCount = $pdo->query("SELECT COUNT(*) FROM visitas WHERE empleado_id IS NULL OR estado = 'En Espera'");
$disponiblesCount = (int)$stmtDispCount->fetchColumn();

// 4. Consulta de Visitas Asignadas
$sqlAsignadas = "SELECT v.*, a.nombre AS asunto_nombre 
                 FROM visitas v 
                 JOIN asuntos a ON v.asunto_id = a.id 
                 WHERE v.empleado_id = :emp_id";
$paramsAsignadas = [':emp_id' => $empleadoId];

if (!empty($filtroAsunto)) {
    $sqlAsignadas .= " AND a.nombre = :asunto";
    $paramsAsignadas[':asunto'] = $filtroAsunto;
}
if (!empty($filtroEstado)) {
    $sqlAsignadas .= " AND v.estado = :estado";
    $paramsAsignadas[':estado'] = $filtroEstado;
}
if (!empty($filtroPrioridad)) {
    $sqlAsignadas .= " AND v.prioridad = :prioridad";
    $paramsAsignadas[':prioridad'] = $filtroPrioridad;
}
if (!empty($filtroFecha)) {
    $sqlAsignadas .= " AND v.fecha_registro = :fecha";
    $paramsAsignadas[':fecha'] = $filtroFecha;
}
$sqlAsignadas .= " ORDER BY v.t_registro DESC";

$stmtAsignadas = $pdo->prepare($sqlAsignadas);
$stmtAsignadas->execute($paramsAsignadas);
$visitasAsignadas = $stmtAsignadas->fetchAll();

// 5. Consulta de Visitas Disponibles (Cola Compartida)
$sqlDisponibles = "SELECT v.*, a.nombre AS asunto_nombre 
                   FROM visitas v 
                   JOIN asuntos a ON v.asunto_id = a.id 
                   WHERE (v.empleado_id IS NULL OR v.estado = 'En Espera')";
$paramsDisp = [];

if (!empty($filtroAsunto)) {
    $sqlDisponibles .= " AND a.nombre = :asunto";
    $paramsDisp[':asunto'] = $filtroAsunto;
}
if (!empty($filtroPrioridad)) {
    $sqlDisponibles .= " AND v.prioridad = :prioridad";
    $paramsDisp[':prioridad'] = $filtroPrioridad;
}
$sqlDisponibles .= " ORDER BY (v.prioridad = 'Urgente') DESC, v.t_registro ASC";

$stmtDisponibles = $pdo->prepare($sqlDisponibles);
$stmtDisponibles->execute($paramsDisp);
$visitasDisponibles = $stmtDisponibles->fetchAll();

$toast = $_SESSION['toast'] ?? null;
unset($_SESSION['toast']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel Empleado - Senati ETI</title>

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
</head>
<body class="bg-light d-flex flex-column min-vh-100">

  <!-- Barra Superior Institucional SENATI (Limpia, Sin senati-topbar) -->
  <nav class="senati-navbar text-white py-3 px-3 px-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
      
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-circle bg-white bg-opacity-10 border border-white border-opacity-25 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
          <i class="bi bi-person-vcard fs-5 text-white"></i>
        </div>
        <div>
          <h1 class="h5 fw-bold mb-0 text-white">Panel Empleado</h1>
          <p class="small text-white-50 mb-0"><?php echo htmlspecialchars($usuario['nombre']); ?> (<?php echo htmlspecialchars($usuario['username']); ?>)</p>
        </div>
      </div>

      <div class="d-flex flex-wrap align-items-center gap-2">
        <?php if ($usuario['rol'] === 'administrador'): ?>
          <a href="dashboard.php" class="btn btn-sm btn-light d-flex align-items-center gap-2 fw-semibold px-3 py-2">
            <i class="bi bi-speedometer2 text-primary"></i>
            <span>Dashboard Admin</span>
          </a>
        <?php endif; ?>
        <button type="button" class="btn btn-sm btn-success d-flex align-items-center gap-2 fw-semibold px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalRegisterVisit">
          <i class="bi bi-plus-circle"></i>
          <span>Registrar Visita</span>
        </button>
        <a href="logout.php" class="btn btn-sm btn-danger d-flex align-items-center gap-2 fw-semibold px-3 py-2">
          <i class="bi bi-box-arrow-right"></i>
          <span>Cerrar Sesión</span>
        </a>
      </div>

    </div>
  </nav>

  <!-- Contenido Operativo -->
  <main class="container-fluid px-3 px-lg-5 py-4 flex-grow-1">

    <!-- TARJETAS DE MÉTRICAS DINÁMICAS (6 KPIS PLANOS) -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-4 col-xl-2">
        <div class="kpi-card">
          <div class="kpi-title">Mis Atenciones</div>
          <div class="kpi-value text-primary" id="kpi-activos"><?php echo $empTotal; ?></div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-xl-2">
        <div class="kpi-card">
          <div class="kpi-title">Completadas</div>
          <div class="kpi-value text-success" id="kpi-completadas-hoy"><?php echo $empCompletadas; ?></div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-xl-2">
        <div class="kpi-card">
          <div class="kpi-title">Efectividad</div>
          <div class="kpi-value text-dark" id="kpi-efectividad"><?php echo $empEfectividad; ?>%</div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-xl-2">
        <div class="kpi-card">
          <div class="kpi-title">En Proceso</div>
          <div class="kpi-value text-warning" id="kpi-en-proceso"><?php echo $empEnProceso; ?></div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-xl-2">
        <div class="kpi-card">
          <div class="kpi-title">Matrícula</div>
          <div class="kpi-value text-primary"><?php echo $empMatricula; ?></div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-xl-2">
        <div class="kpi-card">
          <div class="kpi-title">Pagos/Tutoría/Otros</div>
          <div class="kpi-value text-success"><?php echo $empOtros; ?></div>
        </div>
      </div>
    </div>

    <!-- FILTROS DE CLASIFICACIÓN -->
    <div class="card card-flat p-4 mb-4">
      <div class="d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-funnel text-primary fs-5"></i>
        <h2 class="h6 fw-bold mb-0 text-dark">Filtros de Clasificación</h2>
      </div>

      <form method="GET" action="empleado.php" class="row g-2 mb-3">
        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">

        <div class="col-12 col-sm-6 col-lg-3">
          <select name="asunto" class="form-select form-select-sm">
            <option value="">Todos los asuntos</option>
            <?php foreach ($catalogoAsuntos as $asunto): ?>
              <option value="<?php echo htmlspecialchars($asunto['nombre']); ?>" <?php echo ($filtroAsunto === $asunto['nombre']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($asunto['nombre']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
          <select name="estado" class="form-select form-select-sm">
            <option value="">Todos los estados</option>
            <option value="En Proceso" <?php echo ($filtroEstado === 'En Proceso') ? 'selected' : ''; ?>>En Proceso</option>
            <option value="Completada" <?php echo ($filtroEstado === 'Completada') ? 'selected' : ''; ?>>Completada</option>
            <option value="En Espera" <?php echo ($filtroEstado === 'En Espera') ? 'selected' : ''; ?>>En Espera</option>
          </select>
        </div>
        <div class="col-12 col-sm-6 col-lg-2">
          <select name="prioridad" class="form-select form-select-sm">
            <option value="">Todas las prioridades</option>
            <option value="Alta" <?php echo ($filtroPrioridad === 'Alta') ? 'selected' : ''; ?>>Alta</option>
            <option value="Media" <?php echo ($filtroPrioridad === 'Media') ? 'selected' : ''; ?>>Media</option>
            <option value="Baja" <?php echo ($filtroPrioridad === 'Baja') ? 'selected' : ''; ?>>Baja</option>
            <option value="Urgente" <?php echo ($filtroPrioridad === 'Urgente') ? 'selected' : ''; ?>>Urgente</option>
          </select>
        </div>
        <div class="col-12 col-sm-6 col-lg-2">
          <input type="date" name="fecha" value="<?php echo htmlspecialchars($filtroFecha); ?>" class="form-control form-control-sm">
        </div>
        <div class="col-12 col-lg-2">
          <button type="submit" class="btn btn-sm btn-senati-primary w-100 d-flex align-items-center justify-content-center gap-1">
            <i class="bi bi-search"></i>
            <span>Filtrar</span>
          </button>
        </div>
      </form>

      <div class="d-flex align-items-center gap-2 mb-3">
        <a href="empleado.php?tab=<?php echo htmlspecialchars($tab); ?>" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
          <i class="bi bi-eraser"></i>
          <span>Limpiar</span>
        </a>
      </div>

      <hr class="text-muted opacity-25 my-2">

      <div class="d-flex flex-wrap align-items-center gap-2 pt-2">
        <a href="acciones.php?accion=exportar_csv&tipo=mes" class="btn btn-export-green d-flex align-items-center gap-1">
          <i class="bi bi-file-earmark-spreadsheet"></i>
          <span>Exportar Mes</span>
        </a>
        <a href="acciones.php?accion=exportar_csv&tipo=trimestre" class="btn btn-export-green d-flex align-items-center gap-1">
          <i class="bi bi-calendar-check"></i>
          <span>Exportar Trimestre</span>
        </a>
        <a href="acciones.php?accion=exportar_csv&tipo=filtrado" class="btn btn-export-darkgreen d-flex align-items-center gap-1">
          <i class="bi bi-file-earmark-arrow-down"></i>
          <span>Exportar Filtrado</span>
        </a>
      </div>
    </div>

    <!-- PESTAÑAS DE TRABAJO (FLAT WORKFLOW TABS) -->
    <ul class="nav nav-workflow d-flex gap-2 mb-3">
      <li class="nav-item">
        <a href="?tab=asignadas" class="nav-link <?php echo ($tab === 'asignadas') ? 'active' : ''; ?> d-flex align-items-center gap-2">
          <i class="bi bi-person-check"></i>
          <span>Mis Visitas Asignadas</span>
          <span class="badge bg-white text-primary rounded-pill small"><?php echo count($visitasAsignadas); ?></span>
        </a>
      </li>
      <li class="nav-item">
        <a href="?tab=disponibles" class="nav-link <?php echo ($tab === 'disponibles') ? 'active' : ''; ?> d-flex align-items-center gap-2">
          <i class="bi           <span>Visitas Disponibles</span>
          <span class="badge bg-secondary rounded-pill small" id="badge-cola-disponible"><?php echo $disponiblesCount; ?></span>
        </a>
      </li>
    </ul>

    <!-- TABLA DE VISITAS -->
    <div class="card card-flat overflow-hidden">
      <div class="p-3 px-4 border-bottom bg-white">
        <h3 class="h6 fw-bold mb-0 text-dark">
          <?php if ($tab === 'asignadas'): ?>
            <i class="bi bi-person-check text-primary me-2"></i>Mis Visitas Asignadas
          <?php else: ?>
            <i class="bi bi-inbox text-primary me-2"></i>Visitas Disponibles (Cola Compartida)
          <?php endif; ?>
        </h3>
        <p class="text-muted small mb-0">
          <?php echo ($tab === 'asignadas') ? 'Responde las consultas y actualiza el estado de las visitas sin recargar la página.' : 'Toma tickets de la cola para una distribución equitativa de carga laboral.'; ?>
        </p>
      </div>

      <div class="table-responsive">
        <table class="table table-senati table-hover align-middle">
          <thead>
            <tr>
              <th scope="col">Código</th>
              <th scope="col">Visitante</th>
              <th scope="col">Asunto</th>
              <th scope="col" style="min-width: 180px;">Consulta</th>
              <th scope="col" style="min-width: 180px;">Respuesta</th>
              <th scope="col">Prioridad</th>
              <th scope="col">Estado</th>
              <th scope="col">Fecha</th>
              <th scope="col" class="text-center">Acciones</th>
            </tr>
          </thead>
          <tbody id="<?php echo ($tab === 'disponibles') ? 'tbody-disponibles' : 'tbody-asignadas'; ?>">
            <?php 
            $listaActual = ($tab === 'asignadas') ? $visitasAsignadas : $visitasDisponibles;
            if (!empty($listaActual)): 
              foreach ($listaActual as $v):
                $badgePrioridad = match($v['prioridad']) {
                  'Alta' => 'badge-subtle-warning',
                  'Urgente' => 'badge-subtle-danger',
                  default => 'badge-subtle-primary'
                };
                $badgeEstado = match($v['estado']) {
                  'Completada' => 'badge-subtle-success',
                  'En Proceso' => 'badge-subtle-warning',
                  default => 'badge-subtle-danger'
                };
            ?>
              <tr id="fila-ticket-<?php echo $v['id']; ?>">
                <td class="fw-bold font-monospace text-dark"><?php echo htmlspecialchars($v['codigo']); ?></td>
                <td>
                  <div class="fw-semibold text-dark"><?php echo htmlspecialchars($v['visitante']); ?></div>
                  <div class="text-muted small">DNI: <?php echo htmlspecialchars($v['dni'] ?? 'Sin reg.'); ?></div>
                </td>
                <td><span class="badge bg-light text-secondary border px-2 py-1"><?php echo htmlspecialchars($v['asunto_nombre']); ?></span></td>
                <td class="text-muted text-truncate" style="max-width: 220px;" title="<?php echo htmlspecialchars($v['consulta']); ?>">
                  <?php echo htmlspecialchars($v['consulta']); ?>
                </td>
                <td class="text-muted text-truncate" style="max-width: 220px;" title="<?php echo htmlspecialchars($v['respuesta'] ?? ''); ?>">
                  <?php echo !empty($v['respuesta']) ? htmlspecialchars($v['respuesta']) : '<span class="text-muted fst-italic">Pendiente</span>'; ?>
                </td>
                <td><span class="badge <?php echo $badgePrioridad; ?> px-2 py-1 rounded-pill"><?php echo htmlspecialchars($v['prioridad']); ?></span></td>
                <td><span class="badge <?php echo $badgeEstado; ?> px-2 py-1 rounded-pill badge-estado"><?php echo htmlspecialchars($v['estado']); ?></span></td>
                <td class="text-nowrap small text-muted">
                  <div><?php echo htmlspecialchars($v['fecha_registro']); ?></div>
                  <div class="text-secondary small"><?php echo htmlspecialchars($v['hora_registro']); ?></div>
                </td>
                <td class="text-center celda-acciones">
                  <?php if ($tab === 'asignadas'): ?>
                    <?php if ($v['estado'] !== 'Completada' && $v['estado'] !== 'Cancelada'): ?>
                      <div class="d-flex justify-content-center align-items-center gap-1.5">
                        <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2 rounded-2 d-inline-flex align-items-center gap-1"
                                title="Atender consulta y registrar dictamen"
                                onclick="completarAtencionAjax(<?php echo $v['id']; ?>, '<?php echo $v['codigo']; ?>', '<?php echo htmlspecialchars($v['visitante'], ENT_QUOTES); ?>')">
                          <i class="bi bi-check2-circle"></i>
                          <span>Atender</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 rounded-2 d-inline-flex align-items-center gap-1"
                                title="Marcar como abandonado"
                                onclick="cancelarTicketAjax(<?php echo $v['id']; ?>, '<?php echo $v['codigo']; ?>')">
                          <i class="bi bi-x-circle"></i>
                          <span class="d-none d-md-inline">Abandonado</span>
                        </button>
                      </div>
                    <?php else: ?>
                      <span class="badge bg-light text-secondary border small">
                        <i class="bi bi-check2-all text-success me-1"></i> <?php echo $v['estado']; ?>
                      </span>
                    <?php endif; ?>
                  <?php else: ?>
                    <button type="button" class="btn btn-sm btn-senati-primary py-1 px-2.5 rounded-2 d-inline-flex align-items-center gap-1"
                            onclick="tomarTicketAjax(<?php echo $v['id']; ?>, '<?php echo $v['codigo']; ?>')">
                      <i class="bi bi-hand-index-thumb"></i>
                      <span>Tomar Ticket</span>
                    </button>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; else: ?>foreach; else: ?>
              <tr>
                <td colspan="9" class="p-5 text-center text-muted">
                  <i class="bi bi-inbox fs-2 text-secondary opacity-50 mb-2 d-block"></i>
                  <p class="small mb-0">No se encontraron visitas registradas con los criterios seleccionados.</p>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
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
        <form id="formModalRegisterVisit">
          <div class="modal-body p-4">
            <div class="mb-3">
              <label for="reg-nombre" class="form-label small fw-semibold text-secondary">Nombre Completo del Visitante *</label>
              <input type="text" name="visitante" id="reg-nombre" required placeholder="Ej: Luis Morales Sánchez" class="form-control form-control-sm">
            </div>
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label for="reg-dni" class="form-label small fw-semibold text-secondary">DNI / Documento</label>
                <input type="text" name="dni" id="reg-dni" maxlength="15" placeholder="8 dígitos" class="form-control form-control-sm">
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
                <label for="reg-empleado" class="form-label small fw-semibold text-secondary">Asignación</label>
                <select name="empleado_id" id="reg-empleado" class="form-select form-select-sm">
                  <option value="disponible">Cola Compartida (Disponible)</option>
                  <option value="<?php echo $empleadoId; ?>">Asignármelo a mí (En Proceso)</option>
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
            <button type="submit" class="btn btn-sm btn-success fw-semibold d-inline-flex align-items-center gap-1.5">
              <i class="bi bi-check-circle"></i>
              <span>Generar Registro</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- MODAL: ATENDER / RESOLVER CONSULTA -->
  <div class="modal fade" id="modalRespondVisit" tabindex="-1" aria-labelledby="modalRespondVisitLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-sm rounded-3">
        <div class="modal-header border-bottom py-3">
          <h2 class="modal-title h6 fw-bold text-dark mb-0 d-flex align-items-center gap-2" id="modalRespondVisitLabel">
            <i class="bi bi-pencil-square text-primary"></i>
            <span id="resp-ticket-code">Ticket</span>
          </h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <form id="formModalRespond">
          <input type="hidden" name="visita_id" id="resp-ticket-id">
          
          <div class="modal-body p-4">
            <div class="bg-light p-3 rounded-2 border mb-3">
              <div class="d-flex justify-content-between align-items-center small text-muted mb-1">
                <span id="resp-visitor-info" class="fw-semibold text-dark">Visitante:</span>
                <span id="resp-prioridad-tag" class="badge bg-secondary">Alta</span>
              </div>
              <div class="small text-muted mb-1"><b>Asunto:</b> <span id="resp-asunto-tag" class="text-primary fw-medium"></span></div>
              <div class="small text-dark mt-2 p-2 bg-white rounded border">
                <b>Consulta:</b> <span id="resp-consulta-text" class="text-muted fst-italic"></span>
              </div>
            </div>

            <div class="mb-3">
              <label for="resp-respuesta-text" class="form-label small fw-semibold text-secondary">Respuesta / Dictamen del Colaborador *</label>
              <textarea name="respuesta" id="resp-respuesta-text" required rows="3" placeholder="Ingresa la solución o respuesta brindada al visitante..." class="form-control form-control-sm"></textarea>
            </div>

            <div class="mb-2">
              <label for="resp-estado-select" class="form-label small fw-semibold text-secondary">Actualizar Estado *</label>
              <select name="estado" id="resp-estado-select" required class="form-select form-select-sm">
                <option value="Completada">Completada (Atención Concluida con Éxito)</option>
                <option value="En Proceso">En Proceso (Seguimiento requerido)</option>
              </select>
            </div>
          </div>
          <div class="modal-footer border-top py-2 px-4">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-sm btn-senati-primary fw-semibold d-inline-flex align-items-center gap-1.5">
              <i class="bi bi-save"></i>
              <span>Guardar y Finalizar</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <footer class="bg-white border-top py-2 px-4 text-center text-muted small mt-auto">
    SENATI ETI • Panel de Empleado • Sistema de Gestión de Visitas
  </footer>

  <!-- SweetAlert2 Oficial JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- Bootstrap 5 Bundle JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Script SENATI UX (Fetch + SweetAlert2) -->
  <script src="js/senati-ux.js"></script>

  <script>
    function cargarModalRespuesta(visita) {
      document.getElementById('resp-ticket-id').value = visita.id;
      document.getElementById('resp-ticket-code').textContent = 'Ticket ' + visita.codigo;
      document.getElementById('resp-visitor-info').textContent = 'Visitante: ' + visita.visitante + ' (DNI: ' + (visita.dni || 'Sin registrar') + ')';
      document.getElementById('resp-asunto-tag').textContent = visita.asunto_nombre;
      document.getElementById('resp-prioridad-tag').textContent = visita.prioridad;
      document.getElementById('resp-consulta-text').textContent = visita.consulta;
      document.getElementById('resp-respuesta-text').value = visita.respuesta || '';
      document.getElementById('resp-estado-select').value = (visita.estado === 'Completada') ? 'Completada' : 'En Proceso';

      const modalEl = document.getElementById('modalRespondVisit');
      const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
      modal.show();
    }

    // Manejar envío asíncrono del modal de registro de visita
    document.addEventListener('DOMContentLoaded', () => {
      const formModalReg = document.getElementById('formModalRegisterVisit');
      if (formModalReg) {
        formModalReg.addEventListener('submit', async (e) => {
          e.preventDefault();
          const btn = formModalReg.querySelector('button[type="submit"]');
          btn.disabled = true;
          try {
            const formData = new FormData(formModalReg);
            const res = await fetch('ajax_registrar.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (res.ok && data.status === 'success') {
              const modalEl = document.getElementById('modalRegisterVisit');
              bootstrap.Modal.getInstance(modalEl).hide();
              formModalReg.reset();
              await SenatiUX.showTicketVoucher(data.data);
              // Recargar suavemente para reflejar en la pestaña activa
              window.location.reload();
            } else {
              SenatiUX.alertError('Error', data.message);
            }
          } catch (err) {
            SenatiUX.alertError('Error de Conexión', 'No se pudo registrar la visita.');
          } finally {
            btn.disabled = false;
          }
        });
      }

      // Manejar envío asíncrono del modal de atención/respuesta
      const formModalResp = document.getElementById('formModalRespond');
      if (formModalResp) {
        formModalResp.addEventListener('submit', async (e) => {
          e.preventDefault();
          const ticketId = document.getElementById('resp-ticket-id').value;
          const respuesta = document.getElementById('resp-respuesta-text').value.trim();
          const estado = document.getElementById('resp-estado-select').value;

          if (!respuesta) {
            SenatiUX.alertError('Campo Requerido', 'Por favor ingresa la respuesta o dictamen de la atención.');
            return;
          }

          try {
            const res = await fetch('ajax_actualizar_estado.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({
                accion: (estado === 'Completada') ? 'completar' : 'en_proceso',
                ticket_id: ticketId,
                respuesta: respuesta
              })
            });

            const data = await res.json();
            if (res.ok && data.status === 'success') {
              const modalEl = document.getElementById('modalRespondVisit');
              bootstrap.Modal.getInstance(modalEl).hide();
              await SenatiUX.alertSuccess('¡Atención Actualizada!', data.message);
              
              // Actualizar fila en el DOM
              const fila = document.getElementById(`fila-ticket-${ticketId}`);
              if (fila) {
                const badge = fila.querySelector('.badge-estado');
                if (badge) {
                  badge.className = (estado === 'Completada') 
                    ? 'badge badge-subtle-success px-2 py-1 rounded-pill badge-estado' 
                    : 'badge badge-subtle-warning px-2 py-1 rounded-pill badge-estado';
                  badge.textContent = estado;
                }
              }
              SenatiUX.updateKpiBadges(data.kpis);
            } else {
              SenatiUX.alertError('Error', data.message);
            }
          } catch (err) {
            SenatiUX.alertError('Error', 'No se pudo actualizar el estado de la visita.');
          }
        });
      }
    });
  </script>
</body>
</html>
