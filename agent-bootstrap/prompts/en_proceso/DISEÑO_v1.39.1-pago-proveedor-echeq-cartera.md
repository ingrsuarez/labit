# Diseño UI — v1.39.1 OP con e-cheqs en cartera

**Modo:** mejora de órdenes de pago (Blade + Alpine).

## Flujo

1. **Tipo de liquidación** (radio): *Medio tradicional* (transferencia / cheque / efectivo) vs *E-cheqs en cartera*.
2. Si **tradicional**: mismos campos actuales; no se envían IDs de cartera.
3. Si **cartera**: se ocultan método/referencia tradicional; se muestra tabla de e-cheq disponibles (RC confirmado, libres) con checkbox; total seleccionado vs total OP debe coincidir para habilitar guardar.

## Show OP

- Bloque “E-cheqs entregados” si la OP reservó/pagó con cartera; listar nº, banco, vencimiento, importe, RC origen.

## Listado índice

- Leyenda “E-cheq cartera” cuando aplique (`payment_method` derivado o filas vinculadas).
