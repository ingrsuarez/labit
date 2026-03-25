# CHANGELOG — Labit

> Historial de cambios por versión.
> Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/).

---

## [v1.15.0] — 2026-03-25 — Sub-padres y orden fijo de determinaciones

### Agregado
- Migración: campo `sort_order` (integer, default 0) en tabla `tests` para orden global de padres y standalone en informes
- Método `isSubParent()` en modelo `Test`: identifica tests que son hijos de un padre y a su vez padres de otros tests
- Campo "Orden en informe" en modales de crear y editar determinación (`test/index.blade.php`)
- Validación `sort_order` (integer, min:0) en `TestController::store()` y `update()`

### Modificado
- `Test::getAllChildren()` refactorizado a versión recursiva: ahora soporta 3 niveles (padre → sub-padre → hijo) recorriendo nietos automáticamente
- PDF de muestras (`sample/pdf-mpdf.blade.php`): reescrito bloque de agrupación y renderizado con soporte para 3 niveles, orden por `sort_order` global + `test_parents.order` interno, indentación progresiva (0px/20px/40px)
- PDF de admisiones clínicas (`lab/admissions/pdf-mpdf.blade.php`): misma lógica de 3 niveles con `sort_order` y orden interno
- Sub-padres se renderizan como encabezados intermedios indentados, sin fila de resultado

### Notas
- Compatible con datos existentes: `sort_order` default 0 no altera el orden previo
- Los hijos se ordenan por `test_parents.order` (ya existente en la tabla pivot)
- Al agregar un padre a un protocolo, `getAllChildren()` recursivo trae automáticamente todos los descendientes (hijos y nietos)
- No requiere cambios en SampleController ni LabAdmissionController (la recursión es transparente)

---

## [v1.14.1] — 2026-03-25 — Otros valores de referencia en determinaciones

### Agregado
- Migración: campo `other_reference` (text, nullable) en tabla `tests` para valores de referencia no numéricos
- Campo "Otros valores de referencia" en modales de crear y editar determinación (`test/index.blade.php`)
- Validación `other_reference` (string, max:500) en `TestController::store()` y `update()`
- Lógica en `SampleController::buildReferenceValue()`: usa `other_reference` como fallback cuando `low`/`high` están vacíos, o lo concatena con `|` si ambos tienen contenido
- Fallback en PDF de muestras (`sample/pdf-mpdf.blade.php`): muestra `other_reference` del test si `reference_value` de la determinación está vacío
- Fallback en PDF de admisiones clínicas (`lab/admissions/pdf-mpdf.blade.php`): misma lógica

### Notas
- Útil para determinaciones de aguas/alimentos: "Ausencia en 100ml", "Positivo/Negativo", "< 10 UFC/ml"
- Compatible con determinaciones existentes sin cambios (campo nullable)
- Para determinaciones creadas antes de esta versión, el fallback en PDFs funciona sin migrar datos

---

## [v1.14.0] — 2026-03-25 — Precios en protocolos de aguas y alimentos

### Agregado
- Migración: campo `categories` (JSON array) en tabla `tests` — permite multi-categoría por test (`clinico`, `aguas_alimentos`, `veterinario`)
- Migración: campo `discount_percent` (decimal 5,2) en tabla `customers`
- Migración: campo `price` (decimal 10,2) en tabla `sample_determinations` — precio inmutable al momento de crear
- Filtrado de tests por `whereJsonContains('categories', 'aguas_alimentos')` en `SampleController::create()` y `edit()`
- Precio en dropdown de sugerencias del buscador de determinaciones
- Columna "Precio" en tabla de determinaciones seleccionadas
- Bloque de totales (subtotal, descuento, total) debajo de la tabla
- Cálculo reactivo de totales con Alpine.js (`subtotal`, `discountAmount`, `total`)
- Actualización dinámica del descuento al cambiar de cliente
- Campo "Descuento (%)" en formularios de creación y edición de cliente
- Columna "Precio" y total en vista show de protocolo (condicional: solo si hay precios)

