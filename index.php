<?php
/**
 * SENATI ETI - Pantalla Principal / Tótem Público de Auto-registro QR
 * Raíz del proyecto: Interfaz ultra-minimalista con código QR interactivo y acceso personal discreto
 */

require_once __DIR__ . '/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

  <!-- SweetAlert2 (CSS/JS Oficial) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

  <!-- Estilos Corporativos SENATI -->
  <link rel="stylesheet" href="css/senati-theme.css">

  <style>
    body {
      background: radial-gradient(circle at 50% 20%, #ffffff 0%, #f1f5f9 100%);
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }
    .qr-container {
      max-width: 440px;
      transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    .qr-container:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 28px rgba(0, 56, 130, 0.08) !important;
    }
    .qr-interactive-box {
      cursor: pointer;
      transition: all 0.2s ease;
      background: #ffffff;
    }
    .qr-interactive-box:hover {
      border-color: #003882 !important;
      transform: scale(1.02);
    }
  </style>
</head>
<body class="d-flex flex-column min-vh-100 justify-content-between p-3 p-md-4">

  <!-- Cabecera Ultra-Minimalista: Logotipo Institucional -->
  <header class="text-center pt-2">
    <div class="d-inline-flex align-items-center gap-2 px-3 py-1.5 rounded-pill bg-white border shadow-sm">
      <span class="badge bg-primary px-2.5 py-1 text-uppercase fw-bold" style="background-color: #003882 !important; letter-spacing: 0.08em; font-size: 0.75rem;">
        SENATI
      </span>
      <span class="text-secondary small fw-medium">Dirección Zonal Lima Callao • ETI</span>
    </div>
  </header>

  <!-- Núcleo Central: Tótem de Escaneo QR -->
  <main class="container d-flex align-items-center justify-content-center my-auto py-3">
    <div class="card card-flat qr-container bg-white p-4 p-md-5 text-center border rounded-4 shadow-sm w-100">
      
      <!-- Icono de Servicio / Bienvenida -->
      <div class="d-inline-flex align-items-center justify-content-center text-primary rounded-circle bg-light border mx-auto mb-3" style="width: 52px; height: 52px; color: #003882 !important;">
        <i class="bi bi-qr-code-scan fs-4"></i>
      </div>

      <h1 class="h3 fw-bold text-dark mb-1" style="letter-spacing: -0.02em;">Senati ETI</h1>
      <p class="text-muted small mb-4">Sistema de Gestión de Visitas</p>

      <!-- Código QR Grande Interactivo envuelto en <a> hacia registrar_visita.php -->
      <a href="registrar_visita.php" class="text-decoration-none d-block mb-3" id="enlace-qr" title="Haz clic en el código QR para abrir el formulario de auto-registro">
        <div class="qr-interactive-box p-3 border rounded-3 mx-auto shadow-sm" style="max-width: 240px;">
          <svg viewBox="0 0 200 200" class="w-100 h-100" style="display: block;">
            <rect width="200" height="200" fill="#ffffff" />
            <!-- Patrones Guía QR en Esquinas con Azul SENATI -->
            <rect x="15" y="15" width="46" height="46" fill="#003882" rx="6" />
            <rect x="23" y="23" width="30" height="30" fill="#ffffff" rx="3" />
            <rect x="31" y="31" width="14" height="14" fill="#003882" rx="1" />

            <rect x="139" y="15" width="46" height="46" fill="#003882" rx="6" />
            <rect x="147" y="23" width="30" height="30" fill="#ffffff" rx="3" />
            <rect x="155" y="31" width="14" height="14" fill="#003882" rx="1" />

            <rect x="15" y="139" width="46" height="46" fill="#003882" rx="6" />
            <rect x="23" y="147" width="30" height="30" fill="#ffffff" rx="3" />
            <rect x="31" y="155" width="14" height="14" fill="#003882" rx="1" />

            <!-- Matriz de Datos QR -->
            <rect x="75" y="20" width="10" height="10" fill="#1e293b" />
            <rect x="95" y="20" width="10" height="20" fill="#1e293b" />
            <rect x="115" y="30" width="10" height="10" fill="#003882" />
            <rect x="75" y="45" width="20" height="10" fill="#1e293b" />
            <rect x="20" y="75" width="10" height="20" fill="#1e293b" />
            <rect x="40" y="75" width="20" height="10" fill="#003882" />

            <!-- Emblema Central 'S' de SENATI -->
            <rect x="75" y="75" width="50" height="50" fill="#003882" rx="8" />
            <circle cx="100" cy="100" r="16" fill="#ffffff" />
            <text x="100" y="105" font-size="14" font-weight="900" fill="#003882" text-anchor="middle" font-family="'Inter', Arial, sans-serif">S</text>

            <rect x="135" y="75" width="15" height="15" fill="#1e293b" />
            <rect x="160" y="75" width="20" height="10" fill="#1e293b" />
            <rect x="140" y="100" width="20" height="20" fill="#003882" />
            <rect x="170" y="100" width="15" height="20" fill="#1e293b" />
            <rect x="75" y="135" width="10" height="20" fill="#1e293b" />
            <rect x="95" y="145" width="20" height="10" fill="#1e293b" />
            <rect x="135" y="135" width="20" height="10" fill="#003882" />
            <rect x="165" y="135" width="15" height="15" fill="#1e293b" />
            <rect x="75" y="170" width="40" height="10" fill="#1e293b" />
            <rect x="130" y="160" width="20" height="20" fill="#1e293b" />
            <rect x="160" y="160" width="20" height="20" fill="#003882" />
          </svg>
        </div>
      </a>

      <!-- Botón de Acción Principal para el Visitante -->
      <a href="registrar_visita.php" class="btn btn-senati-primary btn-sm px-4 py-2.5 fw-semibold d-inline-flex align-items-center justify-content-center gap-2 mb-2 w-100 shadow-sm" style="border-radius: 8px;">
        <i class="bi bi-phone"></i>
        <span>Toca aquí para registrarte</span>
      </a>

      <p class="text-secondary small mb-0" style="font-size: 0.8125rem;">
        Escanea con la cámara de tu smartphone o haz clic en el código QR para solicitar tu turno presencial.
      </p>

    </div>
  </main>

  <!-- Parte Inferior: Botón Discreto / Enlace sutil para Acceso Personal -->
  <footer class="text-center pb-2">
    <div class="d-inline-flex align-items-center gap-1">
      <a href="login.php" class="btn btn-sm btn-link text-decoration-none text-muted d-inline-flex align-items-center gap-1.5 px-3 py-1.5 rounded-pill" style="font-size: 0.8125rem; transition: all 0.2s ease;">
        <i class="bi bi-lock-fill text-secondary"></i>
        <span>Acceso Personal</span>
      </a>
    </div>
  </footer>

  <!-- SweetAlert2 Oficial JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- Bootstrap 5 JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
