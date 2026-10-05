<?php
/**
 * SENATI ETI - Pantalla Pública Principal / Tótem de Auto-registro QR
 * Módulo de bienvenida para visitantes con enlace interactivo a registrar_visita.php
 */

require_once __DIR__ . '/conexion.php';
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Senati ETI - Sistema de Gestión de Visitas</title>

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
<body class="bg-light d-flex flex-column min-vh-100 justify-content-between">

  <!-- Navegación Superior Mínima (Sin senati-topbar) -->
  <header class="bg-white border-bottom py-2 px-4 d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-2">
      <span class="badge bg-primary px-2 py-1 text-uppercase fw-bold" style="letter-spacing: 0.05em;">SENATI</span>
      <span class="fw-semibold text-dark small">ETI - Sistema de Gestión de Visitas</span>
    </div>
    <div>
      <a href="login.php" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1.5 py-1 px-3">
        <i class="bi bi-person-lock"></i>
        <span>Acceso Personal</span>
      </a>
    </div>
  </header>

  <!-- Contenedor Central del Tótem QR -->
  <main class="container py-4 d-flex align-items-center justify-content-center flex-grow-1">
    <div class="qr-card shadow-sm bg-white p-4 p-md-5 text-center" style="max-width: 440px;">
      
      <!-- Emblema de Servicio -->
      <div class="d-inline-flex align-items-center justify-content-center bg-light text-primary rounded-3 p-3 mb-3 border">
        <i class="bi bi-plus-lg fs-3 text-primary"></i>
      </div>

      <h1 class="h3 fw-bold text-dark mb-1">Senati ETI</h1>
      <p class="text-muted small mb-4">Sistema de Gestión de Visitas</p>

      <!-- CÓDIGO QR INTERACTIVO (WRAPPER CON ETIQUETA <a> HACIA registrar_visita.php) -->
      <a href="registrar_visita.php" class="text-decoration-none d-block mb-3" title="Haz clic en el código QR para abrir el formulario de registro de visita">
        <div class="qr-box p-3 border rounded-3 bg-white mx-auto shadow-sm" style="transition: transform 0.2s ease, border-color 0.2s ease;">
          <svg viewBox="0 0 200 200" class="w-100 h-100">
            <rect width="200" height="200" fill="#ffffff" />
            <!-- Patrones Guía QR -->
            <rect x="15" y="15" width="46" height="46" fill="#003882" rx="4" />
            <rect x="23" y="23" width="30" height="30" fill="#ffffff" rx="2" />
            <rect x="31" y="31" width="14" height="14" fill="#003882" />
            <rect x="139" y="15" width="46" height="46" fill="#003882" rx="4" />
            <rect x="147" y="23" width="30" height="30" fill="#ffffff" rx="2" />
            <rect x="155" y="31" width="14" height="14" fill="#003882" />
            <rect x="15" y="139" width="46" height="46" fill="#003882" rx="4" />
            <rect x="23" y="147" width="30" height="30" fill="#ffffff" rx="2" />
            <rect x="31" y="155" width="14" height="14" fill="#003882" />
            <!-- Módulos de Datos -->
            <rect x="75" y="20" width="10" height="10" fill="#1f2937" />
            <rect x="95" y="20" width="10" height="20" fill="#1f2937" />
            <rect x="115" y="30" width="10" height="10" fill="#003882" />
            <rect x="75" y="45" width="20" height="10" fill="#1f2937" />
            <rect x="20" y="75" width="10" height="20" fill="#1f2937" />
            <rect x="40" y="75" width="20" height="10" fill="#003882" />
            <rect x="75" y="75" width="50" height="50" fill="#003882" rx="6" />
            <circle cx="100" cy="100" r="16" fill="#ffffff" />
            <text x="100" y="105" font-size="13" font-weight="900" fill="#003882" text-anchor="middle" font-family="Arial">S</text>
            <rect x="135" y="75" width="15" height="15" fill="#1f2937" />
            <rect x="160" y="75" width="20" height="10" fill="#1f2937" />
            <rect x="140" y="100" width="20" height="20" fill="#003882" />
            <rect x="170" y="100" width="15" height="20" fill="#1f2937" />
            <rect x="75" y="135" width="10" height="20" fill="#1f2937" />
            <rect x="95" y="145" width="20" height="10" fill="#1f2937" />
            <rect x="135" y="135" width="20" height="10" fill="#003882" />
            <rect x="165" y="135" width="15" height="15" fill="#1f2937" />
            <rect x="75" y="170" width="40" height="10" fill="#1f2937" />
            <rect x="130" y="160" width="20" height="20" fill="#1f2937" />
            <rect x="160" y="160" width="20" height="20" fill="#003882" />
          </svg>
        </div>
      </a>

      <!-- Llamado a la Acción -->
      <a href="registrar_visita.php" class="btn btn-senati-primary btn-sm px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2 mb-3">
        <i class="bi bi-phone"></i>
        <span>Toca aquí o escanea para registrarte</span>
      </a>

      <p class="text-secondary small mb-3">
        Haz clic en el código QR para simular el escaneo con tu teléfono y obtener tu ticket.
      </p>

      <hr class="text-muted opacity-25 my-3">

      <!-- Enlace para Empleados / Administrador -->
      <div class="text-center">
        <a href="login.php" class="text-decoration-none text-muted small fw-medium d-inline-flex align-items-center gap-1">
          <i class="bi bi-shield-lock text-primary"></i>
          <span>Portal de Empleados y Administrador</span>
        </a>
      </div>

    </div>
  </main>

  <!-- Pie de Página Institucional -->
  <footer class="bg-white border-top py-2 px-4 text-center text-muted small">
    SENATI ETI • Dirección Zonal Lima Callao • Sistema de Gestión de Visitas
  </footer>

  <!-- Bootstrap 5 Bundle JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
