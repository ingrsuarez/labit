# STATUS — Labit

> Estado actual del proyecto y del sistema de agentes.
> Última actualización: 2026-03-17

---

## Estado general

| Campo | Valor |
|---|---|
| **Versión actual** | v1.4.0 |
| **Última completada** | v1.4.0 — Notas de crédito electrónicas |
| **En proceso** | — |
| **Próxima** | v1.5.0 — Lector QR facturas de compra |
| **Pendientes en cola** | 1 |
| **Completadas** | 7 |

---

## Cola de prompts

### Pendientes (1)

| Versión | Nombre | Estimación | Dependencias | Archivo |
|---|---|---|---|---|
| v1.5.0 | Lector QR facturas de compra | 2h | v1.3.0 | `pendientes/v1.5.0-qr-reader-compras.md` |

### En proceso (0)

_Sin prompts en ejecución._

### Completados (7)

| Versión | Nombre | Fecha | Tag |
|---|---|---|---|
| v1.4.0 | Notas de crédito electrónicas | 2026-03-17 | v1.4.0 |
| v1.3.1 | Fix AFIP CondicionIVAReceptorId + ImpTotal | 2026-03-15 | v1.3.1 |
| v1.3.0 | Facturación electrónica WSFEv1 | 2026-03-14 | v1.3.0 |
| v1.2.0 | Infraestructura AFIP | 2026-03-14 | v1.2.0 |
| v1.1.0 | Normalización de line endings | 2026-03-16 | v1.1.0 |
| v1.0.1 | README del proyecto | 2026-03-14 | v1.0.1 |
| v1.0.0 | Línea base del proyecto | 2026-03-14 | v1.0.0 |

---

## Cadena de dependencias

```
v1.0.0 (completada)
├── v1.0.1 — README del proyecto (completada)
├── v1.1.0 — Normalización de line endings (completada)
├── v1.2.0 — Infraestructura AFIP (completada)
│   └── v1.3.0 — Facturación electrónica WSFEv1 (completada)
│       ├── v1.3.1 — Fix AFIP CondicionIVAReceptorId + ImpTotal (completada)
│       ├── v1.4.0 — Notas de crédito electrónicas (completada)
│       └── v1.5.0 — Lector QR facturas de compra (pendiente)
```

---

## Agentes disponibles

| Agente | Archivo | Estado |
|---|---|---|
| CEO (Orquestador) | `.agents/AgenteCEO/AGENTE_CEO.md` | Activo |
| PM (Planificación) | `.agents/AgentePM/AGENTE_PM.md` | Activo |
| Dev (Ejecución) | `.agents/AgenteProgramador/AGENTE_WORKFLOW.md` | Activo |
| QA (Calidad) | `.agents/AgenteQA/AGENTE_QA.md` | Activo |
| Reviewer (Code Review) | `.agents/AgenteReviewer/AGENTE_REVIEWER.md` | Activo |
| Designer (UI/UX) | `.agents/AgenteDesigner/AGENTE_DESIGNER.md` | Activo |

---

## Documentación del proyecto

| Documento | Estado |
|---|---|
| `README.md` | Actualizado (v1.0.1) |
| `ROADMAP.md` | Actualizado |
| `BLUEPRINT.md` | Actualizado |
| `STATUS.md` | Actualizado (este archivo) |
| `CHANGELOG.md` | Actualizado |
| `RESUMEN_INSTITUCIONAL.md` | Completo |
| `agent-bootstrap/PHASES.md` | Creado |

---

## Próximo paso recomendado

Ejecutar el siguiente prompt de la cola:

```
Lee .agents/AgenteProgramador/AGENTE_WORKFLOW.md y ejecutá el ciclo.
```

O arrancar una sesión de planificación:

```
Lee .agents/AgentePM/AGENTE_PM.md y arrancá una sesión de planificación.
```

---

> Este archivo se actualiza automáticamente al completar una versión o al iniciar una sesión de CEO.
