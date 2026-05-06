# BLUEPRINT — Labit

> Arquitectura técnica, estructura del proyecto y decisiones de diseño.
> Fuente de verdad para el Agente CTO y cualquier agente que necesite contexto técnico.
> Última actualización: 2026-05-06 (DD-011 declaraciones juradas de impuestos e imputación de anticipos)

---

## Stack tecnológico

| Capa | Tecnología | Versión | Notas |
|---|---|---|---|
| **Lenguaje** | PHP | ^8.2 | |
| **Framework** | Laravel | ^11.2 | |
| **Frontend** | Blade + Livewire 3 + Alpine.js | | Componentes reactivos |
| **CSS** | Tailwind CSS | 3.x | + @tailwindcss/forms, @tailwindcss/typography |
| **Base de datos** | MySQL | | Configurado por defecto |
| **Autenticación** | Jetstream + Fortify + Sanctum | | Stack Livewire |
| **Roles/Permisos** | Spatie Laravel Permission | | RBAC granular |
| **PDF** | barryvdh/laravel-dompdf + carlos-meneses/laravel-mpdf | | DomPDF para simples, mPDF para multipágina |
| **Excel** | Maatwebsite Excel | | Exportaciones |
| **Códigos de barra** | Picqer Barcode Generator | | Etiquetas de muestras |
| **Build** | Vite | 4.x | |
| **Iconos** | Bootstrap Icons | | |
| **Selects** | Tom Select | | Dropdowns con búsqueda |

---

## Estructura del proyecto

```
labit/
├── app/
│   ├── Actions/               → Acciones de Jetstream/Fortify
│   ├── Console/               → Comandos Artisan custom
│   ├── Exceptions/            → Manejo de excepciones
│   ├── Exports/               → Clases de exportación Excel (Maatwebsite)
│   ├── Http/
│   │   ├── Controllers/       → Controladores (~45 controladores resource)
│   │   │   └── Portal/        → Controladores del portal del empleado
│   │   ├── Middleware/        → CheckSystemAccess, HasEmployee, etc.
│   │   └── Requests/         → Form Requests de validación
│   ├── Livewire/             → Componentes Livewire
│   ├── Mail/                 → Mailables (envío de resultados, etc.)
│   ├── Models/               → 51 modelos Eloquent
│   ├── Providers/            → Service Providers
│   ├── Services/             → Lógica de negocio encapsulada
│   └── View/                 → View Composers
├── config/                   → Configuración de Laravel y paquetes
├── customer_files/           → Archivos de clientes (uploads)
├── database/
│   ├── factories/            → Factories para testing
│   ├── migrations/           → 90+ migraciones (incl. stock por sede, pivote FC–remitos)
│   └── seeders/              → Seeders (roles, permisos, datos iniciales)
├── docs/                     → Documentación adicional
├── public/                   → Assets públicos y entry point
├── resources/
│   ├── css/                  → Estilos (Tailwind)
│   ├── js/                   → JavaScript (Alpine.js, etc.)
│   ├── views/                → 48 directorios de vistas Blade
│   │   ├── components/       → Componentes Blade reutilizables
│   │   ├── layouts/          → Layouts: admin, lab, portal
│   │   ├── livewire/         → Vistas de componentes Livewire
│   │   └── [módulo]/         → Vistas por módulo (CRUD)
│   └── markdown/             → Templates de email (Jetstream)
├── routes/
│   ├── web.php               → Rutas principales
│   └── api.php               → Rutas API (si aplica)
├── scripts/                  → Scripts auxiliares
├── storage/                  → Storage de Laravel
├── tests/                    → Tests (PHPUnit/Pest)
├── .agents/                  → Sistema multi-agente (PM, Dev, QA, etc.)
└── agent-bootstrap/          → Cola de trabajo de agentes
```

---

## Patrones de arquitectura

- **MVC estándar de Laravel** — Controladores resource con las 7 acciones CRUD
- **Blade + Livewire** — Vistas Blade con componentes Livewire para interactividad reactiva (formularios, tablas dinámicas)
- **Alpine.js** — Interactividad ligera del lado del cliente (toggles, dropdowns, modales)
- **Service classes** — Lógica de negocio compleja encapsulada en `app/Services/`
- **Form Requests** — Validación separada en Request classes
- **Exports** — Clases dedicadas para exportación Excel con Maatwebsite
- **Mailables** — Envío de emails con templates Blade (resultados de muestras, etc.)
- **Middleware pipeline** — Cadena de middleware para auth, roles, acceso y empleado

