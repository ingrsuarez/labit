# HOTFIX v1.41.1 — Libro IVA: cambio de empresa sin error 405

**Tipo:** patch / hotfix  
**Fecha:** 2026-04-07  
**Problema:** Tras abrir la vista previa del Libro IVA (`POST libro-iva/preview`), la URL del navegador queda en `/libro-iva/preview`. Al cambiar de empresa, `CompanyController@switchCompany` hacía `redirect()->back()`, que generaba un **GET** a esa URL; la ruta solo acepta **POST** → `MethodNotAllowedHttpException`.

**Solución implementada:**

1. `app/Http/Controllers/CompanyController.php`: si `url()->previous()` apunta a path que termina en `/libro-iva/preview` o `/libro-iva/download`, redirigir a `route('libro-iva.index')` con mensaje flash `success`.
2. `resources/views/libro-iva/index.blade.php`: mostrar `@if(session('success'))` coherente con otras pantallas admin.

**Verificación manual:** Libro IVA → Ver resumen del período → cambiar empresa en el header → debe volver al índice del Libro IVA con mensaje, sin error.

**Nota PM:** Mismo patrón puede afectar otras pantallas “solo POST” con URL visible; si aparece otro caso, extender lista de paths o valorar `GET` idempotente con query params para preview.