### Notas
- Tests existentes inicializados como `["clinico"]` por defecto
- Los hijos de un test padre se crean con precio 0 (el precio es del padre)
- El precio se calcula al crear: `test.price × (1 - customer.discount_percent / 100)`
- Compatible con protocolos existentes sin precios

---

## [v1.10.0] — 2026-03-24 — Importación de nomencladores desde Excel

### Agregado
- `NomencladoresExcelSeeder`: lee 8 archivos `.xlsx` de `docs/` y crea nomencladores base
- 8 nomencladores nuevos: PAMI (1321), Medicus (904), OMINT (1270), Swiss Medical (1012), ISSN (1151), Nomenclador 2016 (1264), Nomenclador 2016 Uni (1264), Nomenclador 2012 Reducido (640)
- 297 tests nuevos creados automáticamente para códigos inexistentes
- 8.826 relaciones InsuranceTest con valores NBU
- Captura de columna AUTORIZACION (SI/NO) en nomencladores que la incluyen (PAMI, Medicus, OMINT, Swiss Medical)
- Seeder idempotente: segunda ejecución actualiza sin duplicar

### Notas
- Los archivos `.xlsx` deben estar en `docs/` para que el seeder funcione
- Estructura de todos los archivos consistente: A=código, B=nombre, C=NBU/NIVEL
- No requiere migraciones, solo ejecutar el seeder

---

## [v1.9.0] — 2026-03-24 — Firma digital de validadores y nombre automático de PDF

### Agregado
- Migración: campo `signature_path` en tabla `users` para almacenar ruta de imagen de firma
- Accessors `signature_url` y `signatureAbsolutePath` en modelo `User`
- `UserSignatureController` con métodos `update()` y `destroy()` para gestión de firma
- Rutas `POST /user/signature` y `DELETE /user/signature`
- Sección "Firma Digital" en página de perfil (`/user/profile`) con upload, preview en tiempo real (Alpine.js), reemplazo y eliminación
- Imagen de firma del validador en PDF de protocolo de muestras (ruta absoluta para mPDF, con guard `file_exists`)
- Método `generatePdfFilename()` en `SampleController`: nombre de archivo descriptivo `tipo-cliente-fecha.protocolo.pdf`

### Modificado
- `downloadPdf()` y `viewPdf()` en `SampleController`: usan nombre de archivo descriptivo en vez de `Protocolo_NNN.pdf`

---

## [v1.8.0] — 2026-03-23 — Búsqueda activa en protocolos de muestras

### Modificado
- Listado de protocolos: filtrado client-side con Alpine.js en tiempo real
- Búsqueda instantánea por protocolo, cliente y lugar (sin botón Filtrar)
- Selects de tipo y estado aplican filtro inmediato al cambiar
- Contador de resultados visibles dinámico
- Eliminada paginación server-side, carga completa de registros
- Badge de tipo "Hielo" en cian

---

## [v1.7.0] — 2026-03-23 — Cobro a particulares y control de deuda

### Agregado
- Migración: campos `payment_status`, `payment_method`, `paid_amount`, `payment_date`, `payment_notes` en tabla `admissions`
- Modelo `Admission`: helpers `isParticular()`, `balance`, `total_to_pay`, scope `debtors()`
- Sección de cobro condicional en formulario de admisión para pacientes "Particular"
- Endpoint `registerPayment` para registrar pagos posteriores desde el show de admisión
- Vista de deudores (`/lab/debtors`) con filtros, resumen de deuda total y acciones de cobro
- Link "Deudores" en sidebar del laboratorio
- Medios de pago: efectivo, transferencia, Mercado Pago
- Estados de pago: `pagado`, `parcial`, `pendiente`, `not_applicable`

---

## [v1.6.1] — 2026-03-23 — Filtrar nomencladores de dropdowns y crear Particular

### Agregado
- Seeder `ParticularInsuranceSeeder`: crea registro "Particular" (type=particular) idempotente

### Corregido
- PatientController: filtrar `type != nomenclador` en `index()` y `edit()`
- LabAdmissionController: filtrar `type != nomenclador` en `index()`, `create()`, `edit()`
- AdmissionController: filtrar `type != nomenclador` en `index()`
- LabReportController: filtrar `type != nomenclador` en `monthly()`
- "Particular" aparece primero en todos los dropdowns de obra social

---

## [v1.6.0] — 2026-03-23 — Formato tabular en PDFs de informes

### Modificado
- Template PDF (`pdf-mpdf.blade.php`) reformateado de layout vertical a formato tabular compacto
- Cada determinación ocupa una fila con columnas: Análisis | Resultado | Unidad | Valores de ref.
- Encabezado de sección con fondo gris, texto bold mayúsculas y ancho completo
- Encabezados de columnas (Análisis, Resultado, Unidad, Valores de ref.) con separador inferior
- Tests padre como sub-encabezados: nombre bold en color teal, sin resultado, con categoría de referencia a la derecha
- Tests hijo indentados (padding-left 20px) debajo de su padre con resultado, unidad y valor de referencia en columnas
- Tests standalone en fila normal sin indentación
- Método como subtexto italic gris debajo de cada determinación (cuando existe)
- Resultado en bold, valores de referencia alineados a la derecha

### Sin cambios
- Header y footer del PDF (logo, empresa, paginación, firma)
- Lógica de ordenamiento padre/hijo/standalone
- Bloque de conclusión (cumple/no cumple)
- Sección de validación y firma

---

## [v1.5.4] — 2026-03-22 — Tests faltantes y jerarquía padre-hijo completa

### Agregado
- `MissingTestsSeeder`: crea 27 tests hijos faltantes agrupados por padre (Hemograma 4, Fórmula Leucocitaria 4, Hepatograma 3, Orina Completa 14, Drogas 2)
- Códigos autogenerados con prefijo por grupo: `VCM`, `HCM`, `CHCM`, `RDW` (hemograma), `NEUTSE`, `BASO`, `LINF`, `MONO` (fórmula), `BILTOT`, `BILDIR`, `BILIND` (hepatograma), `ORI-*` (orina), `DRG-*` (drogas)
- Re-ejecución de `TestParentChildSeeder` completa las 37 relaciones padre-hijo (antes solo 10)
- Badge de padre en tabla de determinaciones: tests hijos muestran el nombre del padre en un badge teal
- Info de padre en modal de edición: texto "Pertenece a: ..." debajo del título

### Corregido
- Eliminar un padre del protocolo ahora cascadea a sus hijos (antes solo eliminaba la determinación padre)
- Mensaje de confirmación indica cuántas subdeterminaciones se eliminaron

### Notas
- Ambos seeders son idempotentes y pueden re-ejecutarse sin riesgo
- Material de los hijos se copia del test padre

---

## [v2.3.0] — 2026-03-22 — RRHH multi-empresa

### Agregado
- Migración `add_company_id_to_employees_table`: columna `company_id` nullable en `employees`
- Relación `company()` (BelongsTo) en modelo `Employee` y `company_id` en `$fillable`
- Relación inversa `employees()` (HasMany) en modelo `Company`
- Filtrado por empresa activa en `EmployeeController@show` (listado) y estadísticas de resumen
- Asignación de `company_id` en `store()` (default: empresa activa) y `save()` (edición)
- Campo select "Empresa Empleadora" en formularios de alta y edición de empleado
- Columna "Empresa" en la tabla del listado de empleados
- Empresa empleadora visible en perfil del empleado
- `PayrollController`: filtrado por empresa activa en `index`, `sac`, `bulk`, `closed`, `liquidarBulk`, `pagarBulk`, `downloadBulkPdf`
- Recibos PDF: encabezado dinámico con razón social, CUIT y domicilio de la empresa empleadora (`$payroll->employee->company`)
- Portal del empleado: logo y nombre de la empresa del empleado en header y sidebar (sin selector de empresa)

### Sin cambios
- Organigrama: sigue mostrando todos los empleados sin filtrar por empresa (organización única)
- Conceptos salariales (`salary_items`): siguen siendo globales

---

## [v2.2.1] — 2026-03-22 — Fix columnas vacías en vista de protocolo de muestras

### Corregido
- Columnas Resultado, Unidad, Estado y Acciones vacías en la vista de detalle de protocolo (`sample/show`)
- Causa: `<template x-if>` de Alpine.js dentro de `<tr>` causa "foster-parenting" en el parser HTML, sacando los `<td>` fuera de la fila
- Fix: reemplazar `<template x-if>` por `x-show` directamente en los `<td>` (5 bloques afectados: 4 de modo ver + 1 de modo editar)

---

## [v2.2.0] — 2026-03-22 — Compras y pagos multi-empresa

### Agregado
- Migración `add_company_id_to_purchase_tables`: columna `company_id` nullable en `purchase_quotation_requests`, `purchase_orders`, `delivery_notes`, `purchase_invoices`, `payment_orders`
- Relación `company()` (BelongsTo) en los 5 modelos de compras y `company_id` en `$fillable`
- Relaciones inversas en `Company`: `purchaseQuotationRequests()`, `purchaseOrders()`, `deliveryNotes()`, `purchaseInvoices()`, `paymentOrders()`
- Filtrado por empresa activa en `index()` de todos los controladores de compras
- Asignación automática de `company_id` en `store()` de todos los controladores
- Guard `abort_if(403)` en `show()`, `edit()`, `update()`, `destroy()` y métodos de acción (`updateStatus`, `accept`, `confirm`) de todos los controladores
- Dropdowns de documentos relacionados (OC, remitos, facturas) filtrados por empresa activa en formularios de creación/edición
- Cálculo de `total_balance` en facturas de compra filtrado por empresa activa

### Corregido
- Bug de compilación Blade: `@json()` con arrow functions multi-línea (`fn()`) extraídas a bloques `@php` en 8 vistas del módulo de compras (create/edit de purchase-orders, delivery-notes, purchase-invoices, payment-orders)

### Notas
- Proveedores, insumos y categorías son GLOBALES — no llevan `company_id`
- Movimientos de stock son globales por ahora (inventario físico compartido)
- Los ítems de cada documento no necesitan `company_id` — se acceden a través de su documento padre

---

## [v2.1.0] — 2026-03-22 — Ventas y cobros multi-empresa

### Agregado
- Migración `add_company_id_to_sales_tables`: columna `company_id` nullable en `sales_invoices`, `quotes`, `collection_receipts`, `credit_notes`, `points_of_sale`
- Relación `company()` (BelongsTo) en los 5 modelos de ventas y `company_id` en `$fillable`
- Relaciones inversas en `Company`: `salesInvoices()`, `quotes()`, `collectionReceipts()`, `creditNotes()`, `pointsOfSale()`
- Filtrado por empresa activa en `index()` de todos los controladores de ventas
- Asignación automática de `company_id` en `store()` de todos los controladores
- Guard `abort_if(403)` en `show()`, `edit()`, `update()`, `destroy()` de todos los controladores
- Puntos de venta filtrados por empresa en dropdowns de creación/edición de facturas
- Facturas pendientes filtradas por empresa en recibos de cobro y notas de crédito

### Modificado
- PDF de factura: datos del emisor (razón social, CUIT, domicilio, condición IVA, IIBB, inicio actividades) leídos desde `$invoice->company` con fallback a `config('afip.emisor')`
- `AfipService`: constructor acepta `?Company` opcional, resuelve CUIT/certificados/modo desde el modelo Company con fallback a `config/afip.php`
- Cache de token AFIP (TA) separado por CUIT para evitar colisiones entre empresas

---

## [v2.0.0] — 2026-03-21 — Infraestructura multi-empresa

### Agregado
- Modelo `Company` con datos fiscales (CUIT, condición IVA, IIBB), dirección, configuración AFIP (cert, key, producción) y estado activo/inactivo
- Migración `create_companies_table` y tabla pivot `company_user` con flag `is_default`
- Relaciones `User::companies()` (BelongsToMany) y `User::defaultCompany()`
- Helper global `active_company_id()` y `active_company()` con autoload en `composer.json`
- Middleware `SetActiveCompany` en grupo `web`: inicializa empresa activa desde default del usuario, comparte `$activeCompany` y `$userCompanies` con todas las vistas
- Selector de empresa en header (Alpine.js dropdown) visible cuando el usuario tiene más de una empresa asignada
- Ruta `POST /switch-company` para cambiar empresa activa en sesión
- `CompanyController` con CRUD completo: index, create, store, show, edit, update, destroy (soft delete por `is_active`)
- Gestión de usuarios por empresa: attach, detach, set default
- 5 permisos nuevos: `companies.section`, `companies.create`, `companies.edit`, `companies.delete`, `companies.assign-users`
- Enlace "Empresas" en sidebar bajo sección Configuración, protegido por `@can('companies.section')`
- Vistas: `companies/index`, `create`, `edit`, `_form`, `show` con listado de usuarios asignados
- `CompanySeeder` con 2 empresas iniciales (unipersonal + SAS) asignadas al admin

### Corregido
- `phpunit.xml`: habilitado SQLite in-memory para evitar wipe accidental de la base de datos real durante tests
- `.gitignore`: excluido `.env.testing`

---

## [v1.5.3] — 2026-03-20 — Seeder de jerarquía padre-hijo de prácticas

### Agregado
- `TestParentChildSeeder` para configurar relaciones en tabla pivote `test_parents`
- 10 relaciones padre-hijo para 3 prácticas padre:
  - Hemograma → Glóbulos Rojos, Hemoglobina, Hematocrito, Glóbulos Blancos (4 hijos)
  - Fórmula Leucocitaria → Eosinófilos (1 hijo)
  - Hepatograma → GOT, GPT, FAL, Colesterol Total, Proteína Totales (5 hijos)
- Búsqueda por nombre con validación estricta (ratio >= 40%, anti-falsos positivos)
- Seeder idempotente y tolerante: loguea warnings para tests no encontrados

### Notas
- 26 tests hijos no existen aún en la tabla `tests` (VCM, HCM, CHCM, RDW-CV, bilirrubinas, componentes de Orina Completa, Fórmula Leucocitaria parcial, Drogas X 2)
- El seeder puede re-ejecutarse después de agregar los tests faltantes
- Corregida relación incorrecta pre-existente: hemograma → glóbulos blancos materia fecal

---

## [v1.5.2] — 2026-03-17 — Roles y permisos del módulo de muestras

### Agregado
- Permisos granulares para muestras: `samples.section`, `samples.show`, `samples-results.*`, `samples-reports.*`, `samples-labels.print`
- Rutas de muestras protegidas con middleware `permission:samples.section`
- `authorize()` en SampleController para cada método (index, create, store, show, edit, update, loadResults, saveResults, downloadPdf, viewPdf, sendEmail, printLabel)
- Directivas `@can` en vistas: botones de cargar resultados, validar, PDF, etiquetas, editar
- Sidebar del lab protegido con `@can('samples.index')`
- Extensión de roles existentes (`recepcion-lab`, `tecnico-lab`, `bioquimico`) con permisos de muestras

### Diferencias con lab clínico (v1.5.1)
- Todos los roles pueden imprimir y enviar informes (no solo bioquímico y recepcionista)
- Middleware de validación actualizado de `can:samples.validate` a `permission:samples-results.validate`

---

## [v1.5.1] — 2026-03-17 — Roles y permisos del módulo de laboratorio