---

## Sistema de permisos

### Roles

| Rol | Alcance |
|---|---|
| **Administrador** | Acceso total a todos los módulos |
| **Contador** | Liquidaciones, compras, ventas |
| **Compras** | Módulo de compras e inventario |
| **Ventas** | Módulo de ventas y facturación |
| **Empleado** | Acceso exclusivo al portal del empleado |

### Middleware

| Middleware | Función |
|---|---|
| `auth:sanctum` | Autenticación obligatoria via Sanctum |
| `verified` | Email verificado |
| `check.access` | Determina si el usuario va al panel admin o al portal del empleado |
| `has.employee` | Verifica que el usuario tenga un registro de empleado vinculado |

### Permisos por sección

Los permisos se gestionan con Spatie Laravel Permission y se asignan por sección:
- `compras.section` — acceso al módulo de compras
- `ventas.section` — acceso al módulo de ventas
- Permisos granulares por acción (crear, editar, eliminar) por módulo

---

## Módulos del sistema

| Módulo | Modelos principales | Controladores | Descripción |
|---|---|---|---|
| **Lab clínico** | Patient, Admission, AdmissionTest, Test, Insurance, InsuranceTest | LabAdmissionController, LabSectionController, LabReportController | Admisiones, protocolo, carga y validación de resultados |
| **Lab muestras** | Sample, SampleDetermination, TestReferenceValue, ReferenceCategory, Material | SampleController, TestController, ReferenceCategoryController | Muestras de aguas/alimentos, PDFs, etiquetas, email |
| **Ventas** | Customer, SalesInvoice, SalesInvoiceItem, Quote, QuoteItem, CollectionReceipt, PointOfSale, Service | SalesInvoiceController, QuoteController, CollectionReceiptController, CustomerController | Presupuestos, facturas, recibos de cobro, puntos de venta |
| **Compras** | Supplier, Supply, SupplyCategory, SupplyLabBranchStock, StockMovement, PurchaseQuotationRequest, PurchaseOrder, DeliveryNote, PurchaseInvoice, PaymentOrder | PurchaseOrderController, DeliveryNoteController, PurchaseInvoiceController, PaymentOrderController | Flujo cotización → OC → remito → factura → pago; **stock por sede** (`lab_branch_id` en documentos de compra y movimientos) |
| **RRHH** | Employee, Job, Category, Leave, Holiday, Payroll, PayrollItem, SalaryItem, Document, DocumentFile | EmployeeController, PayrollController, VacationController, LeaveController, DocumentController | Legajos, organigrama, liquidaciones, vacaciones, ausencias |
| **Calidad** | NonConformity, NonConformityFollowUp, Circular, CircularSignature | NonConformityController, CircularController | No conformidades, circulares con firma digital |
| **Portal** | (usa modelos de RRHH y Calidad) | EmployeePortalController, Portal\CircularController | Dashboard, equipo, recibos, solicitudes, circulares |
| **Admin** | User, Role, Permission, ApiClient | UserController, RoleController, PermissionController, AdminSectionController, ApiClientController | Usuarios, roles, permisos, configuración, **API keys públicas** |

---

## Decisiones de diseño

### DD-001: Stack Livewire para Jetstream
- **Decisión:** Usar Livewire como stack de Jetstream en vez de Inertia/Vue
- **Razón:** Consistencia con el frontend Blade existente, menor complejidad de build, equipo más familiarizado con PHP
- **Consecuencia:** El frontend es server-rendered con islas de interactividad (Livewire + Alpine)

### DD-002: Doble motor de PDF
- **Decisión:** Usar DomPDF para documentos simples y mPDF para documentos complejos/multipágina
- **Razón:** DomPDF es más rápido pero tiene limitaciones con tablas complejas y paginación; mPDF las maneja mejor
- **Consecuencia:** Hay que elegir el motor correcto según el tipo de documento

