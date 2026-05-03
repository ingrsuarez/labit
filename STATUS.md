# STATUS — Labit

> Estado actual del proyecto y del sistema de agentes.
> Última actualización: 2026-05-03 (Release: develop → master con v1.66.0 + fix AFIP error visibility)

---

## Estado general

| Campo | Valor |
|---|---|
| **Versión actual** | **v1.66.0** en **develop** y **master** |
| **Última en master** | v1.66.0 (release 2026-05-03 — incluye también el hotfix de AFIP error visibility) |
| **Última completada** | v1.66.0 — Dashboard ejecutivo financiero + reubicación del panel de RRHH |
| **En proceso** | — |
| **Próxima recomendada** | v1.63.0 — Percepciones de compra (requiere Designer) |
| **Pendientes en cola** | 3 |
| **Completadas** | 114 |

---

## Cola de prompts

### Pendientes (3) — orden de ejecución recomendado por PM

| # | Versión | Nombre | Designer | Prompt |
|---|---|---|---|---|
| 1 | v1.63.0 | Percepciones de compra: catálogo, FC, asiento contable y saldos | Sí | `pendientes/v1.63.0-percepciones-compra.md` |
| 2 | v1.63.1 | Percepciones en NC de proveedor (espejo de v1.63.0) | — | `pendientes/v1.63.1-percepciones-nc-proveedor.md` |
| 3 | v1.64.0 | Declaraciones de impuestos e imputación de anticipos sufridos | Sí | `pendientes/v1.64.0-declaraciones-impuestos-imputacion-anticipos.md` |

> **Razonamiento del orden** (sesión PM/Dev 2026-05-01, actualizado 2026-05-03):
> 1. ~~v1.66.0~~ completada (dashboard ejecutivo + mudanza RRHH)
> 2-3-4. Bloque temático de impuestos (v1.63.0 → v1.63.1 → v1.64.0) para no perder contexto técnico (asientos, plan de cuentas, percepciones)

### En proceso (0)

_Sin prompts en ejecución._

### Completados (102)

