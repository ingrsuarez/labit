# Diseño UI — v1.41.0 PDF de recibo de cobro (envío al cliente)

**Versión:** v1.41.0  
**Fecha diseño:** 2026-04-07  
**Modo:** Mejora de pantalla existente (`collection-receipts/show`) + **nuevo documento PDF** (plantilla impresa)  
**Scope explícito:** solo generación/descarga de PDF en esta versión — **sin envío por email** (versión futura).

---

## Propósito

Dar al usuario interno un **comprobante descargable** del recibo de cobro para **adjuntar a WhatsApp, correo manual o imprimir**, con la misma información relevante que ve en pantalla (empresa, cliente, número, fecha, estado, medios de pago, retenciones, facturas imputadas, total).

**Usuario:** admin / contador con permiso de ver recibos de cobro (mismo perfil que hoy en `show`).

**Acción principal en pantalla:** abrir o descargar el PDF en un clic, con **paridad visual** con Factura de Venta (botón rojo PDF).

---

## Análisis de la pantalla actual (`show`)

### Qué funciona bien (mantener)

- Cabecera con **número de recibo**, subtítulo “Recibo de Cobro”, **badge de estado** (borrador / confirmado / anulado).
- Bloque de alerta verde si está confirmado.
- Cards de información general y seguimiento.
- Tablas: medios de pago, retenciones, facturas incluidas.

### Qué falta

| Elemento | Estado actual | v1.41.0 |
|----------|---------------|---------|
| Acceso a PDF | No existe | Botón(es) en barra de acciones superior derecha |
| Documento para cliente | Solo pantalla web | PDF A4 generado en servidor (DomPDF) |

### Referencia de consistencia

Reutilizar el **mismo patrón** que `resources/views/sales-invoices/show.blade.php`:

- Botón **PDF**: `inline-flex`, `px-4 py-2`, `bg-red-600`, `hover:bg-red-700`, `text-white`, `text-sm`, `font-medium`, `rounded-lg`, ícono SVG de descarga (mismo path que factura de venta).
- Orden sugerido en la fila de acciones (desktop): **PDF** → (si aplica) **Editar** → **Volver**.

---

## Cambios en la vista `show` (delta)

| Elemento | Antes | Después |
|----------|--------|---------|
| Acciones top-right | Solo Editar (borrador) + Volver | **PDF** siempre visible para quien puede ver el recibo; luego Editar si borrador; luego Volver |
| Nuevo enlace | — | `route('collection-receipts.pdf', $collectionReceipt)` (nombre definitivo lo define el Dev) |

### Comportamiento del botón PDF

- **Abrir en nueva pestaña** (`target="_blank"`) **opcional**: la factura de venta no usa `target="_blank"` en el snippet actual; **recomendación:** igual que FV — mismo `href` sin `blank`, el navegador descarga o previsualiza según `Content-Disposition` del controller. Si el Dev implementa `stream()` para “ver”, puede duplicar enlace “Descargar” — **mínimo viable:** un solo botón “PDF” como FV.
- **Estados:** visible también en **borrador** y **anulado** (es documento interno de gestión salvo que negocio pida ocultar en borrador — **decisión:** mostrar siempre si tiene permiso `show`; el PDF debe reflejar el **estado** textualmente).

### Accesibilidad

- Texto del enlace: **“PDF”** o **“Descargar PDF”** — preferir **“PDF”** para paridad con Factura de Venta.
- `title` opcional: “Descargar recibo de cobro en PDF”.

---

## Diseño del documento PDF (no es pantalla Blade admin)

Objetivo: **legible, imprimible, serio** (gestión / contabilidad), no marketing.

### Estructura sugerida (orden vertical)

1. **Cabecera empresa** — Razón social, CUIT, domicilio si está en `Company` y es razonable mostrarlo; alineado con otros PDFs del sistema (sin inventar campos: usar lo que expone el modelo).
2. **Título del documento** — “RECIBO DE COBRO” (mayúsculas, destacado).
3. **Identificación** — Número interno del recibo, fecha, **estado** (Borrador / Confirmado / Anulado).
4. **Cliente** — Nombre, CUIT/CUIL si existe en `Customer`.
5. **Medios de pago** — Tabla: tipo, importe, detalle (cuenta bancaria, e-cheq + banco + vto., etc.), coherente con labels del modelo (`line_type_label`).
6. **Retenciones sufridas** — Solo si hay filas; tabla con tipo, régimen, certificado, monto (y jurisdicción si aplica); total de retenciones.
7. **Facturas incluidas** — Tabla: comprobante (número completo de FC), monto imputado; **total del recibo** al pie.
8. **Pie legal breve** — Una línea tipo: *Documento de constancia de cobro emitido por [empresa]. No reemplaza la factura fiscal electrónica.* (ajustar redacción legal con criterio de negocio; objetivo: no confundir con comprobante AFIP).

### Estilo visual PDF

- Familia **Arial/sans-serif**, tamaños ~10–11px cuerpo, tablas con bordes ligeros o filas alternadas suaves.
- **No** usar layout admin (sidebar, Tailwind de app): HTML + `<style>` embebido como `sales-invoices/pdf.blade.php`.
- Una sola página si el contenido cabe; si no, salto de página natural (DomPDF).

### Qué NO incluir en v1.41.0

- QR AFIP, código de barras, CAE (no aplica al recibo de cobro).
- Logo corporativo **opcional** — solo si ya hay patrón reutilizable en otros PDF de ventas sin esfuerzo extra; si no, omitir.

---

## Permisos y seguridad (criterio UX)

- Misma regla que `CollectionReceiptController@show`: autorización y `company_id` activo.
- No exponer PDF sin autenticación.

---

## Entregables para el programador

1. Ruta + acción que devuelve PDF (DomPDF / `Barryvdh\DomPDF\Facade\Pdf` como en otros módulos).
2. Vista Blade dedicada `collection-receipts/pdf.blade.php` (o nombre acordado).
3. Botón en `collection-receipts/show.blade.php` alineado a FV.
4. Nombre de archivo descargable sugerido: `ReciboCobro_{numeroSanitizado}.pdf`.

---

## Checklist de aceptación (diseño)

- [ ] El usuario encuentra el PDF sin buscar en menús ocultos (misma zona que otras acciones del documento).
- [ ] El PDF contiene al menos: empresa, cliente, nº recibo, fecha, estado, tabla de pagos, retenciones si hay, facturas y total.
- [ ] Aspecto coherente con “documento para imprimir”, no con dashboard web.