### DD-003: Middleware de acceso dual (admin/portal)
- **Decisión:** Un solo sistema de auth con middleware `check.access` que bifurca admin vs portal
- **Razón:** Evitar duplicar la autenticación; los empleados son usuarios con rol restringido
- **Consecuencia:** Todo usuario autenticado pasa por `check.access` que decide la redirección

### DD-004: Stock de insumos por sede (depósito)
- **Decisión:** Cantidades por `supply_id` + `lab_branch_id` en `supply_lab_branch_stock`; `supplies.stock` como total cache; servicios `SupplyStockService` y `LabBranchResolver` para entrada/salida/ajuste y validación de sede en formularios
- **Razón:** Coherencia con sedes de laboratorio (v1.30.x) y trazabilidad de inventario por depósito
- **Consecuencia:** OC, remitos, FC y movimientos manuales exponen y validan sede; la migración pivote FC–múltiples remitos es **idempotente** ante tablas ya creadas para no cortar la cadena de `migrate`

### DD-005: API pública con API key (no Sanctum), una key por sede
- **Decisión:** Auth máquina-a-máquina por header `X-API-Key`, key con prefijo `labit_` + 40 chars random, persistida solo como hash SHA-256, una key por `lab_branch_id` (más `company_id` requerido). CRUD admin en `/admin/api-clients` con permiso `api-clients.manage`. Middleware `auth.api_key` valida + tracking en background (`afterResponse`) + log estructurado en canal `api`. Endpoint inicial `GET /api/v1/ping`.
- **Razón:** Sanctum apunta a tokens de usuarios humanos; para integraciones máquina-a-máquina (LISCOM, equipos HL7) una key explícita y rotable es más auditable y evita acoplar al ciclo de Sanctum. Una key por sede simplifica el filtrado automático por `lab_branch_id` en endpoints futuros (v1.47.0+) y limita el blast radius si una key se compromete. Prefijo identificable habilita detección de leaks en logs/git/screenshots (estilo Stripe/GitHub).
- **Consecuencia:** La key plana se muestra **una sola vez** al crear/regenerar (modal con confirmación). El `lab_branch_id` es inmutable post-creación: si una sede cambia de instancia, se crea una key nueva. El logging del canal `api` (rotación diaria, `storage/logs/api-YYYY-MM-DD.log`) NO incluye la key plana ni el hash. Sin rate limiting en esta versión; si se necesita, agregar `throttle` al grupo `v1` (Laravel ya lo tiene listo).

### DD-006: Protocolos API unificados con resource polimórfico y filtrado por sede
- **Decisión:** Los 3 modelos de protocolo (`Admission` clínico, `Sample` muestras, `VetAdmission` veterinario) se exponen detrás de **un único** conjunto de endpoints (`GET /api/v1/protocols`, `/by-barcode/{code}`, `/{type}/{id}`) usando un `ProtocolResource` polimórfico que normaliza estructura (`type`, `protocol_number`, `barcode`, `patient`, `determinations`, `lab_branch`) y un `DeterminationResource` que mapea estados heterogéneos (`authorization_status` + `is_validated` para clínicas; `status` enum para muestras y vet) a un vocabulario común `pending|in_progress|completed|validated`. El listado mergea las 3 queries en PHP (`ProtocolLookupService`) en lugar de armar una vista SQL. Filtrado de seguridad **solo por `lab_branch_id`** (no por `company_id`, porque las tablas de protocolo no tienen esa columna). PII (DNI/CUIT del paciente) gateado por `api_clients.patient_data_level` con default `minimal` (oculto). Prefijos del `protocol_number` son letras sueltas (`C`/`A`/`V`) sin guión separador, según el trait `GeneratesProtocolNumber` existente.
- **Razón:** LISCOM y otros equipos HL7 escanean barcodes sin saber a priori el tipo de protocolo. Un endpoint unificado evita 3 integraciones paralelas y elimina lógica de routing en el cliente. El merge en PHP es aceptable hasta ~500 protocolos/día/sede; si la latencia p95 sube de 200ms se migra a vista SQL `protocols_unified` en hotfix v1.47.1. El filtro por sede ya está cubierto por la API key (DD-005), por lo que NO hace falta adicionalmente un filtro por empresa: cada empresa tiene sus propias sedes y la key de una sede no puede ver protocolos de otra. PII default `minimal` minimiza superficie de exposición legal/regulatoria; las integraciones que necesiten DNI deben justificarlo y promoverse a `standard` desde el admin.
- **Consecuencia:** Cualquier nuevo modelo "tipo protocolo" debe agregarse al enum `App\Enums\ProtocolType`, exponer un `protocol_number` y `lab_branch_id`, definir su mapping en `ProtocolResource::buildPatientData()` y `getDeterminationsRelation()`, y registrar su prefijo en `protocolPrefix()`. La consistencia eventual (un test validado se refleja en la API en cuanto se persiste) hace innecesario un job de sync. El campo `test_code` queda nullable hasta que v1.49.0 implemente el mapeo a códigos HL7 externos. Cualquier extensión del barcode (formato `C2604180012^SUE` para identificar material) debe documentarse en v1.48.5 manteniendo este endpoint compatible.

