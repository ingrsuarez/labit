# HOTFIX v1.38.2 — ParseError en `/collection-receipts/create`

**VERSIÓN:** v1.38.2  
**TIPO:** hotfix (patch)  
**ESTADO:** aplicado en código (sesión 2026-04-05)

---

## Sesión de planificación (Agente PM)

**Problema:** `ParseError: Unclosed '[' does not match ')'` al ejecutar `resources/views/collection-receipts/create.blade.php` (aprox. línea 139), bloqueando GET `collection-receipts/create`.

**Causa raíz:** Expresión PHP muy anidada dentro de `@json(...)` (arrow functions + `mapWithKeys` + arrays), frágil para el parser y difícil de mantener.

**Alcance del hotfix:**

- Construir `invoicesByCustomer` y `preloadedInvoiceRows` en `CollectionReceiptController@create`.
- Vista: solo `@json($invoicesByCustomer)` y `@json($preloadedInvoiceRows)` sin lambdas inline.
- Filtro `company_id` en la consulta por cliente (consistente con facturas pendientes del mismo controlador).

**Fuera de alcance:** nuevas features de cobro (ver v1.39.0 planificado).

**Verificación:** `/collection-receipts/create` carga; cliente con facturas pendientes muestra la grilla correctamente.

**Commit sugerido:**

```bash
git add app/Http/Controllers/CollectionReceiptController.php resources/views/collection-receipts/create.blade.php
git commit -m "fix(collection-receipts): ParseError en create — JSON desde controlador"
git tag -a "v1.38.2" -m "Hotfix: RC create Blade parse + company_id en payload JS"
```

**Nota de versionado:** `v1.38.1` reservado en el proyecto para otro hotfix (p. ej. stock remito); este fix usa **v1.38.2**.
