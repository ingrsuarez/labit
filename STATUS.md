# STATUS — Labit

> Estado actual del proyecto y del sistema de agentes.
> Última actualización: 2026-05-14 (**develop**: hotfix **v1.98.2** ingesta API LISCOM sin dedup por mensaje HL7; **release master**: **v1.98.1** planilla pendientes clínico+vet y sidebar; **v1.97.0** Santa Cruz FTP)

---

## Estado general

| Campo | Valor |
|---|---|
| **Versión actual (línea v1.x)** | **master**: **v1.98.1**; **develop**: incluye hotfix **v1.98.2** (ingesta API); tags **v1.98.1**, **v1.97.1**, **v1.97.0** |
| **Última en master (releases v1.x previos)** | **v1.98.1** — Planilla pendientes clínico+vet, sidebar; **v1.97.1** — scroll con fragmento; **v1.97.0** — Santa Cruz FTP |
| **Última completada (cola agente)** | **v1.97.1** — Scroll al bloque de resultados tras POST en show clínico/vet (`withFragment` + `vet-admission-results`); tests `RemoveLeafAdmissionDeterminationTest` |
| **Hotfix aplicado** | **v1.98.2** (2026-05-14): ingesta API `POST /api/v1/results/batch` sin dedup por mensaje HL7; idempotencia solo por `batch_id`. Además: 2026-05-09 vet doble submit, botón Eliminar protocolo; **sesión por inactividad** (`SESSION_IDLE_TIMEOUT_MINUTES`, `users.last_activity_at`, middleware `enforce.idle`) |
| **Referencia acceso/roles (v2.x)** | **v2.4.1** — Hotfix redirect loop lab + condición Mi Portal |
| **En proceso** | — |
| **Próxima recomendada** | Por orden: **v1.90.0** (residual en carpeta) → **v1.94.0** Factura B sin cliente → **v1.98.0** planilla pendientes |
| **Pendientes en cola** | 3 con prefijo `v` (**v1.90.0** residual + **v1.94.0** + **v1.98.0**) + 2 diseños/prompts asociados (`DISEÑO_v1.98.0-*`, `DISEÑO_v1.55.0-*`) |
| **Completadas** | 156 |

---

## Cola de prompts

### Pendientes — próximo por orden de versión (`ls pendientes | sort | grep '^v'`)

- `v1.90.0-notas-credito-manuales-independientes.md` — residual en carpeta (verificar si ya aplicado en producto).
- `v1.94.0-factura-b-sin-cliente-fv-create.md` — Factura B manual sin alta de cliente (snapshot en FC).
- `v1.98.0-planilla-resultados-pendientes-lab-clinico.md` — Planilla global resultados pendientes lab clínico (con diseño `DISEÑO_v1.98.0-planilla-resultados-pendientes-clinico.md`).

Archivos adicionales en `pendientes/` (no empiezan con `v`): `DISEÑO_v1.55.0-buscador-unificado-fc.md`, `DISEÑO_v1.98.0-planilla-resultados-pendientes-clinico.md`.

> v1.76.2 completado 2026-05-07 (hotfix: fix ingesta OUT_OF_BRANCH con key global — skip branch check en `ApiResultIngestionService`). v1.76.1 completado 2026-05-07 (hotfix: API key global sin sede para LISCOM). v1.76.0 completado 2026-05-07 (marca de ratificación en determinaciones). hotfix `pdf-mail-jerarquia` completado 2026-05-08 (fix PDF por mail: jerarquía padre-hijo idéntica al PDF directo). hotfix `pdf-filename-paciente-dni-fecha` completado 2026-05-08 (nombre de archivo PDF: nombre_paciente-dni-fecha.pdf en email y descarga manual — lab clínico, vet y muestras). hotfix `a25-mappings-buscador` completado 2026-05-08 (combobox Alpine.js con búsqueda client-side en create y edit de equivalencias A25; fix route model binding `{mapping}`). hotfix `a25-multi-test-mapping` completado 2026-05-09 (tabla pivot `a25_analyte_mapping_tests`; múltiples determinaciones Labit por equivalencia A25; parser aplica resultado a todas las mapeadas).

### En proceso (0)

_Sin prompts en ejecución._

### Completados (111+)

