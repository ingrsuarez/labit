# CHANGELOG — Labit

> Historial de cambios por versión.
> Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/).

---

## [v1.76.0] — 2026-05-07 — Marcar determinaciones como ratificadas

### Agregado
- **Modelo de datos** en `admission_tests`, `vet_admission_tests` y `sample_determinations`:
  - `is_ratified` (bool, default `false`)
  - `ratified_at` (timestamp nullable)
  - `ratified_by` (FK a `users`, `onDelete set null`)
  - Relación Eloquent `ratifier()` en los tres modelos.
- **UI**: nueva columna/checkbox **Ratif.** en la tabla de carga de resultados de:
  - Lab clínico (`lab/admissions/show`)
  - Lab veterinario (`vet/admissions/show`)
  - Muestras (`sample/load-results`)
  Tooltip explicativo: *"Valor anormal/atípico — verificado por bioquímico"*. Solo editable por usuarios con permiso de validar (`lab-results.validate` / `samples.validate`).
- **PDF de informe y envío por email** (lab, vet, sample): asterisco junto al resultado ratificado y leyenda condicional al pie:
  *"\* Resultados marcados fueron revisados por el bioquímico ante valores atípicos."*
- **Tests** (`tests/Feature/RatifiedDeterminationsTest.php`): 5 tests Feature cubriendo persistencia, permisos, limpieza al desvalidar, y los tres módulos.

### UX
- El checkbox de Ratificado queda **editable también después de validar** la práctica, persistiéndose al hacer "Guardar Resultados" sin reabrir el resultado validado. Esto evita el flujo confuso de "marcar → validar → perder marca".

### Backend
- `LabAdmissionController::saveResult/saveResults` y `unvalidateTest`: aplican `is_ratified` solo si el usuario puede validar; al desvalidar, limpian la ratificación.
- `VetAdmissionController::loadResults` y `unvalidateTest`: misma lógica para veterinario.
- `SampleController::saveResults` y `validateDeterminations` (acción unvalidate): misma lógica para muestras (gated por `samples.validate`).

### Notas técnicas
- Migración obligatoria en deploy: `php artisan migrate` (SQLite skip de FK por compatibilidad de tests).
- En MySQL/MariaDB se crea la FK a `users(id)` con `onDelete set null`.

---

## [v1.73.0] — 2026-05-07 — Estado “enviado” en protocolos de muestras

### Agregado
- Columna `samples.sent_at`: al enviar por email o descargar PDF un protocolo **completamente validado**, se registra timestamp y el estado calculado pasa a **Enviado** (prioridad sobre “Validado”).
- Filtro **Enviado** en el listado de protocolos de muestras; badge celeste (`sky`).
- `Sample::isSent()` y limpieza de `sent_at` al revertir validación del protocolo.

### Notas técnicas
- Migración obligatoria en deploy: `php artisan migrate`.

---

## [v1.75.1] — 2026-05-06 — Hotfix: “Otros valores de referencia” en informes PDF

### Corregido
- **Laboratorio clínico, muestras y veterinario:** la columna de valores de referencia en los PDF (mPDF) ahora muestra correctamente el texto de **Otros valores de referencia** del catálogo cuando corresponde, incluyendo combinación con rangos numéricos (`App\Support\ProtocolReferenceDisplay`).
- **Veterinario:** el PDF solo imprimía `reference_value`; se unifica el criterio con clínico/muestras usando el mismo helper y `tests.other_reference`.
- **Carga de resultados (lab clínico):** la fila de valor de referencia en pantalla y el valor enviado al guardar incluyen `other_reference`, evitando “Sin ref.” cuando solo está cargado ahí.

### Notas técnicas
- Sin migraciones. Solo `git pull` en producción.

---

## [v1.67.3] — 2026-05-04 — Hotfix: orden de determinaciones en email veterinario

### Corregido
- **El PDF adjunto en el email de resultados veterinarios no respetaba el orden definido de las determinaciones** (jerarquía padre→sub-padre→hijo + `sort_order`). El informe descargado/visualizado desde la app sí lo respetaba.

### Causa raíz
- `VetAdmissionResultMail::attachments()` cargaba `vetTests` con un eager load filtrado a solo las validadas (`fn ($q) => $q->where('is_validated', true)`), mientras que `downloadPdf`/`viewPdf` cargaban todas sin filtro.
- `VetAdmissionTestDisplayOrder::orderedEntries()` necesita **todas** las determinaciones del protocolo para construir correctamente la jerarquía padre-hijo (tests padre que sirven como agrupadores estructurales pueden no estar validados pero son necesarios para que sus hijos se ordenen dentro del grupo). Al recibir solo las validadas, los padres no validados desaparecían de `$allProtocolTestIds`, los hijos perdían su grupo y caían como orphans a nivel 0 con un orden distinto.
- Adicionalmente, el cálculo del validador más frecuente (`validated_by`) no filtraba la colección a validadas, incluyendo nulls de tests no validados al contar.

### Solución
- Eager load de `vetTests` sin filtro, alineado a `downloadPdf`/`viewPdf`.
- Filtro `->where('is_validated', true)` en la colección para el cálculo del validador (mismo patrón que el controller).

### Notas técnicas
- Cambio de 2 líneas en `app/Mail/VetAdmissionResultMail.php`.
- El template PDF (`vet/admissions/pdf-mpdf.blade.php`) y `VetAdmissionTestDisplayOrder` no se modificaron — ya manejaban correctamente el filtrado interno.
- Mismo patrón de bug potencial existe en `SampleResultsMail` y `LabAdmissionResultMail` (candidatos a auditoría preventiva).

### Cómo aplicar en producción
Solo `git pull`. Sin migraciones, sin assets, sin seeders.

---

## [v1.67.0] — 2026-05-04 — API: catálogo de tests/determinaciones para LISCOM

### Agregado
- **Endpoint `GET /api/v1/tests?search=...`** para búsqueda en el catálogo de determinaciones de labit. Permite a LISCOM buscar tests por nombre o código para configurar los mapeos de códigos por equipo (`EquipmentTestMapping` de v1.49.0).
- **Filtro por categoría** (`?category=clinico|aguas_alimentos|veterinario`) para acotar la búsqueda al tipo de laboratorio relevante.
- **`TestController`** (`app/Http/Controllers/Api/V1/TestController.php`) con búsqueda LIKE por `name` y `code`, paginación configurable (max 100 por página), mínimo 2 caracteres de búsqueda.
- **`TestResource`** (`app/Http/Resources/Api/V1/TestResource.php`) con estructura completa: `id`, `code`, `name`, `unit`, `method`, `nbu`, `categories`, `is_parent`, `is_child`, `material` (con id/name/abbreviation).
- **12 Feature tests** en `tests/Feature/Api/V1/TestCatalogApiTest.php`: auth requerida, búsqueda por nombre/código, campos esperados, flags parent/child, filtro por categoría, paginación, cap de per_page, API key inactiva, material null.

### Notas técnicas
- Detrás de `auth.api_key` (v1.46.0), igual que los demás endpoints de la API v1.
- `is_parent` y `is_child` permiten a LISCOM distinguir tests agrupadores (padres/sub-padres) de determinaciones concretas para evitar mapear headers como tests reales.
- Eager loading de `materialRelation`, `childTests` y `parentTests` para evitar N+1.
- El endpoint no requiere migraciones ni permisos nuevos.

---

## [v1.66.5] — 2026-05-03 — UX: listado de Facturas de Venta más compacto y fila clickeable

