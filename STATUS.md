# STATUS — Labit

> Estado actual del proyecto y del sistema de agentes.
> Última actualización: 2026-03-22

---

## Estado general

| Campo | Valor |
|---|---|
| **Versión actual** | v2.2.0 |
| **Última completada** | v2.2.0 — Compras y pagos multi-empresa |
| **En proceso** | — |
| **Próxima** | v2.3.0 — RRHH multi-empresa |
| **Pendientes en cola** | 1 |
| **Completadas** | 16 |

---

## Cola de prompts

### Pendientes (1)

| Versión | Nombre | Estimación | Dependencias | Archivo |
|---|---|---|---|---|
| v2.3.0 | RRHH multi-empresa | 2h | v2.0.0 | `pendientes/v2.3.0-multi-empresa-rrhh.md` |

### En proceso (0)

_Sin prompts en ejecución._

### Completados (16)

| Versión | Nombre | Fecha | Tag |
|---|---|---|---|
| v2.2.0 | Compras y pagos multi-empresa | 2026-03-22 | v2.2.0 |
| v2.1.3 | UX feedback visual guardado resultados | 2026-03-22 | v2.1.3 |
| v2.1.2 | Fix "Aplicar a todos" valores de referencia | 2026-03-22 | v2.1.2 |
| v2.1.0 | Ventas y cobros multi-empresa | 2026-03-22 | v2.1.0 |
| v2.0.0 | Infraestructura multi-empresa | 2026-03-21 | v2.0.0 |
| v1.5.3 | Seeder de jerarquía padre-hijo de prácticas | 2026-03-20 | v1.5.3 |
| v1.5.2 | Roles y permisos del módulo de muestras | 2026-03-17 | v1.5.2 |
| v1.5.1 | Roles y permisos del módulo de laboratorio | 2026-03-17 | v1.5.1 |
| v1.4.1 | Fix guardado de resultados de protocolo | 2026-03-17 | v1.4.1 |
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
│       └── v1.4.0 — Notas de crédito electrónicas (completada)
├── v1.5.3 — Seeder jerarquía padre-hijo prácticas (completada)
│   └── v2.0.0 — Infraestructura multi-empresa (completada)
│       ├── v2.1.0 — Ventas y cobros multi-empresa (completada)
│       │   └── v2.1.2 — Fix Aplicar a todos ref values (completada)
│       │       └── v2.1.3 — UX feedback guardado resultados (completada)
│       ├── v2.2.0 — Compras y pagos multi-empresa (completada)
│       └── v2.3.0 — RRHH multi-empresa (pendiente)
├── v1.4.1 — Fix guardado de resultados de protocolo (completada)
│   └── v1.5.1 — Roles y permisos laboratorio clínico (completada)
│       └── v1.5.2 — Roles y permisos muestras (completada)
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

Próximo en cola: **v2.3.0 — RRHH multi-empresa**

Bloque multi-empresa restante (1 versión):
1. v2.3.0 — RRHH (dep: v2.0.0)

> v2.1.0, v2.2.0 y v2.3.0 son independientes entre sí y pueden ejecutarse en paralelo.

---

> Este archivo se actualiza automáticamente al completar una versión o al iniciar una sesión de CEO.
