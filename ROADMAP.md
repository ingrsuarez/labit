# ROADMAP — Labit

> Versiones planificadas, en progreso y completadas del proyecto.
> Última actualización: 2026-05-12 (v1.95.0 mergeada a develop: encabezado sticky detalle protocolo)

---

## Convenciones

- **vX.0.0** — Major: cambio significativo de arquitectura o funcionalidad
- **v0.X.0** — Minor: nueva feature o módulo
- **v0.0.X** — Patch: fix, mejora menor, documentación

---

## Completadas

| Versión | Nombre | Fecha | Notas |
|---|---|---|---|
| v1.0.0 | Línea base del proyecto | 2026-03-14 | Documentación del estado existente |
| v1.0.1 | README del proyecto | 2026-03-14 | README.md completo |
| v1.1.0 | Normalización de line endings | 2026-03-16 | .gitattributes + .editorconfig + renormalización |
| v1.2.0 | Infraestructura AFIP | 2026-03-14 | AfipService, certificados, WSAA, WSFEv1 |
| v1.3.0 | Facturación electrónica WSFEv1 | 2026-03-14 | Autorización automática, CAE, PDF con QR |
| v1.3.1 | Fix AFIP CondicionIVAReceptorId + ImpTotal | 2026-03-15 | RG 5616, cálculo correcto de totales |
| v1.4.0 | Notas de crédito electrónicas | 2026-03-17 | NC A/B/C con AFIP, comprobante asociado |
| v1.4.1 | Fix guardado de resultados de protocolo | 2026-03-17 | Formularios anidados → submitAction() |
| v1.5.1 | Roles y permisos del módulo de laboratorio | 2026-03-17 | 3 roles, 15 permisos, middleware + @can |
| v1.5.2 | Roles y permisos del módulo de muestras | 2026-03-17 | Extiende roles con permisos de muestras |
| v1.5.3 | Seeder de jerarquía padre-hijo de prácticas | 2026-03-20 | 10 relaciones para 3 padres, 26 tests pendientes de crear |
| v2.0.0 | Infraestructura multi-empresa | 2026-03-21 | Modelo Company, pivot, middleware, CRUD, selector en header |
| v2.1.0 | Ventas y cobros multi-empresa | 2026-03-22 | company_id en ventas, filtrado por empresa, AfipService multi-empresa |
| v2.2.0 | Compras y pagos multi-empresa | 2026-03-22 | company_id en compras, filtrado por empresa |
| v2.2.1 | Fix columnas vacías en vista de protocolo | 2026-03-22 | Reemplazar template x-if por x-show en tabla de determinaciones |
| v2.3.0 | RRHH multi-empresa | 2026-03-22 | company_id en employees, payrolls, leaves, documents |
| v1.5.4 | Tests faltantes y jerarquía padre-hijo completa | 2026-03-22 | 27 tests hijos, 37 relaciones, badge padre, fix cascada |
| v1.6.0 | Formato tabular en PDFs de informes | 2026-03-23 | Tabla con Análisis/Resultado/Unidad/Ref, padres bold, hijos indentados |
| v1.6.1 | Filtrar nomencladores de dropdowns y crear Particular | 2026-03-23 | Seeder Particular, filtrar type!=nomenclador en 5 controladores |
| v1.7.0 | Cobro a particulares y control de deuda | 2026-03-23 | Migración payment fields, cobro parcial/total, deudores, 3 medios de pago |
| v1.8.0 | Búsqueda activa en protocolos de muestras | 2026-03-23 | Filtrado Alpine.js client-side, sin paginación, búsqueda instantánea |
| v1.9.0 | Firma digital de validadores y nombre automático de PDF | 2026-03-24 | Upload firma en perfil, firma en PDF, nombre descriptivo de archivo |
| v1.10.0 | Importación de nomencladores desde Excel | 2026-03-24 | 8 nomencladores base desde .xlsx, 297 tests nuevos, 8826 prácticas |
| v1.11.0 | Importación de obras sociales desde Excel | 2026-03-24 | Seeder obras sociales desde .xlsx, asociación a nomencladores base |
| v1.11.1 | Configuración de correos del laboratorio | 2026-03-25 | LabSetting key-value, 2 cuentas, firma HTML |
| v1.11.2 | Buscador en dropdown de obra social | 2026-03-24 | Combobox Alpine.js en admisiones y pacientes |
| v1.12.0 | PDF protocolos lab clínico + envío email | 2026-03-25 | PDF tabular, firma validador, envío con LabSetting |
| v1.13.0 | Nomenclador en tiempo real (sin duplicación) | 2026-03-25 | Precio = nbu_units base × nbu_value OS, sin copiar prácticas |
| v1.13.1 | Fix searchTests usa nomenclador base | 2026-03-25 | Hotfix: searchTests no usaba fallback a nomenclador base |
| v1.14.0 | Precios en protocolos de aguas y alimentos | 2026-03-25 | categories JSON en tests, discount_percent en customers, precio en sample_determinations |
| v1.14.1 | Otros valores de referencia en determinaciones | 2026-03-25 | Campo other_reference para valores no numéricos, fallback en PDFs |
| v1.15.0 | Sub-padres y orden fijo de determinaciones | 2026-03-25 | Jerarquía 3 niveles, sort_order, PDFs con indentación progresiva |
| v2.4.0 | Control de acceso por rol y redirección inteligente | 2026-03-25 | Permisos de sección, sidebar protegido, redirección por rol |
| v2.4.1 | Hotfix redirect loop lab + condición Mi Portal | 2026-03-26 | Fix redirect loop CheckSystemAccess para lab roles, Mi Portal solo con employee |
| v1.16.0 | Planillas de trabajo diario del laboratorio | 2026-03-26 | Worksheets con CRUD, filtros, PDF landscape |
| v2.5.0 | Auditoría infraestructura + módulo clínico | 2026-03-26 | audit_logs polimórfica, trait Auditable, auth/patient/admission/sample |
| v2.7.0 | Auditoría vista centralizada | 2026-03-26 | AuditController, tabla filtrable, badges, link sidebar |
| v1.17.0 | Fix impresión de etiquetas Zebra | 2026-03-26 | Parseo JSON, Content-Type, auto-detect HTTP/HTTPS |
| v1.18.0 | Etiquetas de protocolo para laboratorio clínico | 2026-03-27 | Etiquetas imprimibles desde admisiones del lab clínico |
| v1.19.0 | Consulta de padrón AFIP por CUIT | 2026-03-28 | ws_sr_padron_a5, autocompletado formularios, lock IVA |
| v1.19.1 | Fix deselección de padre en determinaciones | 2026-03-29 | Checkboxes en vez de select multiple para padres |
| v1.19.2 | Fix validación y PDF para resultados con valor cero | 2026-03-29 | Validación acepta "0", PDF muestra cero correctamente |
| v1.20.0 | Infraestructura veterinaria base | 2026-03-29 | Modelos Animal, Species, Breed, VetAdmission base |
| v1.21.0 | Tipificación de Customers + CRUD de Veterinarios | 2026-03-29 | customer_type, CRUD veterinarios referentes |
| v1.22.0 | Valores de referencia por especie | 2026-03-29 | Ref values con species_id, fallback a genérico |
| v1.23.0 | Nuevo formato de numeración de protocolos | 2026-03-29 | Formato configurable por módulo, secuencia anual |
| v1.24.0 | Protocolo veterinario (VetAdmission) | 2026-03-29 | Admisión vet completa, animal+dueño+veterinario |
| v1.25.0 | PDF resultados veterinarios + envío email | 2026-03-29 | PDF vet con datos animal/especie, envío email |
| v1.26.0 | Búsqueda de protocolos por dueño/animal | 2026-03-29 | Filtros por dueño, animal, especie en listado vet |
| v1.30.1 | Reorganización del sidebar del laboratorio | 2026-03-29 | Módulos arriba, herramientas abajo, Deudores→Saldos |
| v1.18.1 | Fix etiquetas lab clínico: una por material | 2026-03-29 | 1 etiqueta por tubo/material, botón en index |
| v1.30.2 | Sede por defecto del usuario y selector en header | 2026-03-30 | default_lab_branch_id, helper, selector header |
| v1.30.3 | Fix protocolos sin sede: visibilidad y asignación masiva | 2026-03-30 | Filtro y asignación masiva de sede a protocolos existentes |
| v1.31.0 | Control de facturación de protocolos | 2026-03-30 | Tabla pivot invoice_protocols, vista sin facturar, botón Facturar, badge sidebar |
| v1.31.1 | Facturación masiva por OS, aguas y veterinarias | 2026-03-30 | Batch invoice, preview, checkboxes, agrupación por OS/cliente |
| v1.32.0 | Buscador inteligente de insumos en facturas de compra | 2026-03-31 | Combobox autocompletado, reemplaza select+input, Tab flow |
| v1.32.1 | Fix layout ítems factura de compra | 2026-03-31 | Badge inline, lote/fecha horizontal, fix create y edit |
| v1.28.1 | Fix QR scanner captura nativa | 2026-03-31 | Reemplazar cámara navegador por capture=environment |
| v1.32.4 | Editar y eliminar remitos con sincronización de stock | 2026-04-04 | Editar/eliminar solo sin FC; revertir/sincronizar stock |
| v1.35.0 | Cuenta corriente de proveedores | 2026-04-04 | SupplierStatementController, HTML+PDF, saldo acumulado |
| v3.3.0 | Asientos automáticos desde transacciones | 2026-04-04 | AccountingEntryService, hooks FV/NC/RC/FC/OP, widget en vistas show |
| v1.36.0 | Múltiples remitos en factura de compra | 2026-04-05 | Tabla pivote `delivery_note_purchase_invoice`, formularios create/edit FC, tests |
| v1.37.0 | Stock por sede: modelo, migración y movimientos | 2026-04-05 | `supply_lab_branch_stock`, `LabBranchResolver`, `SupplyStockService`, backfill |
| v1.38.0 | Stock por sede: compras, remitos, FC y vistas | 2026-04-05 | `lab_branch_id` en OC/remitos/FC/movimientos; vistas insumos por sede; tests |
| v1.38.2 | Hotfix ParseError recibo de cobro create | 2026-04-05 | JSON de facturas por cliente en `CollectionReceiptController@create`; `company_id` en query; Blade sin `@json` anidado con arrow functions |
| v1.39.0 | Recibos de cobro: múltiples medios y e-cheq (cartera) | 2026-04-05 | Tabla `collection_receipt_payments`; UI create/edit/show; `fromCollectionReceipt` multiparte; migración legado; tests |
| v1.39.1 | OP a proveedor con e-cheqs en cartera (endoso) | 2026-04-05 | `payment_order_id` en líneas e-cheq; reserva en borrador OP; UI cartera; `fromPaymentOrder` multiparte; tests |
| v1.40.0 | Recibos de cobro: retenciones sufridas (GA, IVA, SUSS, IIBB) | 2026-04-06 | `collection_receipt_withholdings`; UI create/edit/show; `fromCollectionReceipt` + cuentas 1.1.05–08; Libro IVA preview retenciones IVA; tests |
| v1.41.0 | Recibos de cobro: PDF para cliente | 2026-04-06 | Ruta `collection-receipts.pdf`, DomPDF, plantilla A4, botón en show; tests |
| v1.41.1 | Hotfix Libro IVA al cambiar de empresa | 2026-04-07 | `switchCompany` no hace GET a `libro-iva/preview|download`; flash en índice Libro IVA |
| v1.41.2 | Recibos de cobro: logo en PDF + PDF en listado | 2026-04-07 | `logo_ipac.png` en cabecera PDF; botón PDF por fila en `index` |
| v1.42.0 | Servicios de compra: catálogo, FC y estadísticas | 2026-04-07 | Categorías/servicios por empresa; `purchase_service_id` en ítems FC; reporte por categoría/servicio; permisos en seeder |
| v1.42.1 | Orden determinaciones vet: show + PDF unificado | 2026-04-06 | `VetAdmissionTestDisplayOrder`, jerarquía + `sort_order`; vista carga/validación alineada al informe |
| v1.43.0 | Precios protocolo veterinario: NBU veterinaria × NBU práctica | 2026-04-12 | `customers.veterinary_nbu_value`; `searchTests` + `store` server-side; UI clientes y alta vet; tests Feature |
| v1.44.0 | Nomenclador veterinario (hub + listado filtrado) | 2026-04-12 | Ruta `lab/veterinario/nomenclador`; `TestController::indexVeterinary`; reusa `test/index`; redirección `_context=vet_nomenclator` |
| v1.45.0 | Eliminar cliente: protocolos y facturación | 2026-04-13 | `CustomerController::destroy` agrupa bloqueos; botón eliminar + `CustomerDestroyTest` |
| v1.46.0 | API pública con API key + módulo admin de keys | 2026-04-18 | Modelo `ApiClient` (1 key/sede, hash SHA-256, prefix `labit_`), middleware `auth.api_key` + canal log `api`, endpoint `GET /api/v1/ping`, CRUD `/admin/api-clients` con modal "key una sola vez" + regenerate, permiso `api-clients.manage`. 13 tests Feature verde. DD-005 en BLUEPRINT |
| v1.47.0 | API pública: protocolos unificados (clinical/sample/vet) + PII gating | 2026-04-18 | `GET /api/v1/protocols`, `/by-barcode/{code}`, `/{type}/{id}`. `ProtocolResource` polimórfico + `DeterminationResource` con normalización de status (pending/in_progress/completed/validated). Enum `ProtocolType` (prefijos C/A/V). Service `ProtocolLookupService` con merge en PHP + filtro por sede. PII (DNI/CUIT) gateado por `api_clients.patient_data_level` (default `minimal`). 15 tests Feature verde. Doc en `docs/api/v1/protocols.md`. DD-006 en BLUEPRINT |
| v1.48.5 | Formato extendido de barcode (`{protocol_number}^{material_abbreviation}`) | 2026-04-18 | `BarcodeFormatService::forLabel()`. Clínico (una etiqueta por material) + Muestras (primer material, Opción 3.A). VetAdmission sin etiquetas → sin cambios. Tensión abierta: ZPL Zebra usa `protocol_number` directo → hotfix v1.48.5.1 pendiente. 5 tests Feature verde. |
| hotfix | Buscador (combobox Alpine.js) en equivalencias A25 | 2026-05-08 | Reemplaza `<select>` nativo por combobox Alpine.js con filtrado client-side en create y edit de A25 mappings. Fix route model binding: `.parameters(['a25-mappings' => 'mapping'])` en web.php para que `{mapping}` coincida con el controlador. |
| v1.67.3 | Hotfix: orden determinaciones en email veterinario | 2026-05-04 | Eager load de `vetTests` en `VetAdmissionResultMail` sin filtro de validadas, alineado a `downloadPdf`/`viewPdf`. Fix del cálculo del validador con filtro de colección. |
| v1.67.2 | Hotfix: columna birth de pacientes (timestamp → date) | 2026-05-04 | Columna `patients.birth` de TIMESTAMP a DATE para soportar fechas pre-1970. Limpia línea duplicada en PatientController. |
| v1.67.1 | Hotfix billing batch al cambiar empresa | 2026-05-04 | `switchCompany` redirige a `billing.uninvoiced` en vez de `redirect()->back()` cuando URL es `/billing/batch-preview` o `/billing/batch-invoice`. Mismo patrón que v1.41.1 (Libro IVA). |
| v1.67.0 | API: catálogo de tests/determinaciones para LISCOM | 2026-05-04 | `GET /api/v1/tests?search=...&category=...`. Búsqueda por name/code, filtro por categoría, flags is_parent/is_child, material. 12 tests Feature verde. Complementa v1.47.0 para que LISCOM configure EquipmentTestMapping (v1.49.0). |
| v1.77.0 | Vista recepción-lab: leaf + CRUD restringido + eliminar protocolo pendiente (clínico/vet/muestras) | 2026-05-10 | Rutas `destroy`, `removeTest`/`removeDetermination` recepción-lab, vistas `isRecepcionLab`, permisos seeder |
| v1.86.0 | Envío masivo protocolos clínicos validados: un correo con N PDFs (`AdmissionBatchMail`, `batch-email`) | 2026-05-10 | Índice admisiones: selección + FAB + modal; tests `AdmissionBatchEmailTest` |
| v1.93.0 | Navegación siguiente/anterior protocolo pendiente en validación (clínico/vet/muestras): filtros vivos, `protocol_number`, rutas `next-pending`/`previous-pending`, concern `AppliesProtocolIndexFilters` | 2026-05-12 | Tests `ProtocolPendingNavigationTest` |