### Cambiado
- **Listado de Facturas de Venta (`/sales-invoices`)** rediseñado para que la tabla quepa mejor en pantallas estándar y no requiera scroll horizontal:
  - **Columna "Acciones" eliminada.** Toda la fila es clickeable y navega al detalle de la factura. Soporta `Ctrl/Cmd+click` y click con la rueda del mouse para abrir en pestaña nueva. Los clicks sobre el `<a>` del N° de Factura siguen funcionando como links normales.
  - **Razón social del cliente truncada con wrap a 2 líneas** (`max-w-[180px]` + `line-clamp-2`). Para ver el nombre completo se puede pasar el mouse por encima (atributo `title`).
  - **Fechas vencidas** ahora se muestran solo en color rojo con tooltip "Vencida"; se quitó el badge "(vencida)" para ahorrar espacio.
  - **Headers más cortos**: "Fecha Emisión" → "Emisión", "Vencimiento" → "Vencim.".
  - Padding horizontal reducido de `px-4` a `px-3` y celdas alineadas al top (`align-top`) para que la fila no crezca verticalmente cuando la razón social hace wrap.
  - `cursor-pointer` y `transition-colors` en cada fila para reforzar la affordance de "clickeable".

### Notas técnicas
- La función global `rowClick(event, url)` está declarada al final del slot del Blade y sobrevive al reemplazo AJAX del `#results-container` que hace el filtro reactivo de búsqueda/estado/cliente.
- Se respeta el comportamiento estándar del navegador: si el click cae sobre un `<a>` o `<button>` interno (ej: el link del N° de Factura), se deja propagar normalmente y se evita la navegación duplicada.
- Sin cambios en `Controller`/`Model`/rutas. Solo Blade + 1 línea de JS.

### Cómo aplicar en producción
`git pull` y hard refresh (`Ctrl+F5`). El bundle de CSS cambió de hash (`app-a19a11fa.css` → `app-a2da10f9.css`) por la nueva utility `max-w-[180px]`, así que Vite hace cache busting automático.

---

## [v1.66.4] — 2026-05-03 — Hotfix: completar fix de scroll horizontal en `<x-admin-layout>`

### Corregido
- En `v1.66.2` se agregó `min-w-0` al `<main>` del layout admin para evitar que el contenido empuje el viewport. Pero **el padre directo del `<main>` (el contenedor `<div class="flex-1 flex flex-col md:ml-64">`) seguía sin `min-w-0`**, así que en pantallas con tablas o filas anchas (ej: el listado de Facturas de Venta con razones sociales largas en una sola línea con `whitespace-nowrap`) el contenedor se expandía y arrastraba todo el viewport.
- Resultado visible: la página `/sales-invoices` y otras con tablas anchas seguían mostrando barra de scroll horizontal a nivel de viewport y se veían cortadas a la derecha (filtros, columnas finales de la tabla, etc.).

### Solución
- Se agregó `min-w-0` también al wrapper del `<main>` en `resources/views/components/admin-layout.blade.php`. Ahora todos los flex items del path raíz → contenido tienen `min-width: 0`, así el `overflow-x-auto` que envuelve a las tablas funciona como corresponde y el scroll horizontal queda **dentro** de cada tabla, no a nivel viewport.
- Cambio mínimo (1 línea) y de bajo riesgo. Sin impacto en CSS bundle.

### Cómo aplicar en producción
Sólo `git pull` y hard refresh (`Ctrl+F5`).

> ⚠️ Nota importante: si aún ves el bug después del deploy, verificá que efectivamente hayas hecho `git pull` desde el último deploy en producción. Los fixes `v1.66.1`, `v1.66.2` y `v1.66.3` también son necesarios para que se vea bien todo el conjunto (paletas del dashboard + formularios sin overflow + listados sin overflow).

---

## [v1.66.3] — 2026-05-03 — Hotfix: scroll horizontal en Facturas de Venta (listado y formularios)

### Corregido
- **El listado `Facturas de Venta` (`/sales-invoices`) y los formularios `Nueva Factura de Venta` y `Editar Factura de Venta` mostraban barra de scroll horizontal y se cortaban del lado derecho** cuando había clientes con razones sociales largas (ej: "COLEGIO DE BIOQUIMICOS DE NEUQUEN ROISA DoctRed"). Mismo bug que `v1.66.2` pero del lado de venta: el `<select>` con la lista de clientes empujaba la grilla y el filtro fuera del viewport.

### Solución
- Se agregó `[&>div]:min-w-0` al grid de "Datos del Comprobante" en `create.blade.php` y `edit.blade.php`, y al row de filtros en `index.blade.php`.
- Se agregó `min-w-0` a los `<select>` de cliente y de status del listado.
- Vistas afectadas:
  - `resources/views/sales-invoices/index.blade.php`
  - `resources/views/sales-invoices/create.blade.php`
  - `resources/views/sales-invoices/edit.blade.php`
- Sin cambios en CSS bundle (las utilities `min-w-0` y `[&>div]:min-w-0` ya entraron al CSS en `v1.66.2`).

### Cómo aplicar en producción
Sólo `git pull`. No hay assets nuevos ni migraciones. Hard refresh del navegador si hace falta.

---

## [v1.66.2] — 2026-05-03 — Hotfix: scroll horizontal en formularios de Factura de Compra y Remito

### Corregido
- **Los formularios `Nueva Factura de Compra`, `Editar Factura de Compra`, `Nuevo Remito` y `Editar Remito` mostraban barra de scroll horizontal y se cortaban del lado derecho** cuando había proveedores con nombres largos. El campo "Proveedor" desbordaba la grilla y empujaba todo el formulario fuera del viewport.

### Causa raíz
- El campo Proveedor está dentro de un `<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">` y contiene un `<select class="flex-1 ...">` con las opciones de proveedores.
- Por defecto los `select` tienen `min-width: auto = min-content`, donde el `min-content` es el ancho de la opción más larga. Si un proveedor tiene un nombre con muchas palabras o muy largo, el select queda "rígidamente" ancho.
- Como las celdas de un grid `1fr` y los items flex tampoco se encogen por debajo de su `min-content` por defecto, todo el formulario terminaba ocupando más ancho que la pantalla.

### Solución
- Se agregó la utility `[&>div]:min-w-0` al grid de "Datos del Comprobante" y "Datos del Remito" para permitir que las celdas se encojan.
- Se agregó `min-w-0` al `<select name="supplier_id">` para que el flex item pueda achicarse.
- Defensivamente, se agregó `min-w-0` al `<main>` del componente `<x-admin-layout>` para que el área de contenido nunca empuje el viewport hacia los lados (problema típico de flexbox sin `min-width: 0`).
- Vistas afectadas:
  - `resources/views/purchase-invoices/create.blade.php`
  - `resources/views/purchase-invoices/edit.blade.php`
  - `resources/views/delivery-notes/create.blade.php`
  - `resources/views/delivery-notes/edit.blade.php`
  - `resources/views/components/admin-layout.blade.php`
- Rebuild de assets (`npm run build`) y commit de `public/build/*` al repo.

### Notas técnicas
- El truco `[&>div]:min-w-0` es una variante arbitraria de Tailwind v3 que aplica `min-width: 0` a todos los `<div>` que son hijos directos del elemento. Es la forma más limpia de "destrabar" todas las celdas del grid sin tener que tocar cada `<div>` individualmente.
- Este patrón es la fix recomendada en CSS para grid + flex anidado con contenido de ancho variable. Lección: cuando un select está dentro de `flex-1` y a su vez la celda de grid es `1fr`, hay que poner `min-w-0` en ambos lados de la cadena.

### Cómo aplicar en producción
Sólo `git pull`. El CSS regenerado ya está commiteado en `public/build/`. Hard refresh del navegador (`Ctrl+F5`) si el cliente cachea el CSS viejo.

---

## [v1.66.1] — 2026-05-03 — Hotfix: barras del dashboard financiero invisibles en producción

