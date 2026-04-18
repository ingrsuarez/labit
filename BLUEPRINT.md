# BLUEPRINT — Labit

> Arquitectura técnica, estructura del proyecto y decisiones de diseño.
> Fuente de verdad para el Agente CTO y cualquier agente que necesite contexto técnico.
> Última actualización: 2026-04-18 (DD-005 API pública con API key y módulo Admin de keys)

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

---

## Integraciones externas

| Integración | Tipo | Auth | Notas |
|---|---|---|---|
| Email (SMTP) | Envío de resultados y notificaciones | .env config | Resultados de muestras, circulares |
| API pública v1 | Salida de datos a sistemas externos (LISCOM, etc.) | API key (`X-API-Key`) | Modelo `ApiClient`, middleware `auth.api_key`, una key por sede; ver DD-005. Endpoints de negocio en v1.47.0+ |

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
