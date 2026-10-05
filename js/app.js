/**
 * SENATI ETI - Sistema de Gestión de Visitas
 * Lógica modular de Negocio, Reactividad y Gestión de Estado (JavaScript Nativo ES6+)
 * Libre de dependencias heredadas (Sin jQuery, compatible con Bootstrap 5 Native)
 */

// 1. ESTADO GLOBAL DE LA APLICACIÓN
const APP_STATE = {
  currentUser: {
    id: "EMP001",
    name: "Carlos Rodríguez López",
    cargo: "Especialista de Atención ETI"
  },
  activeView: 'empleado', // 'qr', 'empleado', 'admin'
  employeeTab: 'asignadas', // 'asignadas', 'disponibles'
  autoSimInterval: null,
  charts: {
    horasPico: null,
    asuntos: null,
    colaboradores: null
  }
};

// 2. COLECCIÓN DE VISITAS (MODELO DE DATOS CON TIMESTAMPS)
let visits = [
  {
    id: "VIS-1001",
    codigo: "VIS-1001",
    visitante: "Carlos Mendoza Silva",
    dni: "72849102",
    asunto: "Matrícula",
    consulta: "¿Cuáles son las fechas límite de convalidación para Mecatrónica Industrial?",
    respuesta: "Se le brindó el cronograma de matrícula extemporánea y requisitos de convalidación académica.",
    prioridad: "Alta",
    estado: "Completada",
    asignadoA: "Carlos Rodríguez López",
    fecha: "2026-10-05",
    horaRegistro: "08:15",
    tRegistro: Date.now() - 3600000 * 2,
    tInicio: Date.now() - 3600000 * 2 + 600000, // 10 min espera
    tCierre: Date.now() - 3600000 * 2 + 1080000 // 8 min resolución
  },
  {
    id: "VIS-1002",
    codigo: "VIS-1002",
    visitante: "Diana Sánchez Paredes",
    dni: "71029384",
    asunto: "Pagos",
    consulta: "Error en el portal bancario para cancelar la cuota 2 del semestre.",
    respuesta: "Se generó nuevo código CIP y se confirmó recepción en el sistema financiero.",
    prioridad: "Media",
    estado: "Completada",
    asignadoA: "Carlos Rodríguez López",
    fecha: "2026-10-05",
    horaRegistro: "09:00",
    tRegistro: Date.now() - 3600000 * 1.5,
    tInicio: Date.now() - 3600000 * 1.5 + 480000, // 8 min espera
    tCierre: Date.now() - 3600000 * 1.5 + 900000 // 7 min resolucion
  },
  {
    id: "VIS-1003",
    codigo: "VIS-1003",
    visitante: "Roberto Ponce Valdivia",
    dni: "45892019",
    asunto: "Tutoría",
    consulta: "Solicitud de cita presencial con el tutor pedagógico por temas de asistencia.",
    respuesta: "",
    prioridad: "Media",
    estado: "En Proceso",
    asignadoA: "Carlos Rodríguez López",
    fecha: "2026-10-05",
    horaRegistro: "09:45",
    tRegistro: Date.now() - 1800000,
    tInicio: Date.now() - 900000, // 15 min espera
    tCierre: null
  },
  {
    id: "VIS-1004",
    codigo: "VIS-1004",
    visitante: "Valeria Castillo Rivas",
    dni: "76543210",
    asunto: "Consultas de Notas",
    consulta: "Revisión de calificación en examen final del curso Redes CISCO.",
    respuesta: "Se derivó la solicitud al jefe de área y se entregó formato oficial de reclamo.",
    prioridad: "Alta",
    estado: "Completada",
    asignadoA: "Carlos Rodríguez López",
    fecha: "2026-10-05",
    horaRegistro: "10:10",
    tRegistro: Date.now() - 3600000 * 3,
    tInicio: Date.now() - 3600000 * 3 + 720000,
    tCierre: Date.now() - 3600000 * 3 + 1200000
  },
  {
    id: "VIS-1005",
    codigo: "VIS-1005",
    visitante: "Jorge Linares Cárdenas",
    dni: "73201948",
    asunto: "Matrícula",
    consulta: "Cambio de sede presencial de Independencia a San Martín de Porres.",
    respuesta: "",
    prioridad: "Baja",
    estado: "En Proceso",
    asignadoA: "Carlos Rodríguez López",
    fecha: "2026-10-05",
    horaRegistro: "10:30",
    tRegistro: Date.now() - 1200000,
    tInicio: Date.now() - 600000,
    tCierre: null
  },
  {
    id: "VIS-1006",
    codigo: "VIS-1006",
    visitante: "Elena Bravo Montero",
    dni: "78492011",
    asunto: "Otros",
    consulta: "Emisión de constancia de egresado y trámite para título técnico.",
    respuesta: "Verificación de créditos concluida y entrega de orden de pago de trámite.",
    prioridad: "Media",
    estado: "Completada",
    asignadoA: "Carlos Rodríguez López",
    fecha: "2026-10-05",
    horaRegistro: "11:00",
    tRegistro: Date.now() - 3600000 * 4,
    tInicio: Date.now() - 3600000 * 4 + 600000,
    tCierre: Date.now() - 3600000 * 4 + 1140000
  },
  // Visitas Disponibles (Cola compartida)
  {
    id: "VIS-1007",
    codigo: "VIS-1007",
    visitante: "Martín Quispe Gómez",
    dni: "70192834",
    asunto: "Matrícula",
    consulta: "Deseo inscribirme en el curso de Especialización en Inteligencia Artificial.",
    respuesta: "",
    prioridad: "Alta",
    estado: "En Espera",
    asignadoA: null,
    fecha: "2026-10-05",
    horaRegistro: "11:15",
    tRegistro: Date.now() - 1440000, // 24 min esperando -> ¡ALERTA AUDITORÍA!
    tInicio: null,
    tCierre: null
  },
  {
    id: "VIS-1008",
    codigo: "VIS-1008",
    visitante: "Lucía Fernández Torres",
    dni: "74019283",
    asunto: "Pagos",
    consulta: "Consulta sobre descuento por convenio corporativo con empresa aliada.",
    respuesta: "",
    prioridad: "Urgente",
    estado: "En Espera",
    asignadoA: null,
    fecha: "2026-10-05",
    horaRegistro: "11:20",
    tRegistro: Date.now() - 1200000, // 20 min esperando -> ¡ALERTA AUDITORÍA!
    tInicio: null,
    tCierre: null
  },
  {
    id: "VIS-1009",
    codigo: "VIS-1009",
    visitante: "Andrés Ramos Vega",
    dni: "75839201",
    asunto: "Tutoría",
    consulta: "Información sobre programa de nivelación en matemáticas aplicadas.",
    respuesta: "",
    prioridad: "Media",
    estado: "En Espera",
    asignadoA: null,
    fecha: "2026-10-05",
    horaRegistro: "11:32",
    tRegistro: Date.now() - 480000, // 8 min esperando
    tInicio: null,
    tCierre: null
  }
];

// Rendimiento global de colaboradores
const COLABORADORES_DATA = [
  { id: "EMP001", nombre: "Carlos Rodríguez", completadas: 18, enProceso: 2 },
  { id: "EMP002", nombre: "María Quispe", completadas: 24, enProceso: 3 },
  { id: "EMP003", nombre: "Jorge Mendoza", completadas: 15, enProceso: 4 },
  { id: "EMP004", nombre: "Ana Ramos", completadas: 21, enProceso: 1 },
  { id: "EMP005", nombre: "Roberto Dávila", completadas: 12, enProceso: 2 }
];


// 3. INICIALIZACIÓN DEL SISTEMA
document.addEventListener('DOMContentLoaded', () => {
  initLiveClock();
  renderEmployeeTable();
  updateEmployeeKPIs();
  initAdminCharts();
  updateAdminDashboard();
  setupFilterEvents();
});

function initLiveClock() {
  const clockEl = document.getElementById('liveClockDisplay');
  if (!clockEl) return;
  const update = () => {
    const now = new Date();
    const options = { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
    clockEl.textContent = now.toLocaleDateString('es-PE', options);
  };
  update();
  setInterval(update, 1000);
}


// 4. CONTROL DE NAVEGACIÓN Y VISTAS
function switchView(viewName) {
  APP_STATE.activeView = viewName;
  
  const viewQr = document.getElementById('view-qr');
  const viewEmpleado = document.getElementById('view-empleado');
  const viewAdmin = document.getElementById('view-admin');

  viewQr.classList.add('d-none');
  viewEmpleado.classList.add('d-none');
  viewAdmin.classList.add('d-none');

  // Actualizar botones de navegación superior
  const btnQr = document.getElementById('nav-btn-qr');
  const btnEmp = document.getElementById('nav-btn-empleado');
  const btnAdm = document.getElementById('nav-btn-admin');

  [btnQr, btnEmp, btnAdm].forEach(b => {
    if (b) {
      b.classList.remove('btn-primary', 'btn-light', 'active');
      b.classList.add('btn-outline-light');
    }
  });

  if (viewName === 'qr') {
    viewQr.classList.remove('d-none');
    btnQr.classList.add('btn-light', 'text-dark', 'active');
    btnQr.classList.remove('btn-outline-light');
  } else if (viewName === 'empleado') {
    viewEmpleado.classList.remove('d-none');
    btnEmp.classList.add('btn-light', 'text-dark', 'active');
    btnEmp.classList.remove('btn-outline-light');
    renderEmployeeTable();
    updateEmployeeKPIs();
  } else if (viewName === 'admin') {
    viewAdmin.classList.remove('d-none');
    btnAdm.classList.add('btn-light', 'text-dark', 'active');
    btnAdm.classList.remove('btn-outline-light');
    updateAdminDashboard();
  }
}

function setEmployeeTab(tab) {
  APP_STATE.employeeTab = tab;
  const btnAsignadas = document.getElementById('tab-btn-asignadas');
  const btnDisponibles = document.getElementById('tab-btn-disponibles');
  const title = document.getElementById('tableSectionTitle');
  const subtitle = document.getElementById('tableSectionSubtitle');

  if (tab === 'asignadas') {
    btnAsignadas.classList.add('active');
    btnDisponibles.classList.remove('active');
    title.innerHTML = '<i class="bi bi-person-check text-primary me-2"></i>Mis Visitas Asignadas';
    subtitle.textContent = "Responde las consultas y actualiza el estado de las visitas.";
  } else {
    btnDisponibles.classList.add('active');
    btnAsignadas.classList.remove('active');
    title.innerHTML = '<i class="bi bi-inbox text-primary me-2"></i>Visitas Disponibles (Cola Compartida)';
    subtitle.textContent = "Toma tickets de la cola compartida para mantener una distribución equitativa de carga.";
  }

  renderEmployeeTable();
}


// 5. RENDERIZADO DEL PANEL EMPLEADO Y KPIS
function updateEmployeeKPIs() {
  const myVisits = visits.filter(v => v.asignadoA === APP_STATE.currentUser.name);

  const total = myVisits.length;
  const completadas = myVisits.filter(v => v.estado === 'Completada').length;
  const enProceso = myVisits.filter(v => v.estado === 'En Proceso').length;
  const matricula = myVisits.filter(v => v.asunto === 'Matrícula').length;
  const otros = myVisits.filter(v => v.asunto !== 'Matrícula').length;
  const efectividad = total > 0 ? Math.round((completadas / total) * 100) : 0;

  // Actualizar valores en UI
  document.getElementById('kpi-emp-atenciones').textContent = total;
  document.getElementById('kpi-emp-completadas').textContent = completadas;
  document.getElementById('kpi-emp-efectividad').textContent = `${efectividad}%`;
  document.getElementById('kpi-emp-proceso').textContent = enProceso;
  document.getElementById('kpi-emp-matricula').textContent = matricula;
  document.getElementById('kpi-emp-otros').textContent = otros;

  // Badges numéricas en pestañas
  document.getElementById('badge-count-asignadas').textContent = total;
  const disponiblesCount = visits.filter(v => !v.asignadoA || v.estado === 'En Espera').length;
  document.getElementById('badge-count-disponibles').textContent = disponiblesCount;
}

function renderEmployeeTable() {
  const tbody = document.getElementById('employeeTableBody');
  const emptyState = document.getElementById('tableEmptyState');
  tbody.innerHTML = '';

  const filterAsunto = document.getElementById('filter-asunto').value;
  const filterEstado = document.getElementById('filter-estado').value;
  const filterPrioridad = document.getElementById('filter-prioridad').value;
  const filterFecha = document.getElementById('filter-fecha').value;

  let list = visits.filter(v => {
    if (APP_STATE.employeeTab === 'asignadas') {
      return v.asignadoA === APP_STATE.currentUser.name;
    } else {
      return !v.asignadoA || v.estado === 'En Espera';
    }
  });

  // Filtros aplicados
  if (filterAsunto) list = list.filter(v => v.asunto.toLowerCase() === filterAsunto.toLowerCase());
  if (filterEstado) list = list.filter(v => v.estado.toLowerCase() === filterEstado.toLowerCase());
  if (filterPrioridad) list = list.filter(v => v.prioridad.toLowerCase() === filterPrioridad.toLowerCase());
  if (filterFecha) list = list.filter(v => v.fecha === filterFecha);

  if (list.length === 0) {
    emptyState.classList.remove('d-none');
    return;
  } else {
    emptyState.classList.add('d-none');
  }

  list.forEach(v => {
    const tr = document.createElement('tr');

    // Insignias sobrias de Prioridad
    let prioridadBadge = 'badge-subtle-primary';
    if (v.prioridad === 'Alta') prioridadBadge = 'badge-subtle-warning';
    if (v.prioridad === 'Urgente') prioridadBadge = 'badge-subtle-danger';
    if (v.prioridad === 'Baja') prioridadBadge = 'badge-subtle-primary';

    // Insignias sobrias de Estado
    let estadoBadge = 'badge-subtle-primary';
    if (v.estado === 'Completada') estadoBadge = 'badge-subtle-success';
    if (v.estado === 'En Proceso') estadoBadge = 'badge-subtle-warning';
    if (v.estado === 'En Espera') estadoBadge = 'badge-subtle-danger';

    // Botón de acción según pestaña activa
    let actionBtn = '';
    if (APP_STATE.employeeTab === 'asignadas') {
      actionBtn = `
        <button onclick="openRespondVisitModal('${v.id}')" class="btn btn-sm btn-outline-primary py-1 px-2.5 rounded-2 d-inline-flex align-items-center gap-1">
          <i class="bi bi-pencil-square"></i>
          <span>Atender</span>
        </button>
      `;
    } else {
      actionBtn = `
        <button onclick="claimVisit('${v.id}')" class="btn btn-sm btn-senati-primary py-1 px-2.5 rounded-2 d-inline-flex align-items-center gap-1">
          <i class="bi bi-hand-index-thumb"></i>
          <span>Tomar Ticket</span>
        </button>
      `;
    }

    tr.innerHTML = `
      <td class="fw-bold font-monospace text-dark">${v.codigo}</td>
      <td>
        <div class="fw-semibold text-dark">${v.visitante}</div>
        <div class="text-muted small">DNI: ${v.dni || 'Sin reg.'}</div>
      </td>
      <td><span class="badge bg-light text-secondary border px-2 py-1">${v.asunto}</span></td>
      <td class="text-muted text-truncate" style="max-width: 220px;" title="${v.consulta}">${v.consulta}</td>
      <td class="text-muted text-truncate" style="max-width: 220px;" title="${v.respuesta || 'Sin respuesta aún'}">
        ${v.respuesta || '<span class="text-muted fst-italic">Pendiente</span>'}
      </td>
      <td><span class="badge ${prioridadBadge} px-2 py-1 rounded-pill">${v.prioridad}</span></td>
      <td><span class="badge ${estadoBadge} px-2 py-1 rounded-pill">${v.estado}</span></td>
      <td class="text-nowrap small text-muted">
        <div>${v.fecha}</div>
        <div class="text-secondary small">${v.horaRegistro}</div>
      </td>
      <td class="text-center">${actionBtn}</td>
    `;
    tbody.appendChild(tr);
  });
}

function setupFilterEvents() {
  document.getElementById('filter-asunto')?.addEventListener('change', renderEmployeeTable);
  document.getElementById('filter-estado')?.addEventListener('change', renderEmployeeTable);
  document.getElementById('filter-prioridad')?.addEventListener('change', renderEmployeeTable);
  document.getElementById('filter-fecha')?.addEventListener('change', renderEmployeeTable);
}

function clearFilters() {
  document.getElementById('filter-asunto').value = '';
  document.getElementById('filter-estado').value = '';
  document.getElementById('filter-prioridad').value = '';
  document.getElementById('filter-fecha').value = '';
  renderEmployeeTable();
}

function claimVisit(visitId) {
  const v = visits.find(item => item.id === visitId);
  if (v) {
    v.asignadoA = APP_STATE.currentUser.name;
    v.estado = 'En Proceso';
    v.tInicio = Date.now();

    updateEmployeeKPIs();
    renderEmployeeTable();
    updateAdminDashboard();
    showToast(`Ticket ${v.codigo} asignado a ${APP_STATE.currentUser.name}.`);
  }
}


// 6. EXPORTACIONES LIMPIAS (CSV COMPATIBLE CON EXCEL)
function exportData(tipo) {
  let filtered = [...visits];
  let filename = `SenatiETI_Reporte_${tipo}_${new Date().toISOString().slice(0,10)}.csv`;

  if (tipo === 'mes') {
    const currentMonth = new Date().toISOString().slice(0,7);
    filtered = filtered.filter(v => v.fecha.startsWith(currentMonth));
  } else if (tipo === 'filtrado') {
    const fAsunto = document.getElementById('filter-asunto').value;
    const fEstado = document.getElementById('filter-estado').value;
    if (fAsunto) filtered = filtered.filter(v => v.asunto.toLowerCase() === fAsunto.toLowerCase());
    if (fEstado) filtered = filtered.filter(v => v.estado.toLowerCase() === fEstado.toLowerCase());
  }

  let csvContent = "\uFEFF"; // UTF-8 BOM
  csvContent += "Codigo,Visitante,DNI,Asunto,Prioridad,Estado,Fecha,HoraRegistro,Colaborador,Consulta,Respuesta\n";

  filtered.forEach(v => {
    const row = [
      `"${v.codigo}"`,
      `"${v.visitante.replace(/"/g, '""')}"`,
      `"${v.dni || ''}"`,
      `"${v.asunto}"`,
      `"${v.prioridad}"`,
      `"${v.estado}"`,
      `"${v.fecha}"`,
      `"${v.horaRegistro}"`,
      `"${v.asignadoA || 'Sin asignar'}"`,
      `"${(v.consulta || '').replace(/"/g, '""')}"`,
      `"${(v.respuesta || '').replace(/"/g, '""')}"`
    ];
    csvContent += row.join(",") + "\n";
  });

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.setAttribute("href", url);
  link.setAttribute("download", filename);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);

  const notif = document.getElementById('exportNotification');
  if (notif) {
    notif.textContent = `Descargado: ${filename}`;
    notif.classList.remove('d-none');
    setTimeout(() => notif.classList.add('d-none'), 4000);
  }
}