### Corregido
- **Las barras de los gráficos de los widgets Ventas, Ingresos y Egresos no se veían en producción** (mostraban un gráfico aparentemente vacío aunque los KPIs sí estaban bien). El bug era de **purge de Tailwind CSS**: las clases de color (`bg-emerald-400/600`, `bg-sky-400/600`, `bg-rose-400/600`) estaban definidas únicamente en `config/dashboard.php` y se inyectaban interpoladas en el componente Blade (`{{ $palette['bar'] }}`). Tailwind sólo escanea `resources/views/**/*.blade.php` y archivos similares, así que esas clases dinámicas no aparecían en el bundle final y las barras quedaban transparentes con altura > 0.

### Solución
- Se agregó `config/dashboard.php` a la lista de `content` en `tailwind.config.js`.
- Se agregó `safelist` explícita con todas las clases de las paletas de los 4 widgets (`bg-{emerald,amber,sky,rose}-{100,400,600}` y `text-{emerald,amber,sky,rose}-600`) para garantizar que se incluyan en el CSS final aunque alguien renombre la palette en el config.
- Rebuild de assets (`npm run build`) y commit de `public/build/*` al repo.

### Notas técnicas
- En desarrollo local el bug podía no reproducirse si Tailwind ya había cacheado el HTML compilado de `storage/framework/views/*.php` con las clases ya interpoladas (porque sí está en el `content` glob). Por eso pasó la review local y sólo apareció después de un deploy limpio.
- COMPRAS sí se veía (al menos las barritas) porque `bg-amber-400` y `bg-amber-600` ya existían literalmente en otros blades del proyecto y Tailwind las había incluido.
- Lección aprendida: cualquier clase de Tailwind que vaya por `config/*.php` o por DB debe ir explícitamente en el `safelist` del `tailwind.config.js`.

### Cómo aplicar en producción
Sólo hay que hacer `git pull` (los assets compilados están commiteados en `public/build/`). No hace falta correr `npm install` ni `npm run build` en el server. Si el navegador del cliente cachea el CSS viejo, hard refresh (`Ctrl+F5`).

---

## [v1.66.0] — 2026-05-03 — Dashboard ejecutivo financiero + reubicación del panel de RRHH

### Agregado
- **Dashboard ejecutivo financiero** en `/dashboard` con 4 widgets del mes corriente: Ventas netas, Compras netas, Ingresos y Egresos. Cada widget muestra:
  - KPI grande del mes en curso.
  - Variación porcentual vs mes anterior con flechas (▲ verde, ▼ rojo, gris si sin cambios, "—" si sin datos previos).
  - Gráfico de barras con la evolución de los últimos 12 meses, con la barra del mes corriente destacada.
  - Tooltip Alpine al hacer hover sobre cada barra mostrando el monto exacto.
- **Panel de Recursos Humanos** en nueva ruta `/rrhh` (`RrhhController@index`, vista `rrhh/index.blade.php`) — mudanza 1:1 del contenido que antes vivía en `/dashboard` (KPIs de empleados, gráficos, cumpleaños, solicitudes, top puestos, accesos rápidos).
- **Entrada nueva en sidebar admin** "Recursos Humanos" arriba de Personal/Ausencias/Liquidaciones, visible solo para roles `admin` y `contador`.
- **`FinancialDashboardService`** (`app/Services/FinancialDashboardService.php`) que calcula los 4 datasets, filtra por empresa activa (`active_company_id()`) y maneja casos borde de variación % (división por cero, cap a ±999%, ambos en cero, etc.).
- **Componente Blade `<x-financial-widget>`** (`resources/views/components/financial-widget.blade.php`) reutilizable para los 4 widgets, con paleta configurable.
- **`config/dashboard.php`** centraliza las paletas de los widgets (emerald/amber/sky/rose).
- **Documento de diseño** `docs/designs/DISEÑO_v1.66.0-dashboard-financiero-rrhh.md` (entregado por AGENTE_DESIGNER) con anatomía visual, estados, accesibilidad y QA checklist.
- **Tests:**
  - 13 tests Unit en `tests/Unit/Services/FinancialDashboardServiceTest.php` cubriendo cálculo de variación %, filtrado por empresa, exclusión de borradores AFIP sin CAE, exclusión de recibos/OP anuladas, estructura de la serie de 12 meses.
  - 8 tests Feature en `tests/Feature/Dashboard/FinancialDashboardTest.php` cubriendo redirects por rol, banner sin empresa activa y nombre de empresa visible.
  - 5 tests Feature en `tests/Feature/Rrhh/RrhhAccessTest.php` cubriendo control de acceso a `/rrhh` (admin/contador OK, otros roles 403).

### Cambiado
- **`DashboardController@index`** se reescribe: mantiene los redirects por rol existentes (recepcion-lab/tecnico-lab/bioquimico → laboratorio, compras → compras, ventas → ventas) y delega el cálculo financiero al `FinancialDashboardService`. Toda la lógica de RRHH se mudó al `RrhhController`.
- **Vista `dashboard.blade.php`** reescrita con header "Panel ejecutivo", badge de empresa activa, banner sutil cuando falta empresa y el grid 2x2 de los 4 widgets.

### Decisiones de diseño (resumen)
- **Sin librerías de gráficos externas** — barras hechas con `<div>` + Tailwind, igual que el patrón de "Contrataciones por mes" del dashboard original.
- **Paleta diferenciada por widget sin juicio de valor** — el color identifica el concepto, no marca "bueno/malo". El juicio lo da la variación % (verde sube, rojo baja, gris igual).
- **Cap de variación a ±999%** y manejo explícito de mes anterior = 0 → "sin datos previos" (en vez de +∞%).
- **Mudanza de RRHH 1:1** sin cambios funcionales — solo cambia el título de la pantalla y la ruta.

### Notas técnicas
- Multi-empresa: todas las queries filtran por `company_id = active_company_id()`. Si no hay empresa activa, el dashboard muestra ceros con un banner amarillo en la parte superior.
- Cross-database: el service usa `strftime('%Y-%m', ...)` en SQLite (suite de tests) y `DATE_FORMAT(..., '%Y-%m')` en MySQL/MariaDB (producción).
- Detectado durante la ejecución: el test pre-existente `Tests\Feature\Billing\BatchInvoiceDraftTest::send to afip from edit obtains cae` falla en aislamiento por un mock de AFIP que devuelve `null` en `cae`. NO está relacionado con v1.66.0 — venía roto desde v1.65.0/v1.65.x. Cuando ese test falla, deja una transacción colgada que cascadea "There is already an active transaction" al resto de la suite. **Recomendación:** abrir hotfix posterior para fixear el mock de AFIP en ese test específico.

---

## [v1.65.3] — 2026-05-02 — Hotfix: validar y mostrar resultados con valor cero en protocolos veterinarios

### Corregido
- **Determinaciones con resultado `0` no se podían validar en protocolos veterinarios** (ej. Basófilos = 0, Glucosa = 0). El sistema las trataba como "sin resultado" debido a checks falsy (`empty()`, `! $value`) sobre un campo string que recibe `"0"`. Como consecuencia:
  - El badge mostraba "Pendiente" en vez de "Completo".
  - No aparecía el botón ✓ para validar.
  - "Validar Todos" las saltaba.
  - No se incluían en el PDF (porque éste solo renderiza filas validadas).
- **`VetAdmissionTest::hasResult()`** cambiado de `! empty($this->result)` a `$this->result !== null && $this->result !== ''` (comparación estricta), permitiendo `"0"` y `0` como resultados válidos.
- **`VetAdmissionController::loadResults()`** reemplaza `! empty($data['result'])` por una variable `$hasResult` con comparación estricta para definir `status`, `analyzed_by` y `analyzed_at`.
- **Vista `vet/admissions/show.blade.php`** ahora usa `$vt->hasResult()` (en vez de `$vt->result` truthy) para decidir el badge "Completo" y la visibilidad del botón ✓.

