<?php
/**
 * SENATI ETI - Middleware de Control de Acceso Basado en Roles (RBAC)
 * Protege vistas validando sesión activa y privilegios de rol.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Valida si el usuario está autenticado y posee uno de los roles permitidos.
 * 
 * @param array $rolesPermitidos Lista de roles con permiso (ej. ['administrador', 'empleado'])
 * @return array Datos del usuario autenticado
 */
function verificarSesion(array $rolesPermitidos = []): array {
    // 1. Validar si existe sesión activa
    if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['id_usuario'])) {
        header('Location: login.php?error=no_auth');
        exit;
    }

    $usuario = $_SESSION['usuario'];

    // 2. Validar privilegios de rol si se especifican restricciones
    if (!empty($rolesPermitidos) && !in_array($usuario['rol'], $rolesPermitidos)) {
        // Redirección inteligente al panel correspondiente según su rol legítimo
        if ($usuario['rol'] === 'administrador') {
            header('Location: dashboard.php?error=acceso_denegado');
        } elseif ($usuario['rol'] === 'empleado') {
            header('Location: empleado.php?error=acceso_denegado');
        } else {
            header('Location: login.php?error=acceso_denegado');
        }
        exit;
    }

    return $usuario;
}
?>