// 7. MODALES NATIVOS DE BOOTSTRAP 5
function openRegisterVisitModal() {
  const modalEl = document.getElementById('modalRegisterVisit');
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modal.show();
}

function handleNewVisitSubmit(e) {
  e.preventDefault();
  const codeNum = visits.length + 1001;
  const now = new Date();
  const hora = now.toTimeString().slice(0,5);
  const fecha = now.toISOString().slice(0,10);

  const asignadoVal = document.getElementById('reg-empleado').value;
  const asignado = asignadoVal === 'disponible' ? null : asignadoVal;

  const newVisit = {
    id: `VIS-${codeNum}`,
    codigo: `VIS-${codeNum}`,
    visitante: document.getElementById('reg-nombre').value,
    dni: document.getElementById('reg-dni').value,
    asunto: document.getElementById('reg-asunto').value,
    prioridad: document.getElementById('reg-prioridad').value,
    consulta: document.getElementById('reg-consulta').value,
    respuesta: '',
    estado: asignado ? 'En Proceso' : 'En Espera',
    asignadoA: asignado,
    fecha: fecha,
    horaRegistro: hora,
    tRegistro: Date.now(),
    tInicio: asignado ? Date.now() : null,
    tCierre: null
  };

  visits.unshift(newVisit);

  const modalEl = document.getElementById('modalRegisterVisit');
  const modal = bootstrap.Modal.getInstance(modalEl);
  modal?.hide();
  document.getElementById('formNewVisit').reset();

  updateEmployeeKPIs();
  renderEmployeeTable();
  updateAdminDashboard();
  showToast(`Visita registrada con ticket ${newVisit.codigo}.`);
}