### Cambiado
- **Botones de validar/desvalidar individuales en la vista veterinaria** dejan de usar formularios anidados (HTML inválido — el botón submiteaba el form padre `loadResults`). Ahora usan `<button type="button" onclick="vetSubmitAction(...)">` con un helper JS local que crea un form dinámico al `validateTest`/`unvalidateTest`. Mismo patrón que el módulo clínico (`submitAction`).

### Notas técnicas
- Causa raíz: semántica falsy de PHP. `empty("0") === true`, `! "0" === true`. La regla canónica para campos string que pueden contener `"0"` es comparar contra `null` y `''`.
- El PDF (`vet/admissions/pdf-mpdf.blade.php`) ya usaba `$vt->result ?? '-'` (null coalescing) que es correcto para `"0"`. Al destrabarse la validación, las filas con `0` ya aparecen automáticamente.
- Análogo al fix v1.19.2 (que resolvió lo mismo en lab clínico). El módulo veterinario tenía su propio `hasResult()` con la misma falla.
- Hotfix aplicado en rama `hotfix/vet-validacion-resultado-cero`, mergeado a develop y master en un solo camino.

---

## [v1.65.2] — 2026-05-02 — Fix PDF veterinario: excluir no validadas y corregir jerarquía

### Corregido
- **Determinaciones no validadas aparecían en el PDF veterinario** — `VetAdmissionTestDisplayOrder::orderedEntries()` tenía un paso de "orphans" que recolectaba TODAS las `VetAdmissionTest` no visitadas por el DFS, incluyendo las no validadas. Ahora, en modo PDF (`$hierarchyFromValidatedOnly = true`), los orphans se filtran a solo validados, alineando el comportamiento con los PDFs de lab clínico y muestras.
- **Prácticas no validadas aparecían fuera de su grupo padre** — consecuencia directa del bug anterior: al no participar en la construcción del grafo jerárquico (solo validadas armaban aristas), las no validadas caían como orphans a nivel 0 (standalone), fuera de su grupo padre. El fix elimina esas filas del PDF.

### Agregado
- **Defensa en profundidad en la vista PDF** (`vet/admissions/pdf-mpdf.blade.php`): `@continue` para entradas no validadas que no sean cabeceras de grupo (padre/sub-padre), como segunda barrera independiente del filtro en el servicio de orden.

### Notas técnicas
- El cambio es de 2 líneas en `VetAdmissionTestDisplayOrder` y 3 líneas en la vista Blade.
- Los padres y sub-padres siguen apareciendo como agrupadores incluso si no tienen resultado propio, siempre que tengan al menos un hijo validado (comportamiento idéntico a lab clínico y muestras).
- La vista `show` (pantalla de carga/validación) no se ve afectada: sigue usando `orderedEntries($this, false)` que incluye todas las filas.

---

## [v1.65.0] — 2026-04-27 — Borrador editable y líneas extras en facturación masiva

### Cambio de flujo (UX percibible)
La facturación masiva (`/billing/batch-preview`) **ya no emite la factura en línea**. El botón pasa a llamarse **"Crear borrador"** y crea una `SalesInvoice` con `status='pendiente'`, sin CAE y con `invoice_number='PENDIENTE-AFIP'` (para electrónicas), redirigiendo al `sales-invoices.edit` existente. Desde el edit, el usuario puede:

- Agregar **líneas libres** (descripción + cantidad + precio + IVA) sin `test_id` — útil para toma de muestra, flete, descuentos, otros conceptos no asociados a determinaciones de protocolos.
- Quitar o editar items individuales.
- Cambiar customer, PV, fecha, notas, percepciones.
- Apretar **"Enviar a AFIP"** (PV electrónico, reusa `sales-invoices.retry-afip`) o **"Confirmar"** (PV no electrónico).

### Agregado
- **Banner amarillo "BORRADOR — pendiente de envío a AFIP"** en `resources/views/sales-invoices/edit.blade.php` (visible solo para borradores electrónicos sin CAE; muestra el detalle de rechazo AFIP si lo hay).
- **Botón "Enviar a AFIP" / "Reintentar AFIP"** en `edit.blade.php`, en form separada hacia `sales-invoices.retry-afip`, con confirmación.
- **Soporte para líneas libres** (sin `test_id`) en el form del edit (ya existía la infraestructura del controller; se documenta y valida explícitamente en este release).
- **Filtro "Borradores AFIP"** en `/sales-invoices` con badge de cantidad (`afip_draft_count` pasado por el controller). Mutuamente excluyente con el filtro `status`.
- **Scope `SalesInvoice::afipDraft()`** + accessor `is_afip_draft` (`status='pendiente' AND is_electronic=true AND cae IS NULL`).
- **Tests Feature** en `tests/Feature/Billing/BatchInvoiceDraftTest.php` (5 casos: borrador sin AFIP, agregar línea libre, enviar a AFIP desde edit, filtro de listado, factura con CAE no editable).

### Cambiado
- **`BillingController::batchInvoice`**: se quita el bloque `if ($isElectronic) { AfipService::createVoucher(...) }`. Siempre se crea borrador y se redirige a `sales-invoices.edit` con flash success diferenciado por electrónico/manual.
- **`SalesInvoiceController::index`**: incluye `afip_draft_count` y aplica el scope cuando llega `?afip_draft=1`.
- **`SalesInvoiceController::update`**: para electrónicas en estado borrador (`is_afip_draft`), `invoice_number` pasa a `nullable|string` sin `unique`, evitando choques con el placeholder `PENDIENTE-AFIP` de varios borradores simultáneos.
- **`SalesInvoiceController::retryAfip`**: pasa de `new AfipService` a `app(AfipService::class)` (DI), habilitando mocking en tests.
- **`resources/views/billing/batch-preview.blade.php`**: subtítulo y label del botón actualizados ("Crear borrador" en vez de "Crear factura"; mensaje de confirmación menciona el siguiente paso editable).
- **`resources/views/sales-invoices/index.blade.php`**: nuevo toggle "Borradores AFIP" con Alpine.js, deshabilita el select `status` cuando está activo.
- **`resources/views/sales-invoices/edit.blade.php`**: título dinámico ("Editar borrador — pendiente AFIP"), `id="invoice-edit-form"`, "N° Factura" read-only para borradores electrónicos.

### Notas técnicas
- **Sin migraciones, sin modelos nuevos, sin permisos nuevos** — refactor + ajustes de UI sobre infraestructura existente.
- Reusa endpoints `sales-invoices.update` y `sales-invoices.retry-afip` (este último ya soportaba envío inicial; el gate `is_electronic && !cae` cubre tanto envío como reintento).
- Tests existentes que asumían redirect inmediato a `show` con CAE quedan obsoletos; el flujo masivo ahora redirige a `edit` y el CAE se obtiene en un segundo paso explícito.
- Decisión registrada como **DD-009** en `BLUEPRINT.md`.

---

## [v1.53.0] — 2026-04-18 — Dashboard de monitoreo de la API (ingesta de resultados desde LISCOM)

