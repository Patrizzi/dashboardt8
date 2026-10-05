/**
 * SENATI ETI - Capa de Interactividad, Fetch API y SweetAlert2 (senati-ux.js)
 * Estandarización de UX, notificaciones asíncronas y reactividad sin recarga de página.
 */

// Paleta institucional SENATI para SweetAlert2
const SENATI_THEME = {
  primary: '#003882',
  primaryHover: '#002657',
  danger: '#dc3545',
  success: '#10b981',
  warning: '#f59e0b',
  textDark: '#1e293b'
};

// Objeto Global de Utilidades UX de SENATI
window.SenatiUX = {

  /**
   * Alerta de Éxito
   */
  alertSuccess: function(title, text, timer = 2500) {
    return Swal.fire({
      icon: 'success',
      title: title || '¡Operación Exitosa!',
      text: text || '',
      timer: timer,
      timerProgressBar: true,
      confirmButtonColor: SENATI_THEME.primary,
      confirmButtonText: 'Aceptar'
    });
  },

  /**
   * Alerta de Error
   */
  alertError: function(title, text) {
    return Swal.fire({
      icon: 'error',
      title: title || 'Error en la Operación',
      text: text || 'Ocurrió un problema inesperado. Por favor, reintenta.',
      confirmButtonColor: SENATI_THEME.primary,
      confirmButtonText: 'Entendido'
    });
  },

  /**
   * Diálogo de Confirmación (Reemplazo estricto de confirm())
   */
  confirmAction: function(options = {}) {
    const {
      title = '¿Estás seguro?',
      text = 'Esta acción no se puede deshacer.',
      confirmText = 'Sí, continuar',
      cancelText = 'Cancelar',
      confirmColor = SENATI_THEME.danger,
      icon = 'warning'
    } = options;

    return Swal.fire({
      title: title,
      text: text,
      icon: icon,
      showCancelButton: true,
      confirmButtonColor: confirmColor,
      cancelButtonColor: '#64748b',
      confirmButtonText: confirmText,
      cancelButtonText: cancelText,
      reverseButtons: true
    }).then(result => result.isConfirmed);
  },

  /**
   * Modal Especial de Ticket Digital para el Visitante
   */
  showTicketVoucher: function(ticket) {
    const voucherHtml = `
      <div class="text-center p-2">
        <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-3 px-3 py-1 mb-2 fw-bold text-uppercase small" style="letter-spacing:0.08em; background-color: #003882 !important;">
          SENATI ETI
        </div>
        <h4 class="fw-bold text-dark mb-0">TICKET DE ATENCIÓN</h4>
        <div class="my-3 py-2 px-3 bg-light rounded-3 border">
          <div class="text-muted small text-uppercase fw-semibold">Tu Número en Cola</div>
          <div class="display-5 fw-bold text-primary" style="color: #003882 !important; letter-spacing: -0.02em;">
            ${ticket.codigo}
          </div>
        </div>
        <div class="text-start bg-white p-3 rounded-2 border small mb-3">
          <div class="d-flex justify-content-between py-1 border-bottom">
            <span class="text-muted">Visitante:</span>
            <span class="fw-semibold text-dark">${ticket.visitante}</span>
          </div>
          ${ticket.dni ? `
          <div class="d-flex justify-content-between py-1 border-bottom">
            <span class="text-muted">DNI / Documento:</span>
            <span class="fw-semibold text-dark">${ticket.dni}</span>
          </div>` : ''}
          <div class="d-flex justify-content-between py-1 border-bottom">
            <span class="text-muted">Trámite:</span>
            <span class="badge bg-secondary">${ticket.asunto}</span>
          </div>
          <div class="d-flex justify-content-between py-1 border-bottom">
            <span class="text-muted">Tiempo Estimado:</span>
            <span class="fw-semibold text-primary" style="color: #003882 !important;">~${ticket.sla} minutos</span>
          </div>
          <div class="d-flex justify-content-between py-1">
            <span class="text-muted">Fecha y Hora:</span>
            <span class="text-secondary">${ticket.fecha} - ${ticket.hora}</span>
          </div>
        </div>
        <p class="text-muted small mb-0">
          <i class="bi bi-info-circle text-primary me-1"></i>
          Por favor, toma asiento en sala de espera. Un asesor te llamará en breve.
        </p>
      </div>
    `;

    return Swal.fire({
      html: voucherHtml,
      showCloseButton: true,
      showCancelButton: true,
      confirmButtonColor: SENATI_THEME.primary,
      cancelButtonColor: '#64748b',
      confirmButtonText: '<i class="bi bi-plus-circle me-1"></i> Registrar Otro Trámite',
      cancelButtonText: '<i class="bi bi-house me-1"></i> Volver al Tótem QR'
    });
  },

  /**
   * Actualiza visualmente las tarjetas KPI en el DOM
   */
  updateKpiBadges: function(kpis) {
    if (!kpis) return;

    const elActivos = document.getElementById('kpi-activos');
    const elEnEspera = document.getElementById('kpi-en-espera');
    const elEnProceso = document.getElementById('kpi-en-proceso');
    const elCompletadas = document.getElementById('kpi-completadas-hoy');
    const elBadgeCola = document.getElementById('badge-cola-disponible');

    if (elActivos && kpis.activos !== undefined) elActivos.textContent = kpis.activos;
    if (elEnEspera && kpis.enEspera !== undefined) elEnEspera.textContent = kpis.enEspera;
    if (elEnProceso && kpis.enProceso !== undefined) elEnProceso.textContent = kpis.enProceso;
    if (elCompletadas && kpis.completadasHoy !== undefined) elCompletadas.textContent = kpis.completadasHoy;
    if (elBadgeCola && kpis.colaDisponible !== undefined) elBadgeCola.textContent = kpis.colaDisponible;
  }
};