---

## En progreso

| Versión | Nombre | Estado | Rama |
|---|---|---|---|

---

## Planificado

### Cadena de integración LISCOM (servidor HL7 local) ↔ Labit (cloud)

LISCOM vive en `c:\wamp64\www\interfases` (Django + Channels + HL7 MLLP). El flujo objetivo:
labit crea protocolos con barcodes → equipos escanean y consultan a liscom → liscom consulta
a labit por API y cachea localmente → equipo procesa → liscom recibe HL7 con resultados →
operador revisa → liscom envía a labit por API (con cola de reintentos para tolerar caídas
de internet).

| Versión | Nombre | Estado | Prompt | Notas |
|---|---|---|---|---|
| v1.46.0 | API pública: auth con API key + admin de keys | ✅ Completada (2026-04-18) | `completados/v1.46.0-api-publica-fundacion.md` | Tag `v1.46.0`. Cimiento de la cadena LISCOM. Una key por sede + log canal `api`. |
| v1.47.0 | Endpoints GET de protocolos unificados (clinical + sample + vet) | ✅ Completada (2026-04-18) | `completados/v1.47.0-protocolos-api-endpoints.md` | Tag `v1.47.0`. Resource polimórfico + filtrado automático por sede + PII gating por nivel de la key (default minimal, sin DNI). Soporta sync incremental con `updated_since`. |
| v1.48.5 | Formato extendido de barcode: `protocol_number^material_abbr` | ✅ Completada (2026-04-18) | `completados/v1.48.5-barcode-formato-extendido.md` | Tag `v1.48.5`. `BarcodeFormatService::forLabel()`. Clínico (etiqueta por material) + Muestras (Opción 3.A, primer material). Tensión abierta: ZPL Zebra usa `protocol_number` directo (pendiente hotfix v1.48.5.1). |
| v1.49.0 | Mapeo de códigos equipo↔labit + respuesta HL7 al scan en liscom | Pendiente ⚠️ otro repo | `pendientes/v1.49.0-liscom-mapeo-codigos-respuesta-scan.md` | **Se ejecuta en `c:\wamp64\www\interfases` (Django).** Modelo `EquipmentTestMapping` + UI manual + parser de barcode con material + builders DSR^Q03 / ORL^O22 + handler de QRY^Q11 / OUL^R22 en `ConnectionManager._handle_message`. |
| v1.50.0 | Recepción HL7 ORU/OUL + bandeja de revisión humana en liscom | Pendiente ⚠️ otro repo | `interfases/agent-bootstrap/prompts/pendientes/v1.50.0-liscom-recepcion-resultados-bandeja.md` | **Se ejecuta en `c:\wamp64\www\interfases` (Django).** Modelos `ResultMessage` + `Result`, parser HL7 extendido, `ResultIntakeService` con idempotencia, 3 pantallas web con doc de diseño en `interfases/docs/design/v1.50.0-...`, comando `reprocess_result`. NO envía a labit (eso es v1.52.0). |
| v1.51.0 | Endpoint POST `/api/v1/results/batch` con idempotencia + respeto a validación bioquímico | ✅ Completada (2026-04-18) | `completados/v1.51.0-api-ingesta-resultados-batch.md` | Tag `v1.51.0`. Modelos `ResultBatch`+`ResultIngestion`, `ApiResultIngestionService` con regla crítica `ALREADY_VALIDATED` (no sobrescribir si `is_validated=true`), lookup por prefijo `ProtocolType` enum (`C`/`A`/`V`), idempotencia doble (batch_id + hl7_control_id). 15 tests Feature verde. Doc en `docs/api/v1/results.md`. DD-007 en BLUEPRINT. |
| v1.52.0 | Cliente outbound LISCOM → labit + cola persistente + dashboard | Pendiente ⚠️ otro repo | `interfases/agent-bootstrap/prompts/pendientes/v1.52.0-liscom-cliente-cola-outbound.md` | **Se ejecuta en `c:\wamp64\www\interfases` (Django).** Modelos `OutboundDispatch` + `OutboundAttempt`. Backoff exponencial corto (1m/5m/15m/1h/6h, max 5). Mapeo diferenciado de respuestas (`ALREADY_VALIDATED` → blocked terminal, `PROTOCOL_NOT_FOUND` → auto-sync + 1 reintento). Hook post-aprobación + cron de respaldo. Dashboard `/outbound/` con 4 pantallas (doc de diseño en `interfases/docs/design/v1.52.0-...`). Designer ya completado. Cierra la cadena de integración. |
| v1.53.0 | Dashboard de monitoreo de la API en labit (ingesta de resultados) | ✅ Completada (2026-04-18) | `completados/v1.53.0-api-monitor-dashboard.md` | Tag `v1.53.0`. Livewire 3 (`Dashboard`, `BatchesList`, `BatchDetail`, `IngestionsList`, `IngestionDetail`), `ApiMonitorService` (counters materializados), banner ALREADY_VALIDATED, salud de sedes, `api:cleanup` con retención configurable, 20 tests Feature verde. DD-008 en BLUEPRINT. Runbook en `docs/operations/api-monitor.md`. |