| Versión | Nombre | Fecha | Tag |
|---|---|---|---|
| v1.66.0 | Dashboard ejecutivo financiero + reubicación del panel de RRHH | 2026-05-03 | v1.66.0 |
| v1.65.3 | Hotfix vet: validar y mostrar resultado 0 (basofilos/glucosa = 0) | 2026-05-02 | v1.65.3 |
| v1.65.2 | Fix PDF veterinario: excluir no validadas + jerarquía orphans | 2026-05-02 | v1.65.2 |
| v1.65.1 | Etiquetas de protocolo para laboratorio veterinario | 2026-05-01 | v1.65.1 |
| v1.65.0 | Borrador editable y líneas extras en facturación masiva | 2026-04-27 | v1.65.0 |
| v1.62.0 | Unificar insumos: merge A → B con reasignación de referencias | 2026-04-26 | v1.62.0 |
| v1.61.0 | Enter agrega ítem en Factura de Compra (igual que en remito) | 2026-04-26 | v1.61.0 |
| v1.54.0 | UX combobox insumos: ocultar input al seleccionar (delivery-notes + purchase-invoices) | 2026-04-26 | v1.54.0 |
| v1.51.0 | Endpoint POST `/api/v1/results/batch` (idempotencia + ALREADY_VALIDATED) | 2026-04-18 | v1.51.0 |
| v1.48.5 | Formato extendido de barcode (`{protocol_number}^{material_abbreviation}`) | 2026-04-18 | v1.48.5 |
| v1.47.0 | API pública: protocolos unificados (clinical/sample/vet) + PII gating | 2026-04-18 | v1.47.0 |
| v1.46.0 | API pública con API key + módulo admin de keys (LISCOM foundation) | 2026-04-18 | v1.46.0 |
| v1.45.0 | Eliminar cliente: protocolos y facturación | 2026-04-13 | v1.45.0 |
| v1.44.0 | Nomenclador veterinario (hub lab vet) | 2026-04-12 | v1.44.0 |
| v1.43.0 | Precios vet: NBU veterinaria × NBU práctica | 2026-04-12 | v1.43.0 |
| v1.42.1 | Orden determinaciones vet (show + PDF) | 2026-04-06 | v1.42.1 |
| v1.42.0 | Servicios de compra en FC + estadísticas | 2026-04-07 | v1.42.0 |
| v1.41.2 | Logo en PDF recibo + PDF en listado | 2026-04-07 | v1.41.2 |
| v1.41.0 | PDF de recibo de cobro para cliente | 2026-04-06 | v1.41.0 |
| v1.40.0 | Retenciones sufridas en recibos de cobro | 2026-04-06 | v1.40.0 |
| v1.39.1 | OP a proveedor con e-cheqs desde cartera | 2026-04-05 | v1.39.1 |
| v1.39.0 | Recibos de cobro: múltiples medios y e-cheq (cartera) | 2026-04-05 | v1.39.0 |
| v1.38.2 | Hotfix ParseError recibo de cobro create | 2026-04-05 | v1.38.2 |
| v3.4.0 | Libro Diario, Libro Mayor y asientos manuales | 2026-04-05 | v3.4.0 |
| v1.35.3 | Movimientos stock: selector de lotes | 2026-04-05 | v1.35.3 |
| v1.36.1 | FC compra: editar y reasignar empresa | 2026-04-05 | v1.36.1 |
| v1.35.2 | Movimientos stock: cantidad entera | 2026-04-05 | v1.35.2 |
| v1.38.0 | Stock por sede: compras, remitos, FC y vistas | 2026-04-05 | v1.38.0 |
| v1.37.0 | Stock por sede: modelo, migraciones y movimientos | 2026-04-05 | v1.37.0 |
| v1.36.0 | Múltiples remitos en factura de compra | 2026-04-05 | v1.36.0 |
| v1.35.1 | Remito: lote/vencimiento solo si insumo `tracks_lot` | 2026-04-05 | v1.35.1 |
| v3.3.0 | Asientos automáticos desde transacciones | 2026-04-04 | v3.3.0 |
| v1.35.0 | Cuenta corriente de proveedores | 2026-04-04 | v1.35.0 |
| v1.32.4 | Editar y eliminar remitos con sincronización de stock | 2026-04-04 | v1.32.4 |
| v1.34.0 | Asociación de remitos y control de stock en facturas de compra | 2026-04-04 | v1.34.0 |
| v1.32.3 | Marca en búsqueda y display de insumos | 2026-04-04 | v1.32.3 |
| v1.33.0 | UX y validaciones en formulario de factura de compra | 2026-04-04 | v1.33.0 |
| v1.32.2 | Fix buscador inteligente de items en remitos | 2026-04-04 | v1.32.2 |
| v3.2.0 | Conciliación Bancaria: Motor de Matcheo | 2026-04-01 | v3.2.0 |
| v3.1.0 | Conciliación Bancaria: Cuentas e Importación | 2026-04-01 | v3.1.0 |
| v3.0.0 | Infraestructura contable base | 2026-03-31 | v3.0.0 |
| v1.28.1 | Fix QR scanner captura nativa | 2026-03-31 | v1.28.1 |
| v1.32.1 | Fix layout ítems factura de compra | 2026-03-31 | v1.32.1 |
| v1.32.0 | Buscador inteligente de insumos en facturas de compra | 2026-03-31 | v1.32.0 |
| v1.31.1 | Facturación masiva por OS, aguas y veterinarias | 2026-03-30 | v1.31.1 |
| v1.31.0 | Control de facturación de protocolos | 2026-03-30 | v1.31.0 |
| v1.30.3 | Fix protocolos sin sede: visibilidad y asignación masiva | 2026-03-30 | v1.30.3 |
| v1.30.2 | Sede por defecto del usuario y selector en header | 2026-03-30 | v1.30.2 |
| v1.18.1 | Fix etiquetas lab clínico: una por material | 2026-03-29 | v1.18.1 |
| v1.30.1 | Reorganización del sidebar del laboratorio | 2026-03-29 | v1.30.1 |
| v1.30.0 | Sedes laboratorio y procedencia de protocolos | 2026-03-29 | v1.30.0 |
| v1.29.0 | Libro IVA digital (AFIP RG 4597) | 2026-03-29 | v1.29.0 |
| v1.26.2 | UX admisión vet buscador enter + email dueño | 2026-03-29 | v1.26.2 |
| v1.28.0 | Lector QR facturas de compra (RG 4892) | 2026-03-29 | v1.28.0 |
| v1.27.0 | Entrada de stock desde factura sin remito + modal insumo | 2026-03-29 | v1.27.0 |
| v1.26.0 | Búsqueda de protocolos por dueño/animal | 2026-03-29 | v1.26.0 |
| v1.25.0 | PDF resultados veterinarios + envío email | 2026-03-29 | v1.25.0 |
| v1.24.0 | Protocolo veterinario (VetAdmission) | 2026-03-29 | v1.24.0 |
| v1.23.0 | Nuevo formato de numeración de protocolos | 2026-03-29 | v1.23.0 |
| v1.22.0 | Valores de referencia por especie | 2026-03-29 | v1.22.0 |
| v1.21.0 | Tipificación de Customers + CRUD de Veterinarios | 2026-03-29 | v1.21.0 |
| v1.20.0 | Infraestructura veterinaria base | 2026-03-29 | v1.20.0 |
| v1.19.2 | Fix validación y PDF para resultados con valor cero | 2026-03-29 | v1.19.2 |
| v1.19.1 | Fix deselección de padre en determinaciones | 2026-03-29 | v1.19.1 |
| v1.19.0 | Consulta de padrón AFIP por CUIT | 2026-03-28 | v1.19.0 |
| v1.18.0 | Etiquetas de protocolo para laboratorio clínico | 2026-03-27 | v1.18.0 |
| v1.17.0 | Fix impresión de etiquetas Zebra | 2026-03-26 | v1.17.0 |
| v2.7.0 | Auditoría vista centralizada | 2026-03-26 | v2.7.0 |
| v2.5.0 | Auditoría infraestructura + módulo clínico | 2026-03-26 | v2.5.0 |
| v1.16.0 | Planillas de trabajo diario del laboratorio | 2026-03-26 | v1.16.0 |
| v2.4.1 | Hotfix redirect loop lab + condición Mi Portal | 2026-03-26 | v2.4.1 |
| v2.4.0 | Control de acceso por rol y redirección inteligente | 2026-03-25 | v2.4.0 |
| v1.15.0 | Sub-padres y orden fijo de determinaciones | 2026-03-25 | v1.15.0 |
| v1.14.1 | Otros valores de referencia en determinaciones | 2026-03-25 | v1.14.1 |
| v1.14.0 | Precios en protocolos de aguas y alimentos | 2026-03-25 | v1.14.0 |
| v1.12.0 | PDF protocolos lab clínico + envío email | 2026-03-25 | v1.12.0 |
| v1.11.1 | Configuración de correos del laboratorio | 2026-03-25 | v1.11.1 |
| v1.13.0 | Nomenclador en tiempo real (sin duplicación) | 2026-03-25 | v1.13.0 |
| v1.13.1 | Fix searchTests usa nomenclador base | 2026-03-25 | v1.13.1 |
| v1.11.2 | Buscador en dropdown de obra social | 2026-03-24 | v1.11.2 |
| v1.11.0 | Importación de obras sociales desde Excel | 2026-03-24 | v1.11.0 |
| v1.10.0 | Importación de nomencladores desde Excel | 2026-03-24 | v1.10.0 |
| v1.9.0 | Firma digital de validadores y nombre automático de PDF | 2026-03-24 | v1.9.0 |
| v1.8.0 | Búsqueda activa en protocolos de muestras | 2026-03-23 | v1.8.0 |
| v1.7.0 | Cobro a particulares y control de deuda | 2026-03-23 | v1.7.0 |
| v1.6.1 | Filtrar nomencladores de dropdowns y crear Particular | 2026-03-23 | v1.6.1 |
| v1.6.0 | Formato tabular en PDFs de informes | 2026-03-23 | v1.6.0 |
| v1.5.4 | Tests faltantes y jerarquía padre-hijo completa | 2026-03-22 | v1.5.4 |
| v2.3.0 | RRHH multi-empresa | 2026-03-22 | v2.3.0 |
| v2.2.1 | Fix columnas vacías en vista de protocolo | 2026-03-22 | v2.2.1 |
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
│       ├── v1.4.0 — Notas de crédito electrónicas (completada)
│       └── v1.19.0 — Consulta de padrón AFIP por CUIT (completada)
├── v1.20.0 — Infraestructura veterinaria base (completada)
│   └── v1.21.0 — Tipificación Customers + CRUD Veterinarios (completada)
│       └── v1.22.0 — Valores de referencia por especie (completada)
│           └── v1.23.0 — Nuevo formato numeración protocolos (completada)
│               └── v1.24.0 — Protocolo veterinario VetAdmission (completada)
│                   ├── v1.25.0 — PDF resultados vet + email (completada)
│                   ├── v1.26.0 — Búsqueda por dueño/animal (completada)
│                   └── v1.26.2 — UX buscador Enter + email dueño (completada)
├── v1.27.0 — Entrada stock desde factura compra (completada)
│   └── v1.28.0 — Lector QR facturas de compra (completada)
│       └── v1.28.1 — Fix QR scanner captura nativa (completada)
├── v1.32.0 — Buscador inteligente de insumos en facturas de compra (completada)
│   └── v1.32.2 — Fix buscador inteligente de items en remitos (completada)
│       └── v1.33.0 — UX y validaciones factura de compra (completada)
│           └── v1.34.0 — Asociación remitos y control stock FC (completada)
│               └── v1.36.0 — Múltiples remitos en factura de compra (completada)
│                   └── v1.37.0 — Stock por sede: modelo y movimientos (completada)
│                       └── v1.38.0 — Stock por sede: compras y vistas (completada)
├── v1.29.0 — Libro IVA digital AFIP RG 4597 (completada)
├── v1.30.0 — Sedes laboratorio y procedencia protocolos (completada)
├── v1.5.3 — Seeder jerarquía padre-hijo prácticas (completada)
│   ├── v1.5.4 — Tests faltantes y jerarquía completa (completada)
│   │   └── v1.6.0 — Formato tabular en PDFs de informes (completada)
│   │       └── v1.6.1 — Filtrar nomencladores dropdowns (completada)
│   │           └── v1.7.0 — Cobro particulares y deuda (completada)
│   │               └── v1.8.0 — Búsqueda activa protocolos (completada)
│   └── v2.0.0 — Infraestructura multi-empresa (completada)
│       ├── v2.1.0 — Ventas y cobros multi-empresa (completada)
│       │   └── v2.1.2 — Fix Aplicar a todos ref values (completada)
│       │       └── v2.1.3 — UX feedback guardado resultados (completada)
│       ├── v2.2.0 — Compras y pagos multi-empresa (completada)
│       │   └── v2.2.1 — Fix columnas vacías protocolo (completada)
│       └── v2.3.0 — RRHH multi-empresa (completada)
├── v1.4.1 — Fix guardado de resultados de protocolo (completada)
│   └── v1.5.1 — Roles y permisos laboratorio clínico (completada)
│       └── v1.5.2 — Roles y permisos muestras (completada)
├── v1.17.0 — Fix impresión de etiquetas Zebra (completada)
│   └── v1.18.0 — Etiquetas de protocolo para lab clínico (completada)
├── v2.5.0 — Auditoría infraestructura + módulo clínico (completada)
├── v3.0.0 — Infraestructura contable base (completada)
│   └── v3.1.0 — Conciliación Bancaria: Cuentas e Importación (completada)
│       └── v3.2.0 — Conciliación Bancaria: Motor de Matcheo (completada)
│           └── v3.3.0 — Asientos automáticos desde transacciones (completada)
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
| `BLUEPRINT.md` | Actualizado (DD-009 borrador editable facturación masiva, 2026-04-27) |
| `STATUS.md` | Actualizado (este archivo) |
| `CHANGELOG.md` | Actualizado (v1.65.3 hotfix, 2026-05-02) |
| `RESUMEN_INSTITUCIONAL.md` | Completo |
| `agent-bootstrap/PHASES.md` | Creado |