| Versión | Nombre | Fecha | Tag |
|---|---|---|---|
| v1.97.1 | Scroll al bloque resultados tras POST en show protocolo clínico/vet (`withFragment`, `vet-admission-results`) | 2026-05-14 | (tag al release) |
| v1.97.0 | Santa Cruz O&G: FTP (`SantaCruzFtpService`), parser XML, mapeos prestación↔test, importación admisiones, vistas sync + índice mapeos, permiso `santacruz.import` | 2026-05-13 | v1.97.0 |
| v1.96.0 | Quitar determinación hoja sin resultado (lab + vet): `ClinicalAdmissionTestHierarchy`, `removeTest` hoja sin cascada indebida en vet; tests `RemoveLeafAdmissionDeterminationTest` | 2026-05-12 | v1.96.0 |
| v1.95.0 | Encabezado sticky detalle protocolo (`md:top-20`); tests `ProtocolPendingNavigationTest` regresión layout + HTML sticky | 2026-05-12 | (tag al release) |
| v1.93.0 | Navegación siguiente/anterior protocolo pendiente en validación (clínico/vet/muestras); tests `ProtocolPendingNavigationTest` | 2026-05-12 | v1.93.0 |
| v1.92.0 | Dashboard operativo del laboratorio: KPIs, barras por estado/tipo/sede, alerta atrasados, links filtrados | 2026-05-12 | v1.92.0 |
| v1.91.0 | Carga de resultados desde planilla de trabajo: celdas editables inline, batch save, clinico + muestras | 2026-05-12 | v1.91.0 |
| v1.90.0 | Notas de crédito manuales independientes: carga de NC sin factura asociada, PdV manual, sin AFIP | 2026-05-12 | v1.90.0 |
| v1.89.0 | Preservar estado formulario recibo de cobro ante errores (catch Throwable → redirect, old() → Alpine) | 2026-05-11 | v1.89.0 |
| v1.88.0 | Auditoría completa en protocolo veterinario (10 logAudit + x-audit-history en show) | 2026-05-11 | v1.88.0 |
| v1.87.0 | Filtro por sede en planilla de trabajo (preview + PDF) | 2026-05-10 | v1.87.0 |
| v1.86.0 | Envío masivo protocolos clínicos por email (un correo, N PDFs) | 2026-05-10 | v1.86.0 |
| v1.85.0 | Protocolo lab clínico: ícono config práctica solo admin + `quickUpdate` restringido (403) | 2026-05-09 | v1.85.0 |
| v1.77.0 | Vista recepción-lab: determinaciones leaf, CRUD restringido, eliminar protocolo si todo pendiente (clínico/vet/muestras) | 2026-05-10 | v1.77.0 |
| v1.84.0 | Etiquetas: selección de materiales (modal Zebra + vía navegador `?materials=`); `labelData` muestras con `labels[]`; trait `FiltersLabelsByMaterialsQuery` | 2026-05-09 | v1.84.0 |
| v1.83.0 | Conciliación bancaria: `PayrollPayment` en `BankReconciliationService`, filtro Haberes (PP) en panel, categoría `haberes` en extractos, vista show pago | 2026-05-09 | v1.83.0 |
| v1.82.0 | PayrollPayment: agrupa N liquidaciones en 1 pago bancario + asiento Db 2.1.07 Sueldos a Pagar / Cr banco. Controller, vistas, permisos, 10 tests. | 2026-05-09 | v1.82.0 |
| v1.81.0 | Estados visuales en planilla de trabajo: ✓ teal (pendiente), valor (con resultado), tacha diagonal (no pedida) — clínico y muestras | 2026-05-09 | v1.81.0 |
| v1.80.0 | Nombre corto / sigla en clientes y obras sociales (`short_name`, `displayName()`, vistas + PDFs + emails) | 2026-05-09 | v1.80.0 |
| v1.76.2 | Fix ingesta: key global omite validación `PROTOCOL_OUT_OF_BRANCH` — `ApiResultIngestionService` skip branch check cuando `client->isGlobal()` | 2026-05-07 | v1.76.2 |
| v1.76.1 | API key global sin sede para LISCOM (`lab_branch_id` nullable, `isGlobal()`, filtro condicional en `ProtocolLookupService` y `ProtocolController`, UI campo sede opcional) | 2026-05-07 | v1.76.1 |
| v1.76.0 | Marcar determinaciones ratificadas (lab/vet/sample): `is_ratified` + `ratified_at` + `ratified_by`, UI checkbox editable post-validación, marca `*` y leyenda en PDF informe + email | 2026-05-07 | v1.76.0 |
| v1.74.0 | Envío masivo de protocolos de muestras por email (lote, agrupación por cliente, `sent_at`) | 2026-05-06 | v1.74.0 |
| v1.64.0 | Declaraciones de impuestos e imputación de anticipos (Tax, TaxReturn, balances) | 2026-05-06 | v1.64.0 |
| v1.73.0 | Estado "enviado" en protocolos de muestras (`sent_at`, informe email/PDF) | 2026-05-07 | v1.73.0 |
| v1.75.1 | Hotfix: otros valores de referencia visibles en PDF (clínico, vet, muestras) | 2026-05-06 | v1.75.1 |
| v1.75.0 | PDF de protocolos sin observaciones internas (lab, vet, muestras) | 2026-05-06 | v1.75.0 |
| v1.63.1 | Percepciones en NC de proveedor (pivote + asientos + balances) | 2026-05-06 | v1.63.1 |
| v1.72.0 | Editar protocolo veterinario con auditoría de cambios | 2026-05-05 | v1.72.0 |
| v1.71.0 | Fix búsqueda y selección de determinaciones hijas en protocolos | 2026-05-05 | v1.71.0 |
| v1.70.0 | Sección Pacientes en lab clínico (lista + sidebar) | 2026-05-05 | v1.70.0 |
| v1.69.0 | Estado protocolo clínico: columna estado, filtro y sync automático | 2026-05-05 | v1.69.0 |
| v1.67.4 | Hotfix: estado validacion vet + in_progress por LISCOM | 2026-05-04 | v1.67.4 |
| v1.67.3 | Hotfix: orden determinaciones en email veterinario | 2026-05-04 | v1.67.3 |
| v1.67.2 | Hotfix: columna birth de pacientes (timestamp → date) | 2026-05-04 | v1.67.2 |
| v1.67.1 | Hotfix: billing batch al cambiar empresa (MethodNotAllowed) | 2026-05-04 | v1.67.1 |
| v1.67.0 | API: catálogo de tests/determinaciones para LISCOM | 2026-05-04 | v1.67.0 |
| v1.66.5 | UX: listado de Facturas de Venta más compacto y fila clickeable | 2026-05-03 | v1.66.5 |
| v1.66.4 | Hotfix: completar min-w-0 en padre del main del admin-layout | 2026-05-03 | v1.66.4 |
| v1.66.3 | Hotfix: scroll horizontal en Facturas de Venta (listado y formularios) | 2026-05-03 | v1.66.3 |
| v1.66.2 | Hotfix: scroll horizontal en formularios de Factura de Compra y Remito | 2026-05-03 | v1.66.2 |
| v1.66.1 | Hotfix: barras del dashboard financiero invisibles (Tailwind purge) | 2026-05-03 | v1.66.1 |
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

**v1.97.0** mergeada a **master** (2026-05-13); tag **v1.97.0**. Pendientes en cola con prefijo `v`: **v1.90.0** (residual) y **v1.94.0** (Factura B); diseño **v1.55.0** (buscador FC) en `pendientes/`.

**Cadena LISCOM↔labit:** completada en labit (v1.46.0, v1.47.0, v1.48.5, v1.51.0, v1.53.0).
Pendientes en repo `interfases` (Django): v1.48.0, v1.49.0, v1.50.0, v1.52.0.

**Cadena LISCOM (v1.76.1 + v1.76.2 hotfixes):** Deploy ambas versiones en producción. Luego: (1) vaciar `lab_branch_id` del ApiClient de LISCOM, (2) re-encolar dispatches `dead_letter` con `rejection_reason='PROTOCOL_OUT_OF_BRANCH'` desde LISCOM, (3) reprocesar protocolo C2605070011 (ResultMessage id 148).

**Para nueva sesión PM** (cuando surjan ideas nuevas):
```
Lee .agents/AgentePM/AGENTE_PM.md y arrancá una sesión de planificación.
```

---

> Este archivo se actualiza automáticamente al completar una versión o al iniciar una sesión de CEO.