**Tensión del barcode (RESUELTA, decisión PM 2026-04-18):** Se eligió **Opción B** —
cambiar el formato del barcode en labit a `{protocol_number}^{material_abbreviation}`
(ej: `C-2026-001234^EDTA`). Esto se ejecuta en **v1.48.5** antes de v1.49.0 para que
liscom pueda filtrar respuestas HL7 por material. Separator `^` por compatibilidad
con CODE_128 y por ser separator estándar de componentes en HL7. Si el material es
nulo (caso defensivo), fallback al formato actual `{protocol_number}` solo.

> Nota: Los prompts v1.35.2, v1.35.3, v1.36.1 y v3.4.0 figuran en `agent-bootstrap/prompts/completados/`; se retiraron de esta tabla para evitar duplicar el estado.


---

## Planificado — Próximas versiones

| Versión | Nombre | Estado | Prompt |
|---|---|---|---|
| v1.95.0 | Encabezado sticky detalle protocolo (clínico, vet, muestras); `md:top-20`; tests regresión layout | ✅ Completada (2026-05-12) | `completados/v1.95.0-encabezado-sticky-detalle-protocolos.md` |
| v1.93.0 | Navegación “Siguiente protocolo” en validación: filtros vivos del listado, no validado ni enviado, orden ascendente `protocol_number` (clínico + vet + muestras) | ✅ Completada (2026-05-12) | `completados/v1.93.0-navegacion-siguiente-protocolo-validacion.md` |
| v1.92.0 | Dashboard operativo del laboratorio: KPIs, barras por estado/tipo/sede, alerta atrasados, links filtrados | Pendiente | `pendientes/v1.92.0-dashboard-operativo-laboratorio.md` |
| v1.88.0 | Auditoría completa en protocolo veterinario: 10 `logAudit()` faltantes + `<x-audit-history>` en vista show | Pendiente | `pendientes/v1.88.0-auditoria-completa-protocolo-veterinario.md` |
| v1.89.0 | Preservar estado del formulario de recibo de cobro ante errores (Alpine.js + `old()` + catch graceful) | Pendiente | `pendientes/v1.89.0-preservar-estado-formulario-recibo-cobro.md` |
| v1.90.0 | Notas de crédito manuales independientes: carga de NC sin factura asociada, PdV manual, sin AFIP | Pendiente | `pendientes/v1.90.0-notas-credito-manuales-independientes.md` |
| v1.86.0 | Envío masivo protocolos clínicos validados: un correo con N PDFs; destino manual u atajos obra social / mismo paciente | ✅ Completada (2026-05-10) | `completados/v1.86.0-envio-masivo-protocolos-clinicos-email.md` |
| v1.85.0 | Protocolo lab clínico: ícono neutro de config. de práctica + visible solo admin; `quickUpdate` restringido a admin (403) | ✅ Completada (2026-05-09) | `completados/v1.85.0-protocolo-lab-icono-config-admin.md` |
| v1.80.0 | Nombre corto / sigla en clientes y obras sociales (`short_name`, `displayName()`, vistas + PDFs + emails) | ✅ Completada (2026-05-09) | `completados/v1.80.0-nombre-corto-clientes-obras-sociales.md` |
| v1.81.0 | Estados visuales en planilla de trabajo: ✓ pendiente / valor / tacha no pedida | ✅ Completada (2026-05-09) | `completados/v1.81.0-planilla-trabajo-estados-celda.md` |
| v1.82.0 | PayrollPayment: pago de haberes agrupado (N liquidaciones → 1 pago) + asiento contable automático (Db 2.1.07 Sueldos a Pagar / Cr banco) | ✅ Completada (2026-05-09) | `completados/v1.82.0-payroll-payment-pago-haberes-asiento.md` |
| v1.83.0 | Conciliación bancaria de pagos de haberes: `PayrollPayment` como registro reconciliable, filtro "Haberes", sugerencia automática por monto+período | ✅ Completada (2026-05-09) | `completados/v1.83.0-conciliacion-bancaria-pagos-haberes.md` |
| v1.84.0 | Etiquetas: seleccionar materiales antes de imprimir (Zebra + navegador; lab clínico, vet y muestras) | ✅ Completada (2026-05-09) | `completados/v1.84.0-etiquetas-seleccion-materiales-impresion.md` |
| v1.77.0 | Vista recepción-lab: determinaciones leaf + CRUD restringido por estado + eliminar protocolo 100% pendiente | ✅ Completada (en código; tag v1.77.0) | `completados/v1.77.0-vista-recepcion-lab-leaf-crud-restringido.md` |
| v1.76.2 | Fix ingesta: key global omite validación OUT_OF_BRANCH en `ApiResultIngestionService` | ✅ Completada (2026-05-07) | `completados/v1.76.2-labit-ingestion-key-global-out-of-branch.md` |
| v1.76.1 | API key global sin sede para LISCOM — hotfix ORPHAN multi-sede | ✅ Completada (2026-05-07) | `completados/v1.76.1-labit-api-key-global-sin-sede.md` |
| v1.78.0 | Biosystems A25: worklist `import.txt` + import export; equivalencias nombre A25↔Labit; id muestra sin asumir legado=Labit | Pendiente | `pendientes/v1.78.0-a25-biosystems-interfaz-texto-plano.md` |
| v1.76.0 | Marcar determinaciones como ratificadas (valores anormales controlados) | ✅ Completada (2026-05-07) | `completados/v1.76.0-marcar-determinaciones-ratificadas.md` |
| v1.75.0 | PDF de protocolos sin observaciones internas | Pendiente | `pendientes/v1.75.0-pdf-sin-observaciones-internas-protocolos.md` |
| v1.74.0 | Envío masivo de protocolos de muestras por email | Pendiente | `pendientes/v1.74.0-envio-masivo-protocolos-muestras.md` |
| v1.73.0 | Estado "enviado" en protocolos de muestras | ✅ Completada (2026-05-07) | `completados/v1.73.0-estado-enviado-protocolos-muestras.md` |
| v1.70.0 | Sección Pacientes en lab clínico (lista + sidebar) | Pendiente | `pendientes/v1.70.0-seccion-pacientes-lab-clinico.md` |
| v1.69.0 | Estado protocolo clínico: columna, filtro y sync automático | Pendiente | `pendientes/v1.69.0-estado-protocolo-clinico-listado-filtro.md` |
| v1.68.0 | Editar protocolo veterinario con auditoría | Pendiente | `pendientes/v1.68.0-editar-protocolo-veterinario-auditoria.md` |
| v1.67.5 | Fix búsqueda de determinaciones hijas en protocolos | Pendiente (hotfix) | `pendientes/v1.67.5-fix-busqueda-determinaciones-hijas.md` |
| v1.67.6 | Hotfix multi-equipo en batch results: dedup por equipo | ✅ Completada (2026-05-05) | — (ejecutado directo) |
| v1.67.4 | Hotfix estado validación vet + in_progress por LISCOM | ✅ Completada | `completados/v1.67.4-hotfix-estado-validacion-vet-y-liscom-in-progress.md` |
| v1.65.1 | Etiquetas de protocolo para laboratorio veterinario | Pendiente (hotfix) | `pendientes/v1.65.1-etiquetas-lab-veterinario.md` |
|| v1.65.2 | Fix PDF veterinario: excluir no validadas + jerarquía orphans | ✅ Completada (hotfix) | — (ejecutado directo) |
| v1.54.0 | UX combobox insumos: ocultar input al seleccionar | ✅ Completada | `completados/v1.54.0-ux-combobox-insumos-ocultar-input.md` |
| v1.54.1 | Hotfix lote/vencimiento remito: ocultar inputs cuando no trackea lote | ✅ Completada | `completados/v1.54.1-hotfix-lote-vencimiento-remito-xshow.md` |
| v1.55.0 | Factura de compra: buscador unificado insumos + servicios | ✅ Completada | `completados/v1.55.0-fc-buscador-unificado-insumos-servicios.md` |
| v1.56.0 | Botón "Crear Factura de Compra" desde show e index del remito | ✅ Completada | `completados/v1.56.0-boton-crear-fc-desde-remito.md` |
| v1.57.0 | Fix cross-company: remitos visibles en FC sin importar empresa activa | ✅ Completada | `completados/v1.57.0-fix-cross-company-remitos-disponibles-fc.md` |
| v1.58.0 | Modal "Nuevo Proveedor" desde formulario de FC | ✅ Completada | `completados/v1.58.0-modal-nuevo-proveedor-desde-fc.md` |
| v1.58.1 | Modal "Nuevo Proveedor" desde formulario de Remito | ✅ Completada | `completados/v1.58.1-modal-nuevo-proveedor-desde-remito.md` |
| v1.59.0 | Prellenar lote/vencimiento al crear FC desde remito | ✅ Completada | `completados/v1.59.0-prellenar-lote-vencimiento-fc-desde-remito.md` |
| v1.60.0 | Fix: deshabilitar autocomplete del navegador en buscador de insumos del remito | ✅ Completada | `completados/v1.60.0-disable-browser-autocomplete-insumo-remito.md` |
| v1.61.0 | Enter agrega ítem en Factura de Compra (igual que en remito) | ✅ Completada | `completados/v1.61.0-enter-agrega-item-fc.md` |
| v1.62.0 | Unificar insumos: merge A → B con reasignación de referencias | Pendiente | `pendientes/v1.62.0-unificar-insumos-merge.md` |
| v1.63.0 | Percepciones de compra: catálogo, FC, asiento contable y saldos | Pendiente (Designer primero) | `pendientes/v1.63.0-percepciones-compra.md` |
| v1.63.1 | Percepciones en NC de proveedor (espejo de v1.63.0) | Pendiente | `pendientes/v1.63.1-percepciones-nc-proveedor.md` |
| v1.64.0 | Declaraciones de impuestos e imputación de anticipos sufridos | Pendiente (Designer primero) | `pendientes/v1.64.0-declaraciones-impuestos-imputacion-anticipos.md` |
| v1.65.0 | Borrador editable y líneas extras en facturación masiva | Pendiente | `pendientes/v1.65.0-borrador-editable-facturacion-masiva.md` |
| v1.66.0 | Dashboard ejecutivo financiero + reubicación del panel de RRHH | Pendiente (Designer primero) | `pendientes/v1.66.0-dashboard-financiero-rrhh.md` |