// =========================================================================
// 1. INICIALIZADOR: FORMULARIO DE LOGIN (login.php)
// =========================================================================
document.addEventListener('DOMContentLoaded', () => {
  const formLogin = document.getElementById('formLogin');
  if (formLogin) {
    formLogin.addEventListener('submit', async (e) => {
      e.preventDefault();

      const btnSubmit = formLogin.querySelector('button[type="submit"]');
      const originalBtnHtml = btnSubmit.innerHTML;

      const usernameInput = document.getElementById('username');
      const passwordInput = document.getElementById('password');

      const username = usernameInput ? usernameInput.value.trim() : '';
      const password = passwordInput ? passwordInput.value.trim() : '';

      if (!username || !password) {
        SenatiUX.alertError('Campos Incompletos', 'Por favor ingresa tu usuario y contraseña.');
        return;
      }

      // Estado de carga en el botón
      btnSubmit.disabled = true;
      btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Validando credenciales...';

      try {
        const formData = new FormData(formLogin);
        const response = await fetch('ajax_login.php', {
          method: 'POST',
          body: formData
        });

        const data = await response.json();

        if (response.ok && data.status === 'success') {
          await Swal.fire({
            icon: 'success',
            title: '¡Acceso Concedido!',
            text: data.message || 'Iniciando sesión...',
            timer: 1400,
            timerProgressBar: true,
            showConfirmButton: false
          });
          window.location.href = data.redirect || 'dashboard.php';
        } else {
          SenatiUX.alertError('Acceso Denegado', data.message || 'Usuario o contraseña incorrectos.');
          btnSubmit.disabled = false;
          btnSubmit.innerHTML = originalBtnHtml;
        }
      } catch (err) {
        console.error('[Error Fetch Login]:', err);
        SenatiUX.alertError('Error de Conexión', 'No se pudo comunicar con el servidor.');
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = originalBtnHtml;
      }
    });
  }

  // =========================================================================
  // 2. INICIALIZADOR: REGISTRO DE VISITAS (registrar_visita.php)
  // =========================================================================
  const formRegistro = document.getElementById('formRegistroVisita');
  if (formRegistro) {
    formRegistro.addEventListener('submit', async (e) => {
      e.preventDefault();

      const btnSubmit = formRegistro.querySelector('button[type="submit"]');
      const originalBtnHtml = btnSubmit.innerHTML;

      const visitante = formRegistro.querySelector('[name="visitante"]').value.trim();
      const consulta = formRegistro.querySelector('[name="consulta"]').value.trim();

      if (!visitante || !consulta) {
        SenatiUX.alertError('Campos Requeridos', 'El nombre del visitante y el motivo de consulta son obligatorios.');
        return;
      }

      // Estado de carga
      btnSubmit.disabled = true;
      btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Generando Ticket...';

      try {
        const formData = new FormData(formRegistro);
        const response = await fetch('ajax_registrar.php', {
          method: 'POST',
          body: formData
        });

        const data = await response.json();

        if (response.ok && data.status === 'success') {
          // Limpiar formulario sin recargar la página
          formRegistro.reset();
          btnSubmit.disabled = false;
          btnSubmit.innerHTML = originalBtnHtml;

          // Mostrar Voucher Digital interactivo
          const result = await SenatiUX.showTicketVoucher(data.data);

          if (!result.isConfirmed) {
            // Usuario eligió "Volver al Tótem QR"
            window.location.href = 'index.php';
          }
        } else {
          SenatiUX.alertError('Error al Registrar', data.message || 'No se pudo emitir el ticket.');
          btnSubmit.disabled = false;
          btnSubmit.innerHTML = originalBtnHtml;
        }
      } catch (err) {
        console.error('[Error Fetch Registro]:', err);
        SenatiUX.alertError('Error de Comunicación', 'No se pudo procesar la solicitud.');
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = originalBtnHtml;
      }
    });
  }
});

// =========================================================================
// 3. FUNCIONES DE INTERACCIÓN EN PANEL EMPLEADO (empleado.php)
// =========================================================================

/**
 * Tomar un ticket de la cola compartida sin recargar la página
 */
async function tomarTicketAjax(ticketId, codigo) {
  try {
    const response = await fetch('ajax_actualizar_estado.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        accion: 'tomar',
        ticket_id: ticketId
      })
    });

    const data = await response.json();

    if (response.ok && data.status === 'success') {
      await SenatiUX.alertSuccess('¡Ticket Asignado!', data.message, 1800);

      // Eliminar fila de la tabla de disponibles con animación suave
      const fila = document.getElementById(`fila-ticket-${ticketId}`);
      if (fila) {
        fila.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        fila.style.opacity = '0';
        fila.style.transform = 'translateX(-20px)';
        setTimeout(() => fila.remove(), 400);
      }

      // Actualizar contadores KPI reactivamente
      SenatiUX.updateKpiBadges(data.kpis);

      // Si no quedan tickets en la tabla, mostrar fila vacía
      const tbody = document.getElementById('tbody-disponibles');
      if (tbody && tbody.querySelectorAll('tr').length <= 1) {
        setTimeout(() => {
          tbody.innerHTML = `
            <tr>
              <td colspan="7" class="text-center py-4 text-muted small">
                <i class="bi bi-inbox fs-3 d-block text-secondary opacity-50 mb-2"></i>
                No hay más visitas en espera disponibles en este momento.
              </td>
            </tr>`;
        }, 450);
      }
    } else {
      SenatiUX.alertError('No se pudo tomar el ticket', data.message);
    }
  } catch (err) {
    console.error('[Error Tomar Ticket]:', err);
    SenatiUX.alertError('Error de Servidor', 'No se pudo completar la asignación del ticket.');
  }
}

/**
 * Completar Atención de una Visita solicitando la respuesta de ventanilla
 */