### Agregado
- **Dashboard `/admin/api-monitor`** (Livewire 3) con counters de batches/mensajes/rechazos con auto-refresh cada 10s.
- **`App\Services\Api\ApiMonitorService`** — lectura agregada de `result_batches` + `result_ingestions`. Usa contadores materializados de v1.51.0 (no agrega JSON en vivo).
- **5 componentes Livewire** (`App\Livewire\Api\Dashboard`, `BatchesList`, `BatchDetail`, `IngestionsList`, `IngestionDetail`) con filtros URL-persistidos vía `#[Url]`.
- **3 componentes Blade reusables** (`x-api-monitor.counter-card`, `x-api-monitor.health-badge`, `x-api-monitor.status-badge`).
- **Banner ALREADY_VALIDATED** visible para bioquímicos cuando hay rechazos del período. Drill-down al mensaje y protocolo real en labit.
- **Tabla de estado de sedes** con clasificación de salud (`healthy`/`idle`/`stale`/`inactive`/`never_used`) basada en `last_used_at` del ApiClient.
- **Comando `php artisan api:cleanup`** con `--dry-run`, `--days`, `--no-interaction`. Scheduling diario a las 03:00 en `Kernel.php`.
- **`config/api.php`** con `log_retention_days` configurable vía `API_LOG_RETENTION_DAYS` (default 90 días).
- **Migración de índices** sobre `result_ingestions` y `result_batches` para queries del dashboard.
- **20 tests Feature** pasando (17 dashboard + 3 cleanup).
- **`docs/operations/api-monitor.md`** — runbook operativo.
- **Entrada sidebar** con badge de rechazos ALREADY_VALIDATED del día (cacheado 60s).

### Decisión de permisos (tensión resuelta)
No existe permission `view-protocols` transversal. Se eligió **`lab-admissions.index`** como permission de acceso al dashboard porque es compartido por todos los roles del laboratorio (`bioquimico`, `tecnico-lab`, `recepcion-lab`). Sin permissions nuevos.

### Notas técnicas
- `getProtocolUrl()` resuelve la URL del protocolo buscando el modelo por `protocol_number` (query puntual solo en detalle de 1 ingestion, no en listados).
- Raw payload truncado de v1.51.0 (marker `_truncated`) se detecta visualmente en el detalle del batch.
- Los counters del dashboard leen de `result_batches.items_*` (campos materializados), no del JSON de `items_summary`. Performance garantizada incluso con muchos registros.

---

## [v1.51.0] — 2026-04-18 — Ingesta de resultados desde LISCOM (`POST /api/v1/results/batch`)

### Agregado
- **Endpoint `POST /api/v1/results/batch`** con autenticación API key (v1.46.0). Recibe resultados ya validados humanamente en LISCOM y los persiste contra `admission_tests`, `sample_determinations` y `vet_admission_tests`.
- **`App\Models\ResultBatch`** — cabecera de cada request batch. Contadores por estado, `raw_request` (truncado a 64KB). Idempotencia por `(api_client_id, external_batch_id)`.
- **`App\Models\ResultIngestion`** — detalle por mensaje (`hl7_control_id`). `items_summary` JSON con resultado de cada OBX. Idempotencia por `(api_client_id, hl7_control_id)`.
- **`App\Services\Api\ApiResultIngestionService`** — servicio central con lógica de: lookup de protocolo por prefijo (`C`/`A`/`V`), verificación de sede, regla ALREADY_VALIDATED, overwrite con log, idempotencia doble.
- **`App\Http\Requests\Api\IngestResultsBatchRequest`** — validación (UUID batch, max 500 ítems, `labit_test_id` existente en BD).
- **`App\Http\Controllers\Api\V1\ResultIngestionController`** — thin controller.
- **2 migraciones**: `result_batches` + `result_ingestions`.
- **Tests Feature** `tests/Feature/Api/V1/ResultIngestionBatchTest.php` (15 casos: 3 tipos felices, overwrite, ALREADY_VALIDATED, 2 idempotencias, 4 errores, mixed batch).
- **Doc API** `docs/api/v1/results.md`.

### Regla crítica implementada
Si `is_validated = true`, el ítem se rechaza con `ALREADY_VALIDATED`. El valor **nunca** se sobrescribe. Se devuelven `validated_at` + `validated_by_name` para que LISCOM lo marque y no reintente.

### Notas técnicas
- Prefijos de protocolo consistentes con `ProtocolType` enum de v1.47.0 (`C`/`A`/`V`, sin guión).
- Los modelos de determinación no usan trait `Auditable` → se optó por logging estructurado en canal `api` (overwrite, ALREADY_VALIDATED). La trazabilidad explícita vía `audit_logs` es tensión abierta.
- Endpoint individual `POST /api/v1/results` no implementado (fuera de scope).
- Notificación al bioquímico cuando llegan resultados por API: fuera de scope, planificar en v1.51.1 o v1.53.0.

---

## [v1.48.5] — 2026-04-18 — Formato extendido de barcode (preparación HL7)

### Cambiado
- **Etiquetas de protocolo** ahora generan barcodes con formato
  `{protocol_number}^{material_abbreviation}` (antes: solo `protocol_number`).
  Ej: `C-2026-001234^EDTA`.
- Habilita filtrado por material desde el lado del servidor HL7 (LISCOM, v1.49.0).
- Si el material no se puede determinar, fallback transparente al formato anterior.
- Aplicado en: laboratorio clínico (`LabAdmissionController::printLabel`) y muestras
  (`SampleController::printLabel`, Opción 3.A — primer material).

### Agregado
- `App\Services\BarcodeFormatService` — helper estático con método
  `forLabel(string, ?string): string`.
- Test Feature `tests/Feature/BarcodeFormatTest.php` (5 casos).

### Notas técnicas
- `VetAdmissionController` no genera etiquetas con barcode → sin cambios.
- **Tensión abierta:** `public/js/zebra-label-print.js` arma el ZPL usando
  `label.protocol_number` directamente; las impresoras Zebra imprimirán el formato
  anterior hasta que se resuelva en hotfix v1.48.5.1 (agregar `barcode_content` al
  JSON de `labelData()` y consumirlo en el JS).

---

## [v1.45.0] — 2026-04-13 — Eliminar cliente: reglas protocolos y facturación

### Cambiado
- `CustomerController::destroy`: bloqueo explícito solo por **protocolos** (muestras aguas/alimentos + `VetAdmission`) y **facturación** (facturas de venta, recibos de cobro, notas de crédito); mensajes de error agrupados en esos conceptos
- Texto de ayuda en `customer/edit` alineado a las mismas reglas

### Agregado
- Tests Feature `CustomerDestroyTest` (borrado OK, bloqueo por vet, muestra y factura)

---

## [v1.44.0] — 2026-04-12 — Nomenclador veterinario (hub Laboratorio Veterinario)

### Agregado
- Entrada **Nomenclador veterinario** en el hub `/lab/veterinario` y ruta `lab/veterinario/nomenclador` (`lab.section`)
- `TestController::indexVeterinary`: listado solo determinaciones con categoría **veterinario** y sin padres en pivote (`whereDoesntHave('parentTests')`), alineado al buscador de protocolos vet
- Reutiliza `resources/views/test/index.blade.php`: búsqueda AJAX, NBU, modales crear/editar, enlaces a **Valores de referencia** (`tests.reference-values.index`)
- `_context=vet_nomenclator` en crear/editar/eliminar para volver al nomenclador vet; sidebar resalta **Lab. Veterinario** en esa ruta
- Test Feature `VeterinaryNomenclatorTest`

---

## [v1.43.0] — 2026-04-12 — Precios protocolo veterinario: NBU veterinaria × NBU práctica

### Agregado
- Columna `customers.veterinary_nbu_value` (valor en $ de 1 NBU para clientes tipo veterinario)
- Formularios de cliente (crear/editar): campo visible cuando el tipo incluye **Veterinario**
- `VetAdmissionController::searchTests`: requiere `customer_id`; precio = `veterinary_nbu_value × tests.nbu` (redondeo 2 decimales)
- `VetAdmissionController::store`: recalcula precios en servidor con la misma fórmula
- Vista `vet/admissions/create`: búsqueda de prácticas solo con veterinaria elegida; muestra valor NBU; recalcula filas al cambiar veterinaria
- Tests Feature `VetAdmissionNbuPricingTest`

