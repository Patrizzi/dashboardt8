<?php
/**
 * SENATI ETI - Formulario de Inicio de Sesión
 * Diseño Minimalista en Bootstrap 5 con Autenticación Fetch API + SweetAlert2
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirección si ya cuenta con sesión activa
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acceso Personal - Senati ETI</title>

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
      background: radial-gradient(circle at 50% 25%, #ffffff 0%, #f1f5f9 100%);
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }
    .login-card {
      max-width: 430px;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0, 56, 130, 0.06);
    }
  </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 py-4 px-3">

  <div class="container d-flex justify-content-center">
    
    <!-- Tarjeta Principal de Login -->
    <div class="card login-card p-4 p-md-5 bg-white border w-100">
      
      <!-- Encabezado Institucional -->
      <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center text-white rounded-3 px-3 py-1 mb-2 fw-bold text-uppercase small" style="background-color: #003882; letter-spacing: 0.08em; font-size: 0.75rem;">
          SENATI ETI
        </div>
        <h1 class="h4 fw-bold text-dark mb-1" style="letter-spacing: -0.02em;">Acceso Personal</h1>
        <p class="text-muted small mb-0">Gestión de Visitas y Trazabilidad</p>
      </div>

      <!-- Formulario de Autenticación Interceptado por Fetch API -->
      <form id="formLogin" novalidate>
        <div class="mb-3">
          <label for="username" class="form-label small fw-semibold text-secondary">Usuario o Código</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
            <input type="text" name="username" id="username" required autofocus placeholder="Admin o Empleado" class="form-control form-control-sm border-start-0">
          </div>
        </div>

        <div class="mb-4">
          <label for="password" class="form-label small fw-semibold text-secondary">Contraseña</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
            <input type="password" name="password" id="password" required placeholder="••••••••" class="form-control form-control-sm border-start-0">
          </div>
        </div>

        <button type="submit" class="btn btn-senati-primary w-100 py-2.5 fw-semibold d-flex align-items-center justify-content-center gap-2 mb-3 shadow-sm" style="border-radius: 8px;">
          <i class="bi bi-box-arrow-in-right"></i>
          <span>Ingresar al Sistema</span>
        </button>
      </form>

      <!-- Panel de Credenciales de Prueba (Clic para auto-completar) -->
      <div class="bg-light p-3 rounded-2 border mt-2 small text-muted">
        <div class="fw-bold text-dark small mb-2 d-flex align-items-center gap-1.5">
          <i class="bi bi-key-fill text-primary" style="color: #003882 !important;"></i>
          <span>Credenciales de Evaluación (Auto-llenar):</span>
        </div>
        <div class="d-flex flex-column gap-1.5">
          <button type="button" class="btn btn-outline-secondary btn-sm text-start py-1.5 px-2.5 d-flex justify-content-between align-items-center bg-white" onclick="autocompletar('Admin', 'admin1234')">
            <span class="small"><b>Admin:</b> Admin</span>
            <span class="badge bg-secondary font-monospace" style="font-size: 0.7rem;">admin1234</span>
          </button>
          <button type="button" class="btn btn-outline-secondary btn-sm text-start py-1.5 px-2.5 d-flex justify-content-between align-items-center bg-white mt-1" onclick="autocompletar('Empleado', 'emp1234')">
            <span class="small"><b>Empleado:</b> Empleado</span>
            <span class="badge bg-secondary font-monospace" style="font-size: 0.7rem;">emp1234</span>
          </button>
        </div>
      </div>

      <!-- Retorno al Tótem QR -->
      <div class="text-center mt-4 pt-2 border-top">
        <a href="index.php" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1">
          <i class="bi bi-qr-code"></i>
          <span>Volver al Tótem QR Público</span>
        </a>
      </div>

    </div>

  </div>

  <!-- SweetAlert2 Oficial JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- Bootstrap 5 Bundle JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Script SENATI UX (Fetch + SweetAlert2) -->
  <script src="js/senati-ux.js"></script>

  <script>
    function autocompletar(user, pwd) {
      document.getElementById('username').value = user;
      document.getElementById('password').value = pwd;
    }

    // Notificaciones SweetAlert2 para parámetros de URL (Ej. Logout o redirección no auth)
    document.addEventListener('DOMContentLoaded', () => {
      const urlParams = new URLSearchParams(window.location.search);
      const err = urlParams.get('error');
      const msg = urlParams.get('msg');

      if (err === 'no_auth') {
        SenatiUX.alertError('Acceso Restringido', 'Debes iniciar sesión para ingresar a los paneles protegidos.');
      } else if (err === 'acceso_denegado') {
        SenatiUX.alertError('Permiso Denegado', 'Tu rol no tiene autorización para acceder a esa sección.');
      }

      if (msg === 'sesion_cerrada') {
        SenatiUX.alertSuccess('Sesión Cerrada', 'Has cerrado sesión correctamente. ¡Hasta pronto!', 2200);
      }
    });
  </script>
</body>
</html>