### DD-007: Ingesta de resultados — no-overwrite si validado, idempotencia doble

- **Decisión:** Endpoint batch `POST /api/v1/results/batch`. La regla central es inmutable: si `is_validated = true` → rechazar con `ALREADY_VALIDATED`, sin excepciones. Idempotencia en dos niveles: `(api_client_id, external_batch_id)` para reintentos de batch y `(api_client_id, hl7_control_id)` para reintentos de mensaje. Modelos de auditoría `ResultBatch` + `ResultIngestion`. No hay endpoint individual. No hay auto-validación. Logging estructurado en canal `api`.
- **Razón:** El flujo de validación del bioquímico es la operación de mayor valor en labit. Permitir que la API de LISCOM sobrescriba resultados ya validados introduciría regresiones silenciosas en datos clínicos. La idempotencia doble elimina la necesidad de que LISCOM implemente deduplicación: puede reintentar libremente. Los modelos `ResultBatch` + `ResultIngestion` persisten el estado para la UI de monitoreo (v1.53.0). Logging estructurado (canal `api`) provee trazabilidad de overwrites y rechazos sin necesidad de modificar `audit_logs` en esta versión.
- **Consecuencia:** LISCOM (v1.52.0) debe leer los `reason` de la response para clasificar los ítems: `ALREADY_VALIDATED` → no reintentar; `PROTOCOL_NOT_FOUND` / `DETERMINATION_NOT_FOUND` → alerta; `duplicate` → ya procesado. El trait `Auditable` no está en los modelos de determinación; si se suma en el futuro, los overwrites quedarán doblemente trazados (log + audit_logs). Notificación al bioquímico sobre resultados nuevos pendientes de validar es tensión abierta (v1.51.1 o v1.53.0).

### DD-008: Dashboard de monitoreo API — permission `lab-admissions.index`, contadores materializados, retención configurable

- **Decisión:** Dashboard Livewire 3 en `/admin/api-monitor` para visualizar la ingesta de resultados (v1.51.0). Acceso vía permission `lab-admissions.index` (no se crea permission nuevo — es compartido por todos los roles del lab). Sección técnica adicional (raw payload, info técnica) gateada por `api-clients.manage`. Counters del dashboard leen de columnas materializadas `result_batches.items_*` (no del JSON `items_summary`). Retención configurable con `API_LOG_RETENTION_DAYS` + comando `api:cleanup`. Sidebar con badge de rechazos ALREADY_VALIDATED del día (cacheado 60s).
- **Razón:** Visibilidad operativa para que el laboratorio confíe en la automatización y detecte problemas temprano. `lab-admissions.index` es el permission más inclusivo del laboratorio sin crear uno nuevo. Los contadores materializados de v1.51.0 evitan agregaciones JSON en vivo. La retención evita que las tablas crezcan sin límite.
- **Consecuencia:** Si en el futuro se añade un permission `view-protocols` más granular, este middleware puede actualizarse. El comando `api:cleanup` debe tener acceso a confirmación interactiva en producción pero usar `--no-interaction` en cron. El badge del sidebar hace 1 query por pageview pero está cacheado 60s.

### DD-009: Facturación masiva en dos pasos — borrador editable + envío a AFIP separado