async function completarAtencionAjax(ticketId, codigo, visitante) {
  const { value: respuesta, isConfirmed } = await Swal.fire({
    title: `Completar Atención: ${codigo}`,
    html: `
      <div class="text-start small mb-2 text-muted">
        Visitante: <b class="text-dark">${visitante}</b>
      </div>
      <label class="form-label small fw-semibold text-start w-100">Dictamen o Solución Brindada:</label>
      <textarea id="swal-respuesta" class="form-control form-control-sm" rows="3" placeholder="Describe la solución o respuesta entregada al visitante..."></textarea>
    `,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: SENATI_THEME.primary,
    cancelButtonColor: '#64748b',
    confirmButtonText: '<i class="bi bi-check-circle me-1"></i> Guardar y Finalizar',
    cancelButtonText: 'Cancelar',
    preConfirm: () => {
      const resp = document.getElementById('swal-respuesta').value.trim();
      if (!resp) {
        Swal.showValidationMessage('Por favor escribe la respuesta o solución brindada.');
      }
      return resp;
    }
  });

  if (!isConfirmed || !respuesta) return;

  try {
    const response = await fetch('ajax_actualizar_estado.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        accion: 'completar',
        ticket_id: ticketId,
        respuesta: respuesta
      })
    });

    const data = await response.json();

    if (response.ok && data.status === 'success') {
      await SenatiUX.alertSuccess('¡Atención Completada!', data.message, 2000);

      // Actualizar la fila en el DOM
      const fila = document.getElementById(`fila-ticket-${ticketId}`);
      if (fila) {
        // Actualizar badge de estado a Completada
        const badgeEstado = fila.querySelector('.badge-estado');
        if (badgeEstado) {
          badgeEstado.className = 'badge bg-success-subtle text-success border border-success-subtle px-2 py-1 badge-estado';
          badgeEstado.textContent = 'Completada';
        }

        // Deshabilitar o cambiar los botones de acción
        const celdaAcciones = fila.querySelector('.celda-acciones');
        if (celdaAcciones) {
          celdaAcciones.innerHTML = `
            <span class="badge bg-light text-secondary border small">
              <i class="bi bi-check2-all text-success me-1"></i> Finalizada
            </span>
          `;
        }
      }

      // Actualizar contadores KPI
      SenatiUX.updateKpiBadges(data.kpis);
    } else {
      SenatiUX.alertError('Error al Completar', data.message);
    }
  } catch (err) {
    console.error('[Error Completar Ticket]:', err);
    SenatiUX.alertError('Error de Comunicación', 'No se pudo finalizar la atención.');
  }
}

/**
 * Confirmar Cancelación o Abandono de Ticket (Caso Obligatorio Warning/Confirm)
 */
async function cancelarTicketAjax(ticketId, codigo) {
  const confirmado = await SenatiUX.confirmAction({
    title: `¿Marcar Ticket como Abandonado?`,
    text: `Estás a punto de cancelar o marcar como abandonado el ticket ${codigo}. Esta acción registrará la anulación en el historial.`,
    confirmText: 'Sí, confirmar abandono',
    cancelText: 'Cancelar',
    confirmColor: SENATI_THEME.danger,
    icon: 'warning'
  });

  if (!confirmado) return;

  try {
    const response = await fetch('ajax_actualizar_estado.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        accion: 'abandonar',
        ticket_id: ticketId,
        respuesta: 'El visitante no se presentó a ventanilla tras los llamados reglamentarios.'
      })
    });

    const data = await response.json();

    if (response.ok && data.status === 'success') {
      await SenatiUX.alertSuccess('Ticket Anulado', data.message, 2000);

      // Desvanecer fila en el DOM
      const fila = document.getElementById(`fila-ticket-${ticketId}`);
      if (fila) {
        fila.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        fila.style.opacity = '0';
        fila.style.transform = 'scale(0.95)';
        setTimeout(() => fila.remove(), 400);
      }

      // Actualizar contadores KPI
      SenatiUX.updateKpiBadges(data.kpis);
    } else {
      SenatiUX.alertError('Error al Cancelar', data.message);
    }
  } catch (err) {
    console.error('[Error Cancelar Ticket]:', err);
    SenatiUX.alertError('Error', 'No se pudo cancelar el ticket.');
  }
}