function openQrSimulatorModal() {
  const modalEl = document.getElementById('modalQrSimulator');
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modal.show();
}

function handleQrMobileSubmit(e) {
  e.preventDefault();
  const codeNum = visits.length + 1001;
  const now = new Date();
  const hora = now.toTimeString().slice(0,5);
  const fecha = now.toISOString().slice(0,10);

  const newVisit = {
    id: `VIS-${codeNum}`,
    codigo: `VIS-${codeNum}`,
    visitante: document.getElementById('qr-mob-nombre').value,
    dni: document.getElementById('qr-mob-dni').value,
    asunto: document.getElementById('qr-mob-asunto').value,
    prioridad: 'Media',
    consulta: document.getElementById('qr-mob-consulta').value,
    respuesta: '',
    estado: 'En Espera',
    asignadoA: null,
    fecha: fecha,
    horaRegistro: hora,
    tRegistro: Date.now(),
    tInicio: null,
    tCierre: null
  };

  visits.push(newVisit);

  const modalEl = document.getElementById('modalQrSimulator');
  const modal = bootstrap.Modal.getInstance(modalEl);
  modal?.hide();
  document.getElementById('formQrMobile').reset();

  updateEmployeeKPIs();
  renderEmployeeTable();
  updateAdminDashboard();
  showToast(`Ticket QR ${newVisit.codigo} generado con éxito.`);
}

