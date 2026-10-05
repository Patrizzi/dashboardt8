# MEGAPROMPT para Gemini 3.8 Flash HIGH: Desarrollo del Sistema "Senati ETI"

**Rol:** Actúa como un Arquitecto de Software Senior y Desarrollador Full-Stack especializado en la creación de interfaces de usuario (UI/UX) intuitivas y sistemas de gestión basados en datos.

**Objetivo del Proyecto:** Desarrollar la plataforma web integral **"Senati ETI - Sistema de Gestión de Visitas"**. El sistema debe resolver la saturación en recepción y la falta de trazabilidad mediante dos módulos principales: un **Panel de Empleado** (operativo, basado en capturas existentes) y un **Dashboard de Administrador** (estratégico, basado en requerimientos funcionales documentados).

---

## ESTRUCTURA DEL SISTEMA Y FLUJO DE TRABAJO

### 1. Módulo de Auto-registro (QR)
*Crear esta interfaz simple basándose en la imagen de referencia, aunque no es el foco principal del prompt.*
*   Pantalla con el logo de Senati.
*   Textos: "Senati ETI", "Sistema de Gestión de Visitas".
*   Un código QR grande central para que el visitante se registre.

### 2. Módulo 2: Panel de Empleado (Gestión Operativa)
*RECREAR EXACTAMENTE LA INTERFAZ DE LA CAPTURA DE PANTALLA ADJUNTA (image_05ea58.png).*
*   **Barra Superior:** Logo Senati, título "Panel Empleado", nombre de empleado (ej: Carlos Rodríguez López (EMP001)). Botones: "Modo Admin" (amarillo o naranja), "Registrar Visita" (verde), "Salir" (rojo).
*   **Tarjetas de Métricas (KPIs Individuales):** Replicar las seis tarjetas con iconos y números grandes a cero: 'Mis Atenciones', 'Completadas', 'Efectividad 0%', 'En Proceso', 'Matrícula', 'Pagos/Tutoría/Otros'.
*   **Sección de Filtros:** Reproducir fielmente la barra de 'Filtros de Clasificación' con los selectores de 'Todos los asuntos', 'Todos los estados', 'Todas las prioridades' y selector de fecha (`dd/mm/aaaa`). Botones 'Filtrar' (azul) y 'Limpiar' (gris).
*   **Botones de Exportación:** Reproducir los tres botones: 'Exportar Mes', 'Exportar Trimestre' y 'Exportar Filtrado' (verdes).
*   **Sistema de Pestañas:** 'Mis Visitas Asignadas' (azul) y 'Visitas Disponibles' (gris).
*   **Tabla de Datos:** Replicar las columnas exactas de la sección 'Mis Visitas Asignadas' (Responde las consultas y actualiza el estado): Código, Visitante, Asunto, Consulta, Respuesta, Prioridad, Estado, Fecha, Acciones. Poblada con datos de muestra y botones de acción simulados.

### 3. Módulo 3: Dashboard de Administrador (Supervisión Estratégica)
*DISEÑAR UNA NUEVA INTERFAZ GERENCIAL AVANZADA Basada en la Documentación (T8.pdf).*
*   **KPIs Globales:** Crear tarjetas métricas superiores para:
    *   `Visitantes Activos en Sede` (contador en tiempo real, ej: 45).
    *   `Tiempo Promedio de Espera` (ej: 12.5 min, calculado en minutos desde que el visitante escanea el QR hasta que un empleado toma su ticket).
    *   `Tiempo Promedio de Resolución` (ej: 8.2 min, cuánto tarda el personal en cerrar la consulta desde que inicia la atención).
    *   `Tasa de Efectividad Global` (ej: 94.3%, porcentaje total de atenciones completadas exitosamente frente a abandonadas/en proceso).
*   **Gráficos y Visualizaciones Avanzadas (UX/UI):** Integrar los siguientes gráficos detallados:
    *   **Gráfico de Líneas - Mapa de "Horas Pico":** Título: "Mapa de Horas Pico - Volumen de Visitas Diarias". Muestra el volumen de visitas a lo largo del día para identificar recesos o cambios de turno.
    *   **Gráfico de Barras - Rendimiento por Colaborador:** Título: "Rendimiento Mensual por Colaborador - Atenciones Completadas". Comparativa de atenciones por miembro del equipo para identificar sobrecargas o eficiencia.
    *   **Gráfico de Anillo (Donut Chart) - Distribución por Asunto:** Título: "Distribución de Visitas por Asunto". Muestra el porcentaje de los motivos de visita ('Matrícula', 'Pagos', 'Tutorías', 'Consultas de Notas', etc.).
*   **Funcionalidades de Auditoría:**
    *   **Tabla de Alertas:** Título: "Alertas de Auditoría - Tiempos Límite Excedidos". Una lista que destaque en rojo intenso aquellas atenciones que han sobrepasado el tiempo límite de espera establecido, permitiendo al administrador intervenir si es necesario.

---

## ESPECIFICACIONES TÉCNICAS Y DE ESTILO

*   **UI/UX:** El diseño debe ser limpio, moderno y profesional, aplicando buenas prácticas. Usar un diseño de tarjetas, tipografía clara e iconografía consistente. Seguir la paleta de colores de la imagen de referencia para el panel de empleado (azules institucionales, blancos, verdes de acción, rojos de alerta).
*   **Librerías Visuales:** Usar una librería de gráficos profesional (como Chart.js, Recharts, o similar de Tailwind/HTML puro) para todas las visualizaciones del administrador.
*   **Lógica de Negocio Simulada:** Asegurar que los conceptos de datos fluyan correctamente. Cuando un empleado interactúa, las métricas deben tener un correlato con el dashboard. Configurar lógicamente las marcas de tiempo (timestamps) para los cálculos de tiempos de espera y resolución.
*   **Entregable Final:** Genera TODO el código necesario (HTML, CSS/Tailwind, y JS) en un **ÚNICO ARCHIVO** funcional que sirva como prototipo de alta fidelidad, demostrando la navegación entre el Panel de Empleado y el Dashboard de Administrador (puedes usar pestañas principales o botones para cambiar entre las vistas de Empleado y Administrador en el prototipo).