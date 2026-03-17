# CHANGELOG — Labit

> Historial de cambios por versión.
> Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/).

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