function openRespondVisitModal(visitId) {
  const v = visits.find(item => item.id === visitId);
  if (!v) return;

  document.getElementById('resp-ticket-id').value = v.id;
  document.getElementById('resp-ticket-code').textContent = `Ticket ${v.codigo}`;
  document.getElementById('resp-visitor-info').textContent = `Visitante: ${v.visitante} (DNI: ${v.dni || 'Sin registrar'})`;
  document.getElementById('resp-asunto-tag').textContent = v.asunto;
  document.getElementById('resp-prioridad-tag').textContent = v.prioridad;
  document.getElementById('resp-consulta-text').textContent = v.consulta;
  document.getElementById('resp-respuesta-text').value = v.respuesta || '';
  document.getElementById('resp-estado-select').value = v.estado === 'Completada' ? 'Completada' : 'En Proceso';

  const modalEl = document.getElementById('modalRespondVisit');
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modal.show();
}

function handleRespondSubmit(e) {
  e.preventDefault();
  const vId = document.getElementById('resp-ticket-id').value;
  const v = visits.find(item => item.id === vId);
  if (!v) return;

  v.respuesta = document.getElementById('resp-respuesta-text').value;
  v.estado = document.getElementById('resp-estado-select').value;
  if (v.estado === 'Completada') {
    v.tCierre = Date.now();
  }

  const modalEl = document.getElementById('modalRespondVisit');
  const modal = bootstrap.Modal.getInstance(modalEl);
  modal?.hide();

  updateEmployeeKPIs();
  renderEmployeeTable();
  updateAdminDashboard();
  showToast(`Ticket ${v.codigo} actualizado a '${v.estado}'.`);
}