---

## Áreas candidatas (sin planificar)

- **Libro Diario y Libro Mayor**: interfaces de consulta contable (v3.4.0 completada en prompts; verificar cierre en CHANGELOG si aplica)
- **Tesorería**: saldos en tiempo real por cuenta; ~~endoso e-cheq en OP~~ → v1.39.1
- **Percepciones e impuestos**: ~~percepciones en compras (catálogo + FC + asiento + saldos)~~ → **v1.63.0** (planificada). ~~percepciones en NC de proveedor~~ → **v1.63.1** (planificada). ~~declaraciones del impuesto + imputación de anticipos sufridos~~ → **v1.64.0** (planificada). Futuro: pago del saldo a pagar de una DDJJ (v1.64.1), importación de archivos AFIP/IIBB (v1.64.2), traslado automático de saldo a favor cross-período (v1.64.3).
- **Lector QR facturas de compra**: ~~escaneo de QR de facturas recibidas para autocompletar datos~~ → completado en v1.28.0
- **UI/UX**: auditoría visual, migración de componentes, design system
- **Facturación masiva**: ~~borrador editable + líneas extras antes de AFIP~~ → **v1.65.0** (planificada). Futuro: catálogo de servicios facturables recurrentes (v1.67.0 si surge necesidad), auto-guardado del borrador (v1.67.1).
- **Dashboard ejecutivo**: ~~panel financiero con KPIs del mes (ventas/compras/ingresos/egresos) + reubicación de RRHH a `/rrhh`~~ → **v1.66.0** (planificada). Futuro: drilldown desde gráficos (v1.66.1), saldos de tesorería + deudores (v1.66.2), filtros de período personalizado (v1.66.3), comparación interanual (v1.66.4), export PDF (v1.66.5).
- **Recibos de cobro**: ~~retenciones sufridas en cobranzas~~ → **v1.40.0**; ~~PDF para cliente~~ → **v1.41.0**; cobro parcial — UX y validación de saldo (candidato futuro)
- **Testing**: suite de tests automatizados, cobertura mínima
- **DevOps**: CI/CD, ambientes de staging, deploy automatizado
- **Seguridad**: 2FA, protección adicional (auditoría base cubierta por v2.5.0/v2.7.0, acceso por rol por v2.4.0)
- **Integración LISCOM**: cadena v1.46.0–v1.53.0 (ver sección Planificado). Áreas relacionadas pendientes: webhook push de labit→liscom (alternativa a polling), rate limiting de la API pública, replicación multi-instancia avanzada.

---

## Progreso general

```
Completadas:  ver STATUS.md (última v1.53.0 en develop)
Planificadas: 4 (cadena LISCOM restante en interfases: v1.48.0★, v1.49.0★, v1.50.0★, v1.52.0★)
En proceso:   0
Release master: ver tags; develop incluye v1.53.0
```

---

> Este documento se actualiza al finalizar cada versión o sesión de planificación.
> Última actualización: 2026-05-12 (v1.95.0 mergeada a develop; v1.92.0 planificada: dashboard operativo del laboratorio)