### Notas
- Tras desplegar: `php artisan migrate`

---

## [v1.42.0] — 2026-04-07 — Servicios de compra: catálogo, facturas y estadísticas

### Agregado
- Modelos y migración: `purchase_service_categories`, `purchase_services` (por empresa), `purchase_invoice_items.purchase_service_id`
- CRUD categorías y servicios de compra; permisos `purchase-service-categories.*` y `purchase-services.*` en `RolesAndPermissionsSeeder`
- Factura de compra: selector de servicio por categoría en alta/edición; columna en vista detalle; validación de pertenencia a la empresa del comprobante; sin impacto en stock cuando el ítem usa servicio catalogado
- Ruta `purchase-services/statistics`: totales por categoría y por servicio (FC no anuladas, rango de fechas, empresa activa)
- Enlaces en hub Compras y resaltado de sidebar

### Notas
- Tras desplegar: `php artisan migrate` y, si aplica, `php artisan db:seed --class=RolesAndPermissionsSeeder` + `php artisan permission:cache-reset`

---

## [v1.35.3] — 2026-04-05 — Selector de lotes en salidas manuales de stock

### Agregado
- `SupplyLotBalanceService`: saldo por lote desde `stock_movements` (entrada/salida con `lot_number`; ajustes no reparten por lote; filtrado por sede)
- `GET supplies/{supply}/available-lots` (JSON) para el formulario de movimientos
- En **salida** con insumo `tracks_lot`: selector de lotes con disponible; aviso y modo manual con confirmación si no hay buckets; validación servidor de cantidad vs disponible del lote

### Notas
- El consumo manual **recomienda** elegir lote existente cuando hay saldo calculado; la suma por lote puede no coincidir con el stock global tras ajustes sin trazabilidad por lote.

---

## [v1.38.0] — 2026-04-05 — Stock por sede: compras, remitos, FC y vistas

### Agregado
- Selector y validación de **sede / depósito** (`lab_branch_id`) en órdenes de compra, remitos, facturas de compra y movimientos de stock manuales; filtros por sede en índices correspondientes
- Remitos: precarga y coherencia de sede con OC vinculada; aceptación de remito usa siempre la sede del documento (`LabBranchResolver` / `SupplyStockService`)
- Facturas de compra: sede para impacto de stock; alineación con uno o varios remitos (misma sede)
- Vistas de insumos: desglose de stock **por sede** (listado y ficha)
- Tests en `StockByBranchTest`: FC sin remito en sede elegida; flujo OC → remito → aceptar; error si la sede del remito no coincide con la OC

### Corregido
- Migración `2026_04_05_160000_create_delivery_note_purchase_invoice_table`: **idempotente** si la tabla pivote `delivery_note_purchase_invoice` ya existía sin fila en `migrations`; backfill con `insertOrIgnore` para no duplicar filas (desbloquea la cadena hasta `2026_04_05_220000` y columnas `lab_branch_id`)

---

## [v1.38.2] — 2026-04-05 — Hotfix: validación `stock_received_at` vacío en remito

### Corregido
- Normalizar cadena vacía a `null` al aceptar o editar remito (evita error de validación `date` si se borra la fecha de recepción en stock)

---

## [v1.38.1] — 2026-04-05 — Hotfix: fecha de movimientos de stock desde remito y FC

### Agregado
- Columna `occurred_at` en `stock_movements` (backfill desde `created_at`); listado y filtros usan `COALESCE(occurred_at, created_at)`
- Columna `stock_received_at` en `delivery_notes`; al **aceptar remito**, campo **Fecha de recepción en stock** (por defecto = fecha del remito)
- En remito **aceptado**, edición permite ajustar la fecha de recepción en stock antes de resincronizar movimientos

### Modificado
- Entradas por remito (`accept`, `DeliveryNoteStockService::syncStockAfterUpdate`) y por factura de compra con stock usan `occurred_at` coherente con el documento

---

## [v1.37.0] — 2026-04-05 — Stock por sede (modelo, migraciones y movimientos)

### Agregado
- Tabla `supply_lab_branch_stock` y columna `lab_branch_id` en `stock_movements`, `purchase_orders`, `delivery_notes`, `purchase_invoices`
- Migración con backfill (sede central o primera activa) y saldos por insumo con stock positivo; `supplies.stock` como suma cache
- `SupplyLabBranchStock`, `LabBranchResolver`, `SupplyStockService` (entrada/salida/ajuste, reversión por referencia)
- Tests `StockByBranchTest`

### Modificado
- Aceptar remito, FC con stock, ajuste manual, `DeliveryNoteStockService` y borrado de FC usan stock por sede

---

## [v1.36.0] — 2026-04-05 — Múltiples remitos en factura de compra

### Agregado
- Tabla pivote `delivery_note_purchase_invoice` y migración que copia vínculos desde `purchase_invoices.delivery_note_id`
- Relación many-to-many en `PurchaseInvoice` y `DeliveryNote`; compatibilidad con `delivery_note_id` (primer remito)
- Endpoint `GET purchase-invoices/available-delivery-notes` y validación de remitos libres / proveedor / empresa
- Formularios crear/editar FC con varios remitos (chips, líneas por remito); vistas índice y detalle actualizadas
- Tests en `PurchaseInvoiceMultipleDeliveryNotesTest`

### Modificado
- Con remitos vinculados, los ítems de la FC fuerzan `updates_stock` en falso para evitar doble impacto de stock

---

## [v3.4.0] — 2026-04-05 — Libro Diario, Libro Mayor y asientos manuales

### Agregado
- `JournalEntryController`: listado con filtros (fechas, tipo automático/manual, búsqueda, número de asiento), alta/edición/baja de asientos **manuales** con partida doble; `sourcePresentation()` para enlaces al documento origen
- `AccountingLedgerController`: Libro Mayor por cuenta imputable con saldo inicial del período, movimientos del mes, saldo acumulado y enlace al Libro Diario por número/fecha
- Rutas `accounting.journal.*` (resource sin `show`) y `accounting.ledger`
- Vistas Blade: `accounting/journal` (índice con líneas expandibles, crear/editar con Alpine.js) y `accounting/ledger/index`
- Permisos `contabilidad.entries.edit`, `contabilidad.entries.delete`, `contabilidad.ledger.index` en `RolesAndPermissionsSeeder` (rol contador)
- Dashboard contable: tarjetas activas Libro Diario / Libro Mayor; estadísticas de asientos del mes y automáticos vs manuales (empresa activa)
- Tests `JournalAndLedgerTest`

### Notas
- Tras desplegar: `php artisan db:seed --class=RolesAndPermissionsSeeder` (y `php artisan permission:cache-reset` si aplica)

---

## [v3.3.0] — 2026-04-04 — Asientos automáticos desde transacciones

### Agregado
- `AccountingEntryService` con generación de asientos (`is_automatic = true`) desde factura de venta, nota de crédito, recibo de cobro, factura de compra y orden de pago; validación de cuentas del plan y balance debe/haber; resolución de cuenta de caja/banco según medio de pago
- `JournalEntry::deleteForSource()` para eliminar asientos vinculados al borrar el documento fuente (líneas en cascada por FK)
- Componente Blade `journal-entry-widget` y uso en vistas `show` de facturas de venta, notas de crédito, recibos de cobro, facturas de compra y órdenes de pago

