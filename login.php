<?php
/**
 * SENATI ETI - Formulario de Inicio de Sesión
 * Diseño Minimalista en Bootstrap 5 con Identidad SENATI
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si ya tiene sesión activa, redirigir a su panel
if (isset($_SESSION['usuario']['rol'])) {
    if ($_SESSION['usuario']['rol'] === 'administrador') {
        header('Location: dashboard.php');
        exit;
    } elseif ($_SESSION['usuario']['rol'] === 'empleado') {
        header('Location: empleado.php');
        exit;
    }
}

$error = $_GET['error'] ?? '';
$msg = $_GET['msg'] ?? '';

$mensajeAlerta = '';
$tipoAlerta = 'danger';

if ($error === 'credenciales_invalidas') {
    $mensajeAlerta = 'Usuario o contraseña incorrectos. Por favor, verifica tus datos.';
} elseif ($error === 'no_auth') {
    $mensajeAlerta = 'Acceso restringido: Debes iniciar sesión para ingresar al sistema.';
} elseif ($error === 'acceso_denegado') {
    $mensajeAlerta = 'Permiso denegado: Tu perfil no tiene autorización para acceder a esa sección.';
} elseif ($error === 'campos_vacios') {
    $mensajeAlerta = 'Por favor completa todos los campos del formulario.';
} elseif ($error === 'db_error') {
    $mensajeAlerta = 'Error de conexión con la base de datos MySQL (dashboardt8_bd).';
}

if ($msg === 'sesion_cerrada') {
    $mensajeAlerta = 'Has cerrado sesión correctamente. ¡Hasta pronto!';
    $tipoAlerta = 'success';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar Sesión - Senati ETI</title>

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
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100 py-5">

  <div class="container" style="max-width: 440px;">
    
    <!-- Tarjeta Principal de Login (Flat Design) -->
    <div class="card card-flat p-4 p-md-5 bg-white">
      
      <!-- Encabezado Institucional -->
      <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-3 px-3 py-1 mb-2 fw-bold text-uppercase small" style="letter-spacing: 0.08em;">
          SENATI ETI
        </div>
        <h1 class="h4 fw-bold text-dark mb-1">Acceso al Sistema</h1>
        <p class="text-muted small mb-0">Gestión de Visitas y Trazabilidad</p>
      </div>

      <!-- Alertas de Estado -->
      <?php if (!empty($mensajeAlerta)): ?>
        <div class="alert alert-<?php echo $tipoAlerta; ?> alert-dismissible fade show py-2 px-3 small d-flex align-items-center mb-4" role="alert">
          <i class="bi <?php echo ($tipoAlerta === 'success') ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-danger'; ?> me-2"></i>
          <div><?php echo htmlspecialchars($mensajeAlerta); ?></div>
          <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
      <?php endif; ?>

      <!-- Formulario de Autenticación -->
      <form action="login_process.php" method="POST">
        <div class="mb-3">
          <label for="username" class="form-label small fw-semibold text-secondary">Usuario o Código</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
            <input type="text" name="username" id="username" required autofocus placeholder="Ej: Admin o Empleado" class="form-control form-control-sm border-start-0">
          </div>
        </div>

        <div class="mb-4">
          <label for="password" class="form-label small fw-semibold text-secondary">Contraseña</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
            <input type="password" name="password" id="password" required placeholder="••••••••" class="form-control form-control-sm border-start-0">
          </div>
        </div>

        <button type="submit" class="btn btn-senati-primary w-100 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2 mb-3">
          <i class="bi bi-box-arrow-in-right"></i>
          <span>Ingresar al Sistema</span>
        </button>
      </form>

      <!-- Panel de Credenciales de Prueba para Evaluación -->
      <div class="bg-light p-3 rounded-2 border mt-3 small text-muted">
        <div class="fw-bold text-dark small mb-2 d-flex align-items-center gap-1">
          <i class="bi bi-key-fill text-primary"></i>
          <span>Credenciales de Prueba (Clic para auto-llenar):</span>
        </div>
        <div class="d-flex flex-column gap-1.5">
          <button type="button" class="btn btn-outline-secondary btn-sm text-start py-1 px-2 d-flex justify-content-between align-items-center" onclick="autocompletar('Admin', 'admin1234')">
            <span><b>Admin:</b> Admin</span>
            <span class="badge bg-secondary font-monospace">admin1234</span>
          </button>
          <button type="button" class="btn btn-outline-secondary btn-sm text-start py-1 px-2 d-flex justify-content-between align-items-center mt-1" onclick="autocompletar('Empleado', 'emp1234')">
            <span><b>Empleado:</b> Empleado</span>
            <span class="badge bg-secondary font-monospace">emp1234</span>
          </button>
        </div>
      </div>

      <!-- Enlace a Vista Pública QR -->
      <div class="text-center mt-4 pt-2 border-top">
        <a href="index.php" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1">
          <i class="bi bi-qr-code"></i>
          <span>Volver al Tótem QR Público</span>
        </a>
      </div>

    </div>

  </div>

  <script>
    function autocompletar(user, pwd) {
      document.getElementById('username').value = user;
      document.getElementById('password').value = pwd;
    }
  </script>
</body>
</html>