### Agregado
- 3 roles nuevos: `recepcion-lab`, `tecnico-lab`, `bioquimico`
- 15 permisos: `lab.section`, `patients.*`, `lab-admissions.*`, `lab-results.*`, `lab-reports.*`
- Rutas del módulo de laboratorio protegidas con middleware `permission:lab.section`
- `authorize()` en LabAdmissionController y LabReportController para cada método
- Directivas `@can` en vistas: botones de crear admisión, editar, validar, guardar resultados
- Variables `$canEditResults` y `$canValidate` para control granular de inputs y botones
- Link "Ir a Laboratorio" en admin sidebar protegido con `@can('lab.section')`

---

## [v1.4.1] — 2026-03-17 — Fix guardado de resultados de protocolo

### Corregido
- Bug crítico: formularios anidados (DELETE dentro del form de POST) causaban `MethodNotAllowedHttpException` al guardar resultados
- Reemplazados formularios anidados de validar/desvalidar/eliminar por botones con `submitAction()` (formularios dinámicos creados fuera del DOM del form principal)

---

## [v1.4.0] — 2026-03-17 — Notas de crédito electrónicas

### Agregado
- Modelo `CreditNote` y `CreditNoteItem` con campos AFIP (CAE, voucher_number, response)
- Migraciones `create_credit_notes_table` y `create_credit_note_items_table`
- `AfipService::createCreditNote()` con comprobante asociado (`CbtesAsoc`) obligatorio
- `CreditNoteController` con CRUD completo, AFIP retry y cálculo automático de totales
- Vistas: listado con filtros, creación desde factura de venta, detalle con datos AFIP
- Botón "Crear NC" y sección "Notas de Crédito Asociadas" en `show` de factura de venta
- Relación `SalesInvoice::creditNotes()`
- Link "Notas de Crédito" en navegación de ventas y sidebar
- Permisos `credit-notes.index`, `credit-notes.create`, `credit-notes.delete` para admin, contador y ventas

---

## [v1.3.1] — 2026-03-15 — Fix AFIP CondicionIVAReceptorId + ImpTotal

### Corregido
- Campo `CondicionIVAReceptorId` (RG 5616) inyectado vía `AfipSoapClient` custom por limitación del WSDL
- Cálculo de `ImpTotal` directo desde ítems para evitar doble conteo de IVA
- Persistencia de Token Authorization (TA) en archivo JSON para evitar solicitudes duplicadas

---

## [v1.3.0] — 2026-03-14 — Facturación electrónica WSFEv1

### Agregado
- `AfipService` con WSAA (autenticación) y WSFEv1 (facturación electrónica) usando `SoapClient` nativo
- Autorización automática de facturas electrónicas con obtención de CAE
- Campos AFIP en `SalesInvoice`: `cae`, `cae_expiration`, `afip_voucher_number`, `afip_result`, `afip_response`, `is_electronic`
- Detección automática de punto de venta electrónico (`is_electronic`, `afip_pos_number`)
- Reintentar autorización AFIP para facturas rechazadas
- Generación de PDF fiscal con datos del emisor, CAE, código de barras Code 128 y QR AFIP (RG 4892)
- Banner visual en formulario de creación para puntos de venta electrónicos
- Indicadores ⚡/⚠️ en listado de facturas según estado AFIP

---

## [v1.2.0] — 2026-03-14 — Infraestructura AFIP

### Agregado
- Certificados AFIP de homologación (CSR, CRT, KEY) en `storage/app/afip/`
- Configuración `config/afip.php` con datos de certificado, CUIT, entorno y emisor
- Variables de entorno para AFIP (`AFIP_CUIT`, `AFIP_ENV`, `AFIP_CERT_PATH`, etc.)

---

## [v1.1.0] — 2026-03-16 — Normalización de line endings

### Agregado
- `.gitattributes` con reglas explícitas por tipo de archivo (PHP, Blade, JS, CSS, MD, JSON, etc.) y binarios
- `.editorconfig` con charset UTF-8, LF, indentación por tipo de archivo

### Cambiado
- Re-normalización de todos los archivos trackeados (CRLF → LF)

---

## [v1.0.1] — 2026-03-14 — README del proyecto

### Agregado
- `README.md` completo con descripción, stack tecnológico, módulos, instalación y estructura

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