### Modificado
- `SalesInvoiceController`: registro de asiento tras emisión (electrónica autorizada o no electrónica) y reintento AFIP; borrado de asiento en `destroy`
- `CreditNoteController`: registro tras `store` / AFIP con anti-duplicado; regla electrónica solo si `status === confirmada`; borrado en `destroy` y tras reintento exitoso
- `CollectionReceiptController`: asiento al **confirmar** el recibo (no en borrador); borrado de asiento en `destroy`
- `PurchaseInvoiceController`: asiento tras alta con ítems y stock; borrado en `destroy`
- `PaymentOrderController`: asiento al pasar la OP a estado **`pagada`** en `confirm()`

### Notas
- Si falta una cuenta contable requerida, no se crea el asiento y se registra advertencia en el log; la operación comercial sigue igual
- Recibos de cobro y órdenes de pago generan asiento en el momento en que el documento pasa a estado definitivo (confirmado / pagado), coherente con el flujo de borrador del proyecto

---

## [v1.35.0] — 2026-04-04 — Cuenta corriente de proveedores

### Agregado
- `SupplierStatementController` con métodos `index()` (HTML), `pdf()` (descarga mPDF) y privados `buildStatementData()` y `calculateOpenBalance()`
- Vista `suppliers/statement.blade.php`: formulario de filtros, encabezado de proveedor, tabla con columnas Fecha/Comprobante/Detalle/Debe/Haber/Saldo
- Vista `suppliers/statement-pdf.blade.php`: PDF con estilos inline para mPDF, encabezado empresa/proveedor/período, misma tabla
- Saldo inicial (movimientos previos al período) calculado automáticamente
- Saldo acumulado por fila con criterio contable de cuenta de pasivo (Haber - Debe)
- Badge **AC** (Acreedor) / **AD** (Deudor) en cada fila de saldo y en el saldo final
- Botón "Descargar PDF" visible solo cuando hay proveedor seleccionado
- Link "Cta. Cte. Proveedores" en sidebar de compras con ícono de gráfico
- Rutas `GET /suppliers/statement` y `GET /suppliers/statement/pdf` dentro del grupo `can:compras.section`

### Notas
- Movimientos incluidos: facturas de compra (Haber) y órdenes de pago con `status='pagada'` (Debe)
- Adaptado a campos reales del proyecto: `Supplier.tax_id` (no `cuit`), `PaymentOrder.status='pagada'` (no `'confirmed'`)
- PDF sin Tailwind — solo HTML con estilos inline para compatibilidad con mPDF
- Leyenda en pie de tabla: AC = Saldo Acreedor, AD = Saldo Deudor

---

## [v1.32.4] — 2026-04-04 — Editar y eliminar remitos con sincronización de stock

### Agregado
- Relaciones `purchaseInvoices()` (HasMany) y `hasPurchaseInvoice()` en modelo `DeliveryNote`
- `DeliveryNoteStockService` con métodos `reverseStockForDeletion()` y `syncStockAfterUpdate()`
- Botones "Editar" y "Eliminar" en listado de remitos, condicionados por existencia de FC asociada
- Botones deshabilitados con tooltip explicativo cuando el remito tiene FC asociada
- Banner informativo en vista `show` cuando el remito tiene FC asociada
- Flash `warning` en vista `show` para mensajes de redirección desde `edit()`

### Modificado
- `DeliveryNoteController::edit()`: reemplaza restricción de status por verificación de FC asociada
- `DeliveryNoteController::update()`: agrega guard de FC + sincronización de stock si remito aceptado
- `DeliveryNoteController::destroy()`: agrega guard de FC + reversión de stock si remito aceptado, envuelto en `DB::transaction()`
- `DeliveryNoteController::index()`: eager loading de `purchaseInvoices:id,delivery_note_id` para evitar N+1

### Notas
- Editar/eliminar solo permitido si el remito no tiene factura de compra asociada
- Si el remito está en estado `aceptado` (tiene movimientos de stock): al eliminar se revierte el stock; al editar se recrea
- Si el remito está en estado `pendiente`: la operación procede sin tocar movimientos de stock
- `StockMovement` usa `reference_type`/`reference_id` (polimórfico) con tipo `'entrada'` y razón `'compra'`

---

## [v1.32.2] — 2026-04-04 — Fix buscador inteligente de items en remitos

### Corregido
- Reemplazar `<select>` simple de insumos en remitos (create/edit) por combobox con búsqueda inteligente (patrón v1.32.0)
- Fix `overflow-x-auto` que recortaba el dropdown de búsqueda al abrir

### Agregado
- Búsqueda con debounce 300ms vía endpoint `supplies.search` (reutilizado, sin duplicación)
- Badge del insumo vinculado con código y botón desvincular (&times;)
- Tab flow: al seleccionar insumo el foco salta a "Cant. Recibida"
- Navegación por teclado (flechas, Enter, Escape, Tab) en el dropdown
- Pre-llenado correcto de `_supply_name` y `_supply_code` al editar remitos existentes
- Modal "Crear Insumo Nuevo" con stock 0 (el stock se actualiza al aceptar el remito)

### Notas
- El endpoint `supplies.search` ya existía de v1.32.0, no se crearon rutas nuevas
- El modal de crear insumo es análogo al de facturas de compra, adaptado para el contexto de remitos

---

## [v1.19.1] — 2026-03-29 — Fix deselección de padre en determinaciones

### Corregido
- Selector de padres en modales crear y editar: reemplazado `<select multiple>` por lista de checkboxes para permitir click simple de selección/deselección (antes requería Ctrl+Click)
- Función `filterParentOptions()` actualizada para operar sobre labels con `data-search` en vez de options de select
- Función `openEditModal()` actualizada para marcar/desmarcar checkboxes en vez de options

### Notas
- Sin cambios de backend: los checkboxes con `name="parent_ids[]"` envían el mismo formato que el select multiple
- UX mejorada: los usuarios ya no necesitan conocer el atajo Ctrl+Click para deseleccionar padres
- Buscador de padres funciona igual que antes, filtrando las opciones visibles

---

## [v1.19.0] — 2026-03-28 — Consulta de padrón AFIP por CUIT

### Agregado
- Método `consultarPadron(string $cuit)` en `AfipService` usando web service `ws_sr_padron_a5` (Padrón Alcance 5)
- Constantes WSDL para `ws_sr_padron_a5` (homologación y producción) en `AfipService`
- Mapeo de provincias AFIP (código numérico → nombre) y condición IVA desde impuestos inscriptos
- Parseo de domicilio fiscal, actividad económica principal y estado CUIT desde respuesta AFIP
- Migración: campos `afip_activity` (string), `cuit_status` (string), `afip_verified_at` (timestamp) en tabla `customers`
- Helper `isAfipVerified()` en modelo `Customer`
- Endpoint `GET /customers/consultar-cuit/{cuit}` para consulta AJAX con respuesta JSON
- Botón "Consultar AFIP" en formularios create/edit de cliente (Alpine.js, `bg-indigo-600`)
- Autocompletado de razón social, condición IVA, dirección, ciudad, provincia y código postal desde AFIP
- Badge de estado CUIT (verde activo / rojo inactivo) debajo del campo CUIT
- Actividad económica como texto descriptivo debajo del badge
- Condición IVA se bloquea tras verificación AFIP con badge "Verificado por AFIP" y opción "Desbloquear edición"
- Hidden inputs para `afip_activity`, `cuit_status`, `afip_verified_at` en ambos formularios

### Modificado
- `getTokenAuthorization()` refactorizado para aceptar parámetro `$service` (default `wsfe`), cache key incluye nombre del servicio
- `invalidateTokenCache()` acepta parámetro `$service` opcional
- Validación en `store()` y `update()` de `CustomerController` incluye nuevos campos AFIP

