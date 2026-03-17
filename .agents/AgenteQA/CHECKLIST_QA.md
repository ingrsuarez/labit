# CHECKLIST QA — Módulos del proyecto Labit

> Referencia para el AGENTE_QA. Cada ítem debe verificarse en el navegador.
> Rutas y permisos completos en `BLUEPRINT.md`.

---

## Cómo usar este checklist

- Marcar ✅ si pasa, ❌ si hay bug, ⚠️ si hay advertencia menor.
- Verificar **siempre con dos usuarios**: uno con el permiso correcto y otro sin él.
- Si un módulo no fue modificado en el scope actual, puede marcarse como `⏭ no en scope`.

---

## 🌐 Acceso y autenticación

| Check | URI | Descripción |
|-------|-----|-------------|
| [ ] | `/login` | Formulario carga, credenciales correctas redirigen al dashboard |
| [ ] | `/login` | Credenciales incorrectas muestran error sin romper |
| [ ] | `/logout` | Cierra sesión y redirige al login |
| [ ] | `/dashboard` | Dashboard admin carga sin errores |

---

## 🧪 Laboratorio clínico

| Check | URI | Descripción |
|-------|-----|-------------|
| [ ] | `/patients` | Lista de pacientes visible |
| [ ] | `/lab-admissions` | Admisiones con protocolo, carga correcta |
| [ ] | `/lab-admissions/create` | Formulario de nueva admisión funciona |
| [ ] | `/lab-reports` | Reportes mensuales, exportación Excel funciona |
| [ ] | `/tests` | CRUD de tests/determinaciones |
| [ ] | `/insurances` | Nomenclador por obra social |

---

## 🔬 Laboratorio de muestras

| Check | URI | Descripción |
|-------|-----|-------------|
| [ ] | `/samples` | Lista de muestras de aguas/alimentos |
| [ ] | `/samples/create` | Formulario de nueva muestra |
| [ ] | `/samples/{id}` | Detalle con PDF y etiqueta con código de barras |
| [ ] | `/reference-categories` | Categorías de referencia |

---

## 💰 Ventas

| Check | URI | Descripción |
|-------|-----|-------------|
| [ ] | `/sales-invoices` | Lista de facturas de venta con indicadores AFIP (⚡/⚠️) |
| [ ] | `/sales-invoices/create` | Formulario de creación, banner electrónico, autorización AFIP |
| [ ] | `/sales-invoices/{id}` | Detalle con datos AFIP (CAE, vencimiento), botón PDF |
| [ ] | `/sales-invoices/{id}/pdf` | Descarga PDF con QR AFIP y datos fiscales |
| [ ] | `/credit-notes` | Lista de notas de crédito con filtros |
| [ ] | `/credit-notes/create?sales_invoice_id=X` | Crear NC desde factura, prellenado correcto |
| [ ] | `/credit-notes/{id}` | Detalle NC con datos AFIP |
| [ ] | `/quotes` | Presupuestos (PRES-AÑO-NNNN) |
| [ ] | `/collection-receipts` | Recibos de cobro (RC-AÑO-NNNN) |
| [ ] | `/customers` | CRUD de clientes |
| [ ] | `/points-of-sale` | Puntos de venta (electrónicos y manuales) |
| [ ] | `/services` | Catálogo de servicios |

---

## 🛒 Compras

| Check | URI | Descripción |
|-------|-----|-------------|
| [ ] | `/purchase-orders` | Órdenes de compra |
| [ ] | `/delivery-notes` | Remitos |
| [ ] | `/purchase-invoices` | Facturas de compra |
| [ ] | `/payment-orders` | Órdenes de pago |
| [ ] | `/suppliers` | CRUD de proveedores |
| [ ] | `/supplies` | Insumos con stock mínimo |
| [ ] | `/supply-categories` | Categorías de insumos |
| [ ] | — | Flujo completo: cotización → OC → remito → factura → pago |

---

## 👥 RRHH

| Check | URI | Descripción |
|-------|-----|-------------|
| [ ] | `/employees` | Lista de empleados con legajos |
| [ ] | `/employees/{id}` | Detalle de empleado, organigrama |
| [ ] | `/payrolls` | Liquidaciones mensuales (individual y masiva) |
| [ ] | `/payrolls/{id}/pdf` | Recibo de sueldo PDF |
| [ ] | `/vacations` | Calendario visual de vacaciones |
| [ ] | `/leaves` | Ausencias y licencias |
| [ ] | `/documents` | Documentación con vencimiento |

---

## ✅ Calidad

| Check | URI | Descripción |
|-------|-----|-------------|
| [ ] | `/non-conformities` | No conformidades con seguimiento |
| [ ] | `/circulars` | Circulares internas con firma digital |

---

## 🏠 Portal del empleado

| Check | URI | Descripción |
|-------|-----|-------------|
| [ ] | `/portal/dashboard` | Dashboard del empleado |
| [ ] | `/portal/team` | Equipo y directorio |
| [ ] | `/portal/payrolls` | Recibos de sueldo del empleado |
| [ ] | `/portal/vacations` | Solicitudes de vacaciones |
| [ ] | `/portal/leaves` | Solicitudes de licencias |
| [ ] | `/portal/circulars` | Lectura y firma de circulares |

---

## 🔧 Administración

| Check | URI | Descripción |
|-------|-----|-------------|
| [ ] | `/users` | Gestión de usuarios |
| [ ] | `/roles` | Gestión de roles |

---

## 🔑 Verificaciones transversales (en todos los módulos)

- [ ] No hay errores en la **consola del navegador** (JS errors, 404 de assets)
- [ ] No hay **N+1 queries** visibles (tiempos de carga razonables)
- [ ] Los **flash messages** aparecen y desaparecen correctamente
- [ ] Los **modales** abren, cierran y no dejan el scroll bloqueado
- [ ] Las **tablas con paginación** cambian de página correctamente
- [ ] Los **filtros** de búsqueda/filtrado funcionan y se pueden limpiar
- [ ] Los **formularios** muestran errores de validación inline (no `alert()`)
- [ ] Los **permisos** están correctamente configurados: sin permiso → 403 o redirect