- **Decisión:** El flujo de facturación masiva (`/billing/batch-preview` → `BillingController::batchInvoice`) deja de emitir la factura en línea. Crea un **borrador editable** (`SalesInvoice` con `status='pendiente'`, `is_electronic=true|false`, `cae=NULL` y `invoice_number='PENDIENTE-AFIP'` para electrónicas) y redirige al `sales-invoices.edit` ya existente. Desde ahí el usuario puede agregar **líneas libres** (descripción + cantidad + precio + IVA, sin `test_id`) — útil para toma de muestra, flete, descuentos, otros conceptos no asociados a determinaciones de protocolos — antes de apretar "Enviar a AFIP" / "Reintentar AFIP" (electrónicas) o "Confirmar" (no electrónicas). El envío a AFIP reusa el endpoint `sales-invoices.retry-afip` ya existente, cuyo gate (`is_electronic && !cae`) sirve tanto para envío inicial como para reintento. El listado `/sales-invoices` expone un filtro **"Borradores AFIP"** con badge de cantidad apoyado en el scope `SalesInvoice::afipDraft()` (`status='pendiente' AND is_electronic=true AND cae IS NULL`) y el accessor `is_afip_draft`. La validación del `invoice_number` en `update` se relaja para borradores (nullable, sin uniqueness) para evitar choques con `PENDIENTE-AFIP` cuando hay varios borradores simultáneos. El controller usa `app(AfipService::class)` (DI) en `retryAfip` para permitir mocking en tests.
- **Razón:** Caso pedido por el cliente: necesita poder agregar líneas extras (toma de muestra, flete, descuentos) y revisar/quitar items antes de obtener el CAE. Emitir directamente desde el preview impedía cualquier ajuste, obligando a anular y rehacer ante el menor cambio. Reusar `edit` + `retry-afip` evita duplicar UI/endpoints y mantiene una sola pantalla de edición de facturas. El filtro del listado da visibilidad operativa de borradores pendientes para que no queden olvidados sin enviar a AFIP. La relajación de `invoice_number` permite que coexistan múltiples borradores con el placeholder mientras esperan CAE.
- **Consecuencia:** Cambio de UX percibible (paso adicional). El banner amarillo "BORRADOR — pendiente de envío a AFIP" en `edit.blade.php` y el texto del botón "Crear borrador" en `batch-preview` lo comunican al usuario. El `invoice_number` solo se materializa con número AFIP cuando `retryAfip` recibe un CAE válido. Tests existentes que esperaban redirect a `show` con CAE inmediato deben ajustarse a la nueva semántica (el flujo masivo ya no emite en línea). Cualquier nueva regla de negocio sobre borradores AFIP debe consultarse contra el scope `afipDraft()` para mantener consistencia con el filtro y el badge del sidebar de facturas.

### DD-010: Percepciones de compra — tabla pivote por factura con catálogo multi-empresa

- **Decisión:** Las percepciones de compra se modelan con dos tablas nuevas: `purchase_perceptions` (catálogo por empresa, con `accounting_account_id`, `jurisdiction`, `rate`) y `purchase_invoice_perceptions` (pivote por factura, con snapshots de nombre/jurisdicción/tasa y `amount`). El campo `percepciones` de `purchase_invoices` se mantiene como cache agregado calculado por `recalculate()` a partir de la suma de la pivote. El `AccountingEntryService::fromPurchaseInvoice` genera una línea de débito individual por cada percepción usando su `accounting_account_id` snapshot, en lugar de una línea única genérica. Para facturas legacy sin pivote (`perceptions->isEmpty() && percepciones > 0`) se genera una línea genérica en cuenta `5.1.02` preservando compatibilidad.
- **Razón:** El campo numérico único `percepciones` no permitía discriminar origen (IIBB Neuquén, IVA RG, ARBA, etc.) ni cuenta contable específica, impidiendo hacer un seguimiento preciso de anticipos para compensar contra DDJJ. La pivote con snapshots permite historial inmutable incluso si el catálogo cambia, y permite calcular el balance por tipo de percepción en el reporte `/purchase-perceptions/balances`.
- **Consecuencia:** El bloque de percepciones en `create/edit` de facturas de compra es ahora multi-línea (Alpine.js) en lugar de un input numérico único. Los asientos de compra generados a partir de esta versión debitan cada percepción en su cuenta contable individual. El reporte de balances (`PurchasePerceptionBalanceService`) cruza los anticipos cargados en facturas con el saldo real de la cuenta contable en el período, mostrando la diferencia como `OK | diferencia`. Permisos nuevos: `purchase-perceptions.{index,create,edit,destroy,balances}`.
- **Extensión v1.63.1:** El mismo patrón de pivote aplica a **notas de crédito de proveedor** (`purchase_credit_note_perceptions` → modelo `PurchaseCreditNotePerception`). El campo cache `percepciones` en `purchase_credit_notes` se recalcula desde la pivote. En `AccountingEntryService::fromPurchaseCreditNote`, cada percepción genera un **crédito** en la cuenta snapshot (revierte el débito que hizo la FC). El reporte de balances resta los montos de NC del total de anticipos cargados para la misma `purchase_perception_id`.