---

## Próximo paso recomendado

**v1.65.1 completada y mergeada a develop (2026-05-01).** Cola priorizada con 4 pendientes:

1. **v1.66.0** — Dashboard ejecutivo financiero + reubicación de RRHH (Designer primero, ~1 día)
2. **v1.63.0** — Percepciones de compra (Designer primero, ~2 días) — arranca bloque de impuestos
3. **v1.63.1** — Percepciones en NC de proveedor (sin Designer, ~1 día)
4. **v1.64.0** — DDJJ + imputación de anticipos (Designer primero, ~2 días) — cierra bloque

**Cadena LISCOM↔labit:** completada en labit (v1.46.0, v1.47.0, v1.48.5, v1.51.0, v1.53.0).
Pendientes en repo `interfases` (Django): v1.48.0, v1.49.0, v1.50.0, v1.52.0.

**Para ejecutar el hotfix (próximo paso recomendado):**
```
Lee .agents/AgenteProgramador/AGENTE_WORKFLOW.md y ejecutá el ciclo completo.
```

**Para arrancar el Designer en paralelo** (v1.66.0):
```
Lee .agents/AgenteDesigner/AGENTE_DESIGNER.md y diseñá la UI de v1.66.0.
Leé agent-bootstrap/handoffs/v1.66.0-pm-to-dev.md sección "Diseño pendiente del Designer"
para el brief completo. El doc de diseño debe quedar en
docs/design/v1.66.0-dashboard-financiero-rrhh.md.
```

**Para nueva sesión PM** (cuando surjan ideas nuevas):
```
Lee .agents/AgentePM/AGENTE_PM.md y arrancá una sesión de planificación.
```

---

> Este archivo se actualiza automáticamente al completar una versión o al iniciar una sesión de CEO.
