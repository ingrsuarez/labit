# Diseño UI — v1.39.0 Recibos de cobro: múltiples medios y e-cheq

**Versión:** v1.39.0  
**Pantalla:** `collection-receipts` create / edit / show  
**Modo:** mejora de flujo existente (Alpine + Blade, admin layout)

## Objetivos de UX

- Dejar claro que el cobro puede partirse en **varias líneas** que deben sumar el total de facturas seleccionadas.
- **E-cheq** solo captura datos de cartera (número, banco, vencimiento, importe); sin proveedor ni endoso.
- Evitar errores: el botón guardar deshabilitado si no cuadran totales; mensaje de ayuda visible.

## Estructura (create / edit)

1. **Datos generales** — Cliente, fecha, notas (sin método único en cabecera en UI nueva).
2. **Facturas pendientes** — Tabla actual (checkboxes + montos); pie “Total a cobrar”.
3. **Valores recibidos** (nueva sección, card blanca como el resto):
   - Título + botón “Agregar medio”.
   - Cada fila: selector **Tipo** (Efectivo / Transferencia / E-cheq).
   - Campos condicionales:
     - Efectivo: solo importe.
     - Transferencia: importe + select **Cuenta bancaria** (empresa activa).
     - E-cheq: importe + número + banco (texto) + fecha vencimiento.
   - Botón quitar fila (ícono o texto discreto).
   - Pie: **Total medios** vs **Total facturas**; si diferencia > 0,01 mostrar texto rojo y deshabilitar envío.

## Show / impresión

- Bloque “Medios de pago” con tabla: tipo, importe, detalle (cuenta bancaria o datos e-cheq).
- Si hay varias líneas, listar todas; no mostrar endoso.

## Accesibilidad y consistencia

- Labels explícitos; `required` solo en servidor; en cliente deshabilitar submit si no cuadra.
- Misma tipografía y bordes que secciones “Datos generales” y “Facturas pendientes”.