### DD-011: Declaraciones juradas de impuestos e imputación de anticipos sufridos (v1.64.0)

- **Decisión:** Catálogo `Tax` por empresa con cuenta de pasivo (`liability_account_id`) y periodicidad (`monthly` / `quarterly` / `annual`). Las percepciones del catálogo (`purchase_perceptions.tax_id`) agrupan anticipos bajo el mismo impuesto. Las DDJJ se modelan como `TaxReturn` (estados `draft` / `confirmed` / `cancelled`) con líneas explícitas `TaxReturnApplication` que referencian `purchase_invoice_perceptions` y/o `purchase_credit_note_perceptions`. Al **confirmar**, `TaxReturnService::confirm` genera un asiento automático vía `AccountingEntryService::createEntryForSource`: créditos en cuentas de anticipo por FC, débitos por NC, débito en pasivo por monto declarado y líneas de ajuste en la misma cuenta de pasivo para saldo a pagar o saldo a favor. La **anulación** crea un asiento reverso (`cancel`) y libera anticipos para nuevas DDJJ. El reporte de saldos suma columna **imputado** desde aplicaciones en declaraciones confirmadas y **disponible** = anticipos del período − imputado.
- **Razón:** Los anticipos cargados en percepciones (v1.63.x) necesitan un circuito contable de cierre contra obligación fiscal sin duplicar líneas en FC; la DDJJ formaliza el período y permite auditar qué anticipos se compensaron.
- **Consecuencia:** Permisos `taxes.manage` y `tax-returns.manage` (admin + contador vía `TaxDeclarationsPermissionsSeeder`). El pago en efectivo del saldo a pagar sigue siendo asiento manual en Libro Diario (v3.4.0). Restricción MySQL: FKs en `tax_return_applications` usan nombres cortos (`tra_applications_*_fk`) por límite de longitud de identificadores.

---



| Integración | Tipo | Auth | Notas |
|---|---|---|---|
| Email (SMTP) | Envío de resultados y notificaciones | .env config | Resultados de muestras, circulares |
| API pública v1 | Salida de datos a sistemas externos (LISCOM, etc.) | API key (`X-API-Key`) | Modelo `ApiClient`, middleware `auth.api_key`, una key por sede; ver DD-005 (auth) y DD-006 (protocolos unificados clinical/sample/vet). Doc completa: `docs/api/v1/protocols.md`. |
| Ingesta de resultados v1 | Recepción de resultados de equipos HL7 desde LISCOM | API key (`X-API-Key`) | Modelos `ResultBatch` + `ResultIngestion`; ver DD-007. Doc: `docs/api/v1/results.md`. |
| Dashboard monitoreo API | Visualización operativa de la ingesta de resultados | Web auth + permission `lab-admissions.index` | Livewire 3; componentes `App\Livewire\Api\*`; servicio `ApiMonitorService`; ver DD-008. Runbook: `docs/operations/api-monitor.md`. |

---

## Variables de entorno clave

```
APP_NAME=Labit
APP_URL=http://localhost
DB_CONNECTION=mysql
DB_DATABASE=labit
MAIL_MAILER=smtp
```

---

> Este documento se actualiza cuando hay cambios de arquitectura, nuevas integraciones, o decisiones técnicas significativas.