### Notas
- El servicio `ws_sr_padron_a5` debe estar habilitado en el portal de AFIP (Administración de Relaciones de Clave Fiscal) para que funcione
- En homologación, el certificado puede no tener acceso al padrón — el error se maneja gracefully
- La consulta solo se dispara con clic explícito del usuario (sin auto-consulta en blur/keyup) por rate limiting de AFIP
- Compatible con datos existentes: campos nuevos son nullable, formularios funcionan sin consulta AFIP

---

## [v1.17.0] — 2026-03-26 — Fix impresión de etiquetas Zebra

### Corregido
- Parseo de impresoras en `zebra-label-print.js`: soporta JSON (versiones modernas de Zebra Browser Print) y TSV (versiones legacy)
- `Content-Type` del `POST /write`: cambiado de `text/plain` a `application/json`
- URL de API: detección automática de puerto HTTP (9100) / HTTPS (9101) según `window.location.protocol`
- Host: cambiado de `127.0.0.1` a `localhost` para compatibilidad con certificado de Browser Print
- Mensajes de error mejorados: distingue "Browser Print no corriendo" de "error de comunicación con impresora"

### Agregado
- `authorize('samples-labels.print')` en `SampleController::labelData()` (faltaba)
- Auto-selección de impresora cuando hay una sola disponible
- Método `_parsePrintersFromText()` como fallback para parseo TSV

### Notas
- El ZPL y diseño de etiqueta (60×30mm, Code128) no se modificaron
- La impresora de producción es una Zebra GK420t conectada por USB

---

## [v2.7.0] — 2026-03-26 — Vista centralizada de auditoría

### Agregado
- `AuditController` con método `index` y filtros: usuario, acción, módulo, rango de fechas, búsqueda por texto en descripción
- Ruta `GET /audit` protegida con middleware `permission:auditoria.section`
- Permiso `auditoria.section` creado y asignado exclusivamente al rol `admin`
- Vista `audit/index.blade.php` con tabla paginada (50/página), barra de filtros, badges de color por acción, links a registros auditados
- Constantes `MODULE_NAMES` y `ACTION_LABELS` en modelo `AuditLog` para mapeo de nombres legibles y colores
- Accessors `module_name`, `action_label`, `action_color`, `auditable_url` en `AuditLog`
- Link "Auditoría" en sidebar admin con ícono, protegido por `@can('auditoria.section')`
- Estado vacío con mensaje contextual (sin datos vs sin resultados de filtros)
- Paginación con contador "Mostrando X–Y de Z registros" y persistencia de query params

### Notas
- Los logs de auth (login/logout/failed) aparecen sin link a registro (no tienen `auditable_type`)
- Cuando se agreguen nuevos módulos auditados, aparecen automáticamente en los filtros
- El mapeo `auditable_type` → URL verifica existencia del registro antes de generar el link

---

## [v2.5.0] — 2026-03-26 — Auditoría: infraestructura + módulo clínico

### Agregado
- Migración: tabla `audit_logs` con relación polimórfica (`auditable_type`/`auditable_id`)
- Modelo `AuditLog` con fillable, relaciones `user()` y `auditable()` (MorphTo)
- Trait `App\Traits\Auditable` reutilizable: `auditLogs()` y `logAudit(action, description)`
- Componente Blade `<x-audit-history>` con timeline visual, badges por acción, usuario, fecha relativa e IP
- `AuthAuditSubscriber` para eventos Login, Logout y Failed con registro automático
- Auditoría en `PatientController`: store (created), save_changes (updated)
- Auditoría en `LabAdmissionController`: store, update, saveResults, validateTest, unvalidateTest, validateAll, downloadPdf, viewPdf, sendEmail
- Auditoría en `SampleController`: store, update, saveResults, processValidation, revertValidation, downloadPdf, viewPdf, sendEmail
- Historial de actividad en vista `patient/edit.blade.php`, `lab/admissions/show.blade.php` y `sample/show.blade.php`

### Notas
- Nivel básico: solo descripción textual de la acción, sin diff campo-a-campo
- `user_name` se guarda como snapshot para legibilidad incluso si el usuario se elimina
- El componente `<x-audit-history>` es reutilizable para futuras entidades

---

## [v1.16.0] — 2026-03-26 — Planillas de trabajo diario del laboratorio

### Agregado
- Migración: tablas `worksheets` (name, type, created_by) y `worksheet_test` (sort_order) para plantillas de planillas de trabajo
- Modelo `Worksheet` con relación many-to-many a `Test` (via `worksheet_test`, con `sort_order`)
- `WorksheetController` con CRUD completo: index, create, store, edit, update, destroy
- Buscador AJAX de tests con filtrado por tipo: clínico (todos) y muestras (`categories` JSON `aguas_alimentos`)
- Vista `show` con filtros: fecha desde/hasta, rango de protocolo, checkboxes con/sin resultados
- Vista previa tabular en pantalla: N° protocolo, paciente/cliente, columnas de tests con resultados
- Generación de PDF landscape con mPDF: tabla con bordes para escritura manual, encabezados de tests como códigos/siglas
- Consulta adaptativa por tipo: `Admission`+`AdmissionTest` para clínico, `Sample`+`SampleDetermination` para muestras
- Link "Planillas de Trabajo" en sidebar del laboratorio con ícono clipboard

### Notas
- Las planillas son templates reutilizables: se crean una vez y se usan cambiando el filtro de fecha
- Cada planilla pertenece a un solo módulo (clínico O muestras)
- El `sort_order` de los tests determina el orden de las columnas en el PDF
- No se implementa carga de resultados desde la planilla — es solo visualización/impresión

---

## [v2.4.1] — 2026-03-26 — Hotfix redirect loop lab + condición Mi Portal

### Corregido
- `CheckSystemAccess`: usuarios lab (`recepcion-lab`, `tecnico-lab`, `bioquimico`) causaban redirect loop infinito (`ERR_TOO_MANY_REDIRECTS`) porque el middleware los redirigía a `lab.section.clinico` que está dentro del mismo grupo `check.access`. Ahora pasan directo y `DashboardController` maneja la redirección.
- Lab sidebar: link "Mi Portal" solo se muestra si el usuario tiene un Employee vinculado (`$user->employee`), no por tener el rol `empleado` solo. Evita link roto cuando no hay Employee asociado.

### Notas
- En producción, para que "Mi Portal" funcione, el User debe tener `user_id` en la tabla `employees`
- El rol `empleado` por sí solo no crea la asociación User→Employee

---

## [v2.4.0] — 2026-03-25 — Control de acceso por rol y redirección inteligente

### Agregado
- 4 permisos de sección: `personal.section`, `ausencias.section`, `liquidaciones.section`, `configuracion.section`
- Rol `contador` recibe `ausencias.section` y `liquidaciones.section`
- Grupos de roles en `CheckSystemAccess`: `adminCapableRoles`, `labOnlyRoles`, `portalOnlyRoles`
- Link "Laboratorio" en sidebar del portal para usuarios con roles lab
- Redirección inteligente en `DashboardController`: lab → lab, compras → compras, ventas → ventas

### Modificado
- Sidebar admin: Personal, Ausencias, Liquidaciones y Configuración protegidos con `@can`
- `routes/web.php`: rutas RRHH envueltas en `permission:personal.section`, ausencias en `permission:ausencias.section`, liquidaciones en `permission:liquidaciones.section`, config en `permission:configuracion.section`
- `CheckSystemAccess`: redirección por prioridad admin-capable > lab > portal > pending
- Lab sidebar: link "Administración" solo visible para roles admin-capable
- Portal sidebar: links "Panel Administrativo" y "Ir al Sistema" solo para roles admin-capable

### Notas
- Permisos granulares dentro de cada sección (CRUD) quedan para versión futura
- Compras y Ventas ya estaban protegidos por middleware — sin cambios
- Seeder idempotente: ejecutar de nuevo no duplica permisos

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