function openDocumentationModal() {
  const modalEl = document.getElementById('modalDocs');
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modal.show();
}


// 8. DASHBOARD ADMINISTRADOR & CHART.JS (ESTILO MINIMALISTA)
function initAdminCharts() {
  // Gráfico 1: Horas Pico (Línea limpia y sobria)
  const ctxLine = document.getElementById('chartHorasPico')?.getContext('2d');
  if (ctxLine) {
    APP_STATE.charts.horasPico = new Chart(ctxLine, {
      type: 'line',
      data: {
        labels: ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00'],
        datasets: [{
          label: 'Volumen de Visitas en Sede',
          data: [14, 28, 48, 56, 32, 22, 29, 34, 38, 42, 54, 36, 18],
          borderColor: '#003882',
          backgroundColor: 'rgba(0, 56, 130, 0.08)',
          borderWidth: 2.5,
          fill: true,
          tension: 0.25,
          pointBackgroundColor: '#ffffff',
          pointBorderColor: '#003882',
          pointBorderWidth: 2,
          pointRadius: 3.5,
          pointHoverRadius: 5
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: '#1f2937',
            titleFont: { size: 12 },
            bodyFont: { size: 12 },
            padding: 8,
            cornerRadius: 6
          }
        },
        scales: {
          x: {
            grid: { color: '#f1f5f9' },
            ticks: { color: '#64748b', font: { size: 11 } }
          },
          y: {
            grid: { color: '#f1f5f9' },
            ticks: { color: '#64748b', font: { size: 11 } },
            suggestedMax: 65
          }
        }
      }
    });
  }

  // Gráfico 2: Rendimiento Colaboradores (Barras planas)
  const ctxBar = document.getElementById('chartColaboradores')?.getContext('2d');
  if (ctxBar) {
    APP_STATE.charts.colaboradores = new Chart(ctxBar, {
      type: 'bar',
      data: {
        labels: COLABORADORES_DATA.map(e => e.nombre),
        datasets: [
          {
            label: 'Completadas',
            data: COLABORADORES_DATA.map(e => e.completadas),
            backgroundColor: '#198754',
            borderRadius: 4
          },
          {
            label: 'En Proceso',
            data: COLABORADORES_DATA.map(e => e.enProceso),
            backgroundColor: '#d97706',
            borderRadius: 4
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top',
            labels: { boxWidth: 12, font: { size: 11 }, color: '#475569' }
          }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { color: '#64748b', font: { size: 11 } }
          },
          y: {
            grid: { color: '#f1f5f9' },
            ticks: { color: '#64748b', font: { size: 11 } }
          }
        }
      }
    });
  }

  // Gráfico 3: Distribución por Asunto (Doughnut plano)
  const ctxDonut = document.getElementById('chartAsuntos')?.getContext('2d');
  if (ctxDonut) {
    APP_STATE.charts.asuntos = new Chart(ctxDonut, {
      type: 'doughnut',
      data: {
        labels: ['Matrícula', 'Pagos', 'Tutoría', 'Consultas de Notas', 'Otros'],
        datasets: [{
          data: [38, 26, 16, 12, 8],
          backgroundColor: [
            '#003882', // Matrícula Senati Blue
            '#198754', // Pagos Verde
            '#d97706', // Tutoría Ambar
            '#0284c7', // Notas Celeste
            '#64748b'  // Otros Gris
          ],
          borderWidth: 2,
          borderColor: '#ffffff'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '72%',
        plugins: {
          legend: { display: false }
        }
      }
    });
  }
}

function updateAdminDashboard() {
  // 1. Visitantes Activos
  const activos = visits.filter(v => v.estado === 'En Espera' || v.estado === 'En Proceso');
  const enEspera = visits.filter(v => v.estado === 'En Espera').length;
  const enProceso = visits.filter(v => v.estado === 'En Proceso').length;
  
  const totalActivosDisplay = Math.max(activos.length, 38 + (activos.length % 12));
  document.getElementById('admin-kpi-activos').textContent = totalActivosDisplay;
  document.getElementById('admin-kpi-activos-sub').textContent = `${enEspera + 8} en espera • ${enProceso + 25} en atención`;

  // 2. Tiempo Promedio de Espera
  let sumEspera = 0;
  let countEspera = 0;
  visits.forEach(v => {
    if (v.tInicio && v.tRegistro) {
      sumEspera += (v.tInicio - v.tRegistro) / 60000;
      countEspera++;
    }
  });
  const avgEspera = countEspera > 0 ? (sumEspera / countEspera).toFixed(1) : "12.5";
  document.getElementById('admin-kpi-espera').textContent = avgEspera;

  // 3. Tiempo Promedio de Resolución
  let sumResol = 0;
  let countResol = 0;
  visits.forEach(v => {
    if (v.tCierre && v.tInicio) {
      sumResol += (v.tCierre - v.tInicio) / 60000;
      countResol++;
    }
  });
  const avgResol = countResol > 0 ? (sumResol / countResol).toFixed(1) : "8.2";
  document.getElementById('admin-kpi-resolucion').textContent = avgResol;

  // 4. Efectividad Global
  const completadas = visits.filter(v => v.estado === 'Completada').length;
  const efectividadGlobal = visits.length > 0 ? ((completadas / visits.length) * 100).toFixed(1) : "94.3";
  document.getElementById('admin-kpi-efectividad').textContent = `${efectividadGlobal}%`;

  // 5. Donut Data & Legend
  const counts = { 'Matrícula': 0, 'Pagos': 0, 'Tutoría': 0, 'Consultas de Notas': 0, 'Otros': 0 };
  visits.forEach(v => {
    if (counts[v.asunto] !== undefined) counts[v.asunto]++;
    else counts['Otros']++;
  });

  if (APP_STATE.charts.asuntos) {
    APP_STATE.charts.asuntos.data.datasets[0].data = [
      counts['Matrícula'] + 15,
      counts['Pagos'] + 10,
      counts['Tutoría'] + 6,
      counts['Consultas de Notas'] + 5,
      counts['Otros'] + 3
    ];
    APP_STATE.charts.asuntos.update();

    const legendEl = document.getElementById('donutLegend');
    if (legendEl) {
      const items = [
        { name: 'Matrícula', color: 'text-primary', count: counts['Matrícula'] + 15 },
        { name: 'Pagos', color: 'text-success', count: counts['Pagos'] + 10 },
        { name: 'Tutoría', color: 'text-warning', count: counts['Tutoría'] + 6 },
        { name: 'Notas', color: 'text-info', count: counts['Consultas de Notas'] + 5 },
      ];
      legendEl.innerHTML = items.map(it => `
        <div class="col-6 d-flex justify-content-between align-items-center mb-1">
          <span class="${it.color} small fw-medium">● ${it.name}</span>
          <span class="font-monospace small text-muted">${it.count}</span>
        </div>
      `).join('');
    }
  }

  // 6. Alertas de Auditoría (Tiempos Excedidos > 15 min)
  renderAdminAlerts();
}

function renderAdminAlerts() {
  const tbody = document.getElementById('adminAlertsTableBody');
  if (!tbody) return;
  tbody.innerHTML = '';

  const now = Date.now();
  const SLA_LIMIT_MS = 15 * 60 * 1000;

  const alerts = visits.filter(v => {
    if (v.estado === 'En Espera') {
      const waitTime = now - v.tRegistro;
      return waitTime > SLA_LIMIT_MS;
    }
    return false;
  });

  const badge = document.getElementById('alertsCountBadge');
  if (badge) badge.textContent = `${alerts.length} Críticas`;

  if (alerts.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="6" class="text-center py-3 text-muted small">
          <i class="bi bi-check-circle text-success me-1"></i> No hay tiempos de espera críticos excedidos.
        </td>
      </tr>
    `;
    return;
  }

  alerts.forEach(v => {
    const minutesWaiting = Math.round((now - v.tRegistro) / 60000);
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="fw-bold font-monospace text-danger">${v.codigo}</td>
      <td class="fw-medium">${v.visitante}</td>
      <td>${v.asunto}</td>
      <td>
        <span class="badge badge-subtle-danger px-2 py-1">
          ${minutesWaiting} min (Excedido)
        </span>
      </td>
      <td><span class="badge badge-subtle-warning">${v.prioridad}</span></td>
      <td class="text-center">
        <button onclick="reassignAlert('${v.id}')" class="btn btn-sm btn-danger py-0 px-2 rounded-2 small">
          Asignar Urgente
        </button>
      </td>
    `;
    tbody.appendChild(tr);
  });
}

