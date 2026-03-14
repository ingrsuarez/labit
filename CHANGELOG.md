# CHANGELOG — Labit

> Historial de cambios por versión.
> Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/).

---

## [v1.0.0] — 2026-03-14 — Línea base del proyecto

### Documentado
- Stack tecnológico completo en BLUEPRINT.md (Laravel 11, Tailwind, Livewire 3, Jetstream, Spatie, etc.)
- Estructura del proyecto y patrones de arquitectura (MVC, Services, Middleware pipeline)
- Sistema de roles y permisos (5 roles, 4 middleware, permisos granulares por sección)
- Estado del proyecto en STATUS.md con cola de prompts y agentes disponibles
- Roadmap reestructurado con fases, áreas candidatas y progreso general

### Estado de módulos al momento de la línea base
- **Laboratorio clínico**: pacientes, admisiones con protocolo, tests/determinaciones, nomenclador por obra social, carga y validación de resultados, reportes mensuales con exportación Excel
- **Laboratorio de muestras**: muestras de aguas/alimentos, determinaciones, PDFs, etiquetas con código de barras, envío por email
- **Ventas**: clientes, servicios, presupuestos (PRES-AÑO-NNNN), facturas de venta con IVA, puntos de venta, recibos de cobro (RC-AÑO-NNNN)
- **Compras**: proveedores, insumos por categoría con stock mínimo, movimientos de stock, flujo completo (cotización → OC → remito → factura → orden de pago)
- **RRHH**: legajos de empleados, organigrama jerárquico, conceptos salariales, liquidación mensual (individual y masiva), SAC, recibos PDF, vacaciones con calendario visual, ausencias/licencias, documentación con vencimiento
- **Calidad**: no conformidades con seguimiento y acciones correctivas, circulares internas con firma digital
- **Portal del empleado**: dashboard, equipo, directorio, organigrama, recibos de sueldo, solicitudes de vacaciones/licencias, lectura y firma de circulares

### Notas
- 51 modelos Eloquent, 90 migraciones, ~45 controladores
- Infraestructura de sistema multi-agente establecida (PM, Dev, QA, Reviewer, Designer, CEO)

---

> Cada versión nueva se registra aquí al completarse. El formato sigue la convención:
> `## [vX.Y.Z] — FECHA — Nombre de la versión`