function reassignAlert(visitId) {
  const v = visits.find(item => item.id === visitId);
  if (v) {
    v.asignadoA = APP_STATE.currentUser.name;
    v.estado = "En Proceso";
    v.tInicio = Date.now();
    updateAdminDashboard();
    updateEmployeeKPIs();
    renderEmployeeTable();
    showToast(`Ticket ${v.codigo} reasignado con urgencia.`);
  }
}

function resolveAllCriticalAlerts() {
  const now = Date.now();
  const SLA_LIMIT_MS = 15 * 60 * 1000;
  let count = 0;

  visits.forEach(v => {
    if (v.estado === 'En Espera' && (now - v.tRegistro) > SLA_LIMIT_MS) {
      v.asignadoA = APP_STATE.currentUser.name;
      v.estado = "En Proceso";
      v.tInicio = Date.now();
      count++;
    }
  });

  if (count > 0) {
    updateAdminDashboard();
    updateEmployeeKPIs();
    renderEmployeeTable();
    showToast(`Se reasignaron ${count} tickets críticos a colaboradores disponibles.`);
  } else {
    showToast("No existen tickets con tiempo de espera excedido.");
  }
}


// 9. SIMULADOR DE FLUJO Y TOASTS NOTIFICADORES
function generateRandomVisit() {
  const nombres = ["Alejandro Castro", "Mariana Quispe", "Fernando Huamán", "Gabriela Torres", "Sebastián Flores"];
  const asuntos = ["Matrícula", "Pagos", "Tutoría", "Consultas de Notas", "Otros"];
  const prioridades = ["Alta", "Media", "Baja", "Urgente"];

  const randomNombre = nombres[Math.floor(Math.random() * nombres.length)];
  const randomAsunto = asuntos[Math.floor(Math.random() * asuntos.length)];
  const randomPrioridad = prioridades[Math.floor(Math.random() * prioridades.length)];
  const codeNum = visits.length + 1001;
  const now = new Date();

  const newVisit = {
    id: `VIS-${codeNum}`,
    codigo: `VIS-${codeNum}`,
    visitante: randomNombre,
    dni: `7${Math.floor(1000000 + Math.random() * 9000000)}`,
    asunto: randomAsunto,
    prioridad: randomPrioridad,
    consulta: `Consulta sobre trámites de ${randomAsunto.toLowerCase()}.`,
    respuesta: '',
    estado: 'En Espera',
    asignadoA: null,
    fecha: now.toISOString().slice(0,10),
    horaRegistro: now.toTimeString().slice(0,5),
    tRegistro: Date.now() - Math.floor(Math.random() * 900000),
    tInicio: null,
    tCierre: null
  };

  visits.push(newVisit);
  updateEmployeeKPIs();
  renderEmployeeTable();
  updateAdminDashboard();
  showToast(`Nuevo visitante simulado: ${newVisit.visitante} (${newVisit.asunto})`);
}

function toggleAutoSimulation() {
  const btn = document.getElementById('btnAutoSim');
  const text = document.getElementById('autoSimText');

  if (APP_STATE.autoSimInterval) {
    clearInterval(APP_STATE.autoSimInterval);
    APP_STATE.autoSimInterval = null;
    if (text) text.textContent = "Auto-Tráfico (OFF)";
    btn?.classList.remove('btn-success');
    btn?.classList.add('btn-outline-secondary');
    showToast("Simulación continua detenida.");
  } else {
    APP_STATE.autoSimInterval = setInterval(generateRandomVisit, 5000);
    if (text) text.textContent = "Auto-Tráfico (ON)";
    btn?.classList.remove('btn-outline-secondary');
    btn?.classList.add('btn-success');
    showToast("Simulación continua activada (visita cada 5s).");
  }
}

function showToast(message) {
  let toastContainer = document.getElementById('senatiToastContainer');
  if (!toastContainer) {
    toastContainer = document.createElement('div');
    toastContainer.id = 'senatiToastContainer';
    toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    toastContainer.style.zIndex = '1090';
    document.body.appendChild(toastContainer);
  }

  const toastEl = document.createElement('div');
  toastEl.className = 'toast align-items-center text-bg-dark border-0 shadow-sm';
  toastEl.setAttribute('role', 'alert');
  toastEl.setAttribute('aria-live', 'assertive');
  toastEl.setAttribute('aria-atomic', 'true');
  toastEl.innerHTML = `
    <div class="d-flex">
      <div class="toast-body d-flex align-items-center gap-2 small">
        <span class="spinner-grow spinner-grow-sm text-primary" style="width: 8px; height: 8px;"></span>
        ${message}
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
    </div>
  `;
  toastContainer.appendChild(toastEl);
  const bsToast = new bootstrap.Toast(toastEl, { delay: 3500 });
  bsToast.show();
  toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}
