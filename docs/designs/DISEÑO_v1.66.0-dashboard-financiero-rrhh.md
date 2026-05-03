# Diseño: v1.66.0 — Dashboard ejecutivo financiero + reubicación del panel de RRHH

> Documento de diseño para el programador.
> **Modo:** Pantalla nueva (dashboard financiero) + mudanza 1:1 (panel RRHH) + entrada de sidebar.
> **Diseñado por:** AGENTE_DESIGNER (invocado por AGENTE_PROGRAMADOR el 2026-05-03 ante ausencia de doc previo).
> **Insumos:** `agent-bootstrap/handoffs/v1.66.0-pm-to-dev.md`, `.agents/AgenteDesigner/DESIGN_SYSTEM.md`, vistas actuales (`resources/views/dashboard.blade.php`, `resources/views/admin/partials/sidebar.blade.php`).

> ⚠️ **Nota sobre ubicación:** El handoff PM mencionaba `docs/design/v1.66.0-...md`, pero la convención del proyecto (ver `docs/designs/DISEÑO_v1.19.0-...md`) usa `docs/designs/DISEÑO_*`. Se respeta la convención existente para mantener consistencia.

---

## Propósito

Reemplazar el contenido del dashboard principal (`/dashboard`) por un **panel ejecutivo financiero** con 4 widgets que muestran la actividad del mes corriente y su evolución a 12 meses (Ventas netas, Compras netas, Ingresos, Egresos).

El contenido actual de RRHH se muda íntegro a `/rrhh` con una entrada nueva en el sidebar admin.

**Usuarios:** `admin` y `contador`. Otros roles siguen redirigidos a sus secciones.

---

## Análisis del dashboard actual (estado de partida)

### ✅ Qué funciona bien (mantener como referencia visual)

| Elemento | Por qué se mantiene |
|---|---|
| Layout `<x-admin-layout>` con sidebar zinc + área de contenido `p-6 space-y-6` | Patrón establecido del proyecto |
| Cards con `bg-white rounded-xl shadow-sm border p-6` | Look moderno, ya es estándar de facto en este dashboard |
| Tipografía: `text-2xl font-bold` títulos, `text-sm font-medium text-gray-500` labels | Jerarquía clara y consistente |
| Patrón de barras de "Contrataciones por Mes" (líneas ~233-254 de `dashboard.blade.php`) | Construcción con divs+Tailwind sin librería externa, simple y performante |
| Header: `now()->format('d/m/Y H:i')` como timestamp visible | Da sensación de "datos frescos" |

### ⚠️ Qué necesita mejorar para el dashboard financiero

| Elemento | Problema |
|---|---|
| Densidad: el dashboard actual tiene 7 KPIs + 4 gráficos + 3 listas + accesos rápidos | Para el financiero: solo 4 widgets, mucho más respirado |
| Falta de jerarquía financiera vs RRHH | Mezcla dominios. Solución: separar `/dashboard` (financiero) y `/rrhh` |
| No hay variación temporal | Los KPIs son números estáticos. El financiero suma "% vs mes anterior" |

---

## Layout general — Dashboard financiero (`/dashboard`)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  [Sidebar admin]  │  Panel ejecutivo                       🏢 IPAC          │
│                   │  Resumen financiero de mayo 2026       Actualizado 18:30│
│                   │ ──────────────────────────────────────────────────────  │
│                   │                                                          │
│                   │  ┌──────────────────────┐  ┌──────────────────────┐    │
│                   │  │ 💰 VENTAS DEL MES    │  │ 🛒 COMPRAS DEL MES   │    │
│                   │  │ $1.250.000           │  │ $850.000              │    │
│                   │  │ ▲ +12.3% vs abril    │  │ ▼ -3.1% vs abril      │    │
│                   │  │                      │  │                       │    │
│                   │  │ ▁▂▃▅▇█▆▇█▆▇█        │  │ ▂▃▄▆▅▆▇▆█▇▆▇         │    │
│                   │  │ jun jul... abr may   │  │ jun jul... abr may    │    │
│                   │  └──────────────────────┘  └──────────────────────┘    │
│                   │                                                          │
│                   │  ┌──────────────────────┐  ┌──────────────────────┐    │
│                   │  │ 💵 INGRESOS DEL MES  │  │ 💸 EGRESOS DEL MES    │    │
│                   │  │ $1.100.000           │  │ $720.000              │    │
│                   │  │ ▲ +8.5% vs abril     │  │ — sin datos previos   │    │
│                   │  │                      │  │                       │    │
│                   │  │ ▁▂▃▄▅▆▇▆▇█▇█        │  │ ▁▁▂▃▄▅▆▇█             │    │
│                   │  │ jun jul... abr may   │  │ jun jul... abr may    │    │
│                   │  └──────────────────────┘  └──────────────────────┘    │
│                   │                                                          │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Header de la pantalla

```blade
<div class="flex flex-col md:flex-row md:items-start md:justify-between gap-2">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Panel ejecutivo</h1>
        <p class="text-gray-500 text-sm">
            Resumen financiero de {{ now()->locale('es')->isoFormat('MMMM YYYY') }}
        </p>
    </div>
    <div class="flex flex-col md:flex-row md:items-center gap-2 text-sm">
        @if(active_company())
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-100 text-gray-700 rounded-md text-xs font-medium">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            {{ active_company()->name }}
        </span>
        @endif
        <span class="text-xs text-gray-400">
            Actualizado {{ now()->format('H:i') }}
        </span>
    </div>
</div>
```

### Grid de widgets

```blade
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <x-financial-widget :data="$ventas" />
    <x-financial-widget :data="$compras" />
    <x-financial-widget :data="$ingresos" />
    <x-financial-widget :data="$egresos" />
</div>
```

| Breakpoint | Layout | Razón |
|---|---|---|
| `< 1024px` (mobile + tablet) | 1 columna, stack vertical | El sidebar ocupa 256px; en pantallas chicas no entran 2 widgets cómodos |
| `≥ 1024px` (desktop) | 2 columnas (2x2) | El widget mantiene proporción "card cuadrado" y se ven los 4 sin scroll |

---

## Componente — `<x-financial-widget>` (corazón del diseño)

Cada uno de los 4 widgets tiene **estructura idéntica**, varían solo el dato y la paleta. Por eso conviene un componente Blade reutilizable en `resources/views/components/financial-widget.blade.php`.

### Estructura visual

```
┌────────────────────────────────────────────┐
│ [icon] VENTAS DEL MES         mayo 2026    │  ← Header del widget
│                                            │
│ $1.250.000                                 │  ← KPI grande (text-3xl)
│ ▲ +12.3% vs abril                          │  ← Variación (text-sm)
│                                            │
│  ┌──────────────────────────────────────┐  │
│  │                                  ███ │  │
│  │                            ███ ███ ███│  │
│  │                       ███ ███ ███ ███│  │
│  │                  ███ ███ ███ ███ ███ │  │
│  │             ███ ███ ███ ███ ███ ███  │  │
│  │       ███ ███ ███ ███ ███ ███ ███    │  │  ← Gráfico de barras
│  │  ███ ███ ███ ███ ███ ███ ███ ███     │  │     12 meses, mes
│  └──────────────────────────────────────┘  │     corriente destacado
│   jun jul ago sep oct nov dic ene feb mar  │
│   abr may                                  │
└────────────────────────────────────────────┘
```

### Anatomía detallada del widget

```blade
@props(['data'])
@php
    // $data trae: label, monthly[], current_total, previous_total, variation_percent
    $palette = config('dashboard.financial.palettes')[$data['key']] ?? [];
    $maxValue = max(array_column($data['monthly'], 'value'), 1);
    $variation = $data['variation_percent'];
@endphp

<div class="bg-white rounded-xl shadow-sm border p-6"
     role="region"
     aria-label="{{ $data['label'] }} del mes: ${{ number_format($data['current_total'], 0, ',', '.') }}">

    {{-- 1. Header del widget --}}
    <div class="flex items-center justify-between mb-2">
        <div class="flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg flex items-center justify-center {{ $palette['icon_bg'] }}">
                <svg class="w-4 h-4 {{ $palette['icon_text'] }}" ...icon SVG según concepto...></svg>
            </span>
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                {{ $data['label'] }} del mes
            </h3>
        </div>
        <span class="text-xs text-gray-400">
            {{ now()->locale('es')->isoFormat('MMMM YYYY') }}
        </span>
    </div>

    {{-- 2. KPI grande --}}
    <p class="text-3xl font-bold text-gray-900 mb-1">
        ${{ number_format($data['current_total'], 0, ',', '.') }}
    </p>

    {{-- 3. Variación vs mes anterior --}}
    <div class="text-sm mb-4 h-5">
        @if($variation === null)
            <span class="text-gray-400">— sin datos previos</span>
        @elseif($variation > 0)
            <span class="text-emerald-600 font-medium">
                ▲ +{{ number_format($variation, 1, ',', '.') }}% vs {{ now()->subMonth()->locale('es')->isoFormat('MMMM') }}
            </span>
        @elseif($variation < 0)
            <span class="text-rose-600 font-medium">
                ▼ {{ number_format($variation, 1, ',', '.') }}% vs {{ now()->subMonth()->locale('es')->isoFormat('MMMM') }}
            </span>
        @else
            <span class="text-gray-500">Sin cambios vs {{ now()->subMonth()->locale('es')->isoFormat('MMMM') }}</span>
        @endif
    </div>

    {{-- 4. Gráfico de barras de 12 meses --}}
    <div class="flex items-end justify-between h-36 gap-1.5"
         x-data="{ hovered: null }">
        @foreach($data['monthly'] as $idx => $month)
            @php
                $altura = $maxValue > 0 ? ($month['value'] / $maxValue) * 100 : 0;
                $barColor = $month['is_current'] ? $palette['bar_current'] : $palette['bar'];
            @endphp
            <div class="flex-1 flex flex-col items-center group cursor-default"
                 @mouseenter="hovered = {{ $idx }}"
                 @mouseleave="hovered = null"
                 title="{{ $month['label'] }}: ${{ number_format($month['value'], 0, ',', '.') }}">

                {{-- Tooltip contextual al hacer hover (Alpine) --}}
                <div class="relative w-full flex flex-col items-end justify-end h-28">
                    <div x-show="hovered === {{ $idx }}"
                         x-transition.opacity
                         class="absolute -top-7 left-1/2 -translate-x-1/2 px-2 py-0.5 bg-gray-900 text-white text-[10px] rounded whitespace-nowrap z-10"
                         style="display: none;">
                        ${{ number_format($month['value'], 0, ',', '.') }}
                    </div>

                    <div class="w-full {{ $barColor }} rounded-t transition-all duration-300"
                         style="height: {{ max($altura, 2) }}%"></div>
                </div>

                <span class="text-[10px] text-gray-500 mt-1.5 transform -rotate-45 origin-top-left whitespace-nowrap"
                      :class="{{ $month['is_current'] ? "'font-semibold text-gray-700'" : "''" }}">
                    {{ $month['label'] }}
                </span>
            </div>
        @endforeach
    </div>
</div>
```

> **Nota sobre Alpine.js:** El proyecto ya lo usa en otras pantallas. Si no se quiere agregar Alpine al dashboard, el tooltip puede omitirse — el atributo `title` nativo del `<div>` ya da el valor al hover (menos lindo, pero accesible).

---

## Paleta de colores por widget

Cada widget tiene una identidad visual diferenciada. **Importante:** el color del widget identifica el concepto, NO carga juicio de valor (verde ≠ "bueno", rojo ≠ "alerta"). El valor lo da la variación %, no el widget.

| Concepto | Color base | Bar normal | Bar mes corriente | Icon bg | Icon text | Razonamiento |
|---|---|---|---|---|---|---|
| **Ventas** | emerald (verde) | `bg-emerald-400` | `bg-emerald-600` | `bg-emerald-100` | `text-emerald-600` | Lo que entra por facturación. Verde = "outcome" (resultado del esfuerzo comercial). |
| **Compras** | amber (ámbar) | `bg-amber-400` | `bg-amber-600` | `bg-amber-100` | `text-amber-600` | Lo que sale por facturación. Cálido pero no rojo de alerta. |
| **Ingresos** | sky (celeste) | `bg-sky-400` | `bg-sky-600` | `bg-sky-100` | `text-sky-600` | Cobranza efectiva (caja). Azul = "líquido" (cash). |
| **Egresos** | rose (rosa) | `bg-rose-400` | `bg-rose-600` | `bg-rose-100` | `text-rose-600` | Salida efectiva (caja). Rosa diferenciado del rojo alerta. |

**Color de la variación (independiente del widget):**
- `▲` aumento → `text-emerald-600` (verde)
- `▼` disminución → `text-rose-600` (rojo)
- Sin cambios → `text-gray-500`
- Sin datos previos → `text-gray-400`

> Por qué no cargar juicio de valor en compras/egresos: a primera vista parecería que "compras subiendo" = "malo" (más gasto). Pero en muchos contextos significa "más actividad" o "stock para crecer". No queremos alarmar al usuario por algo que puede ser positivo. Dejamos la lectura al criterio del admin.

### Configuración centralizada (sugerido)

Agregar en `config/dashboard.php`:

```php
return [
    'financial' => [
        'palettes' => [
            'ventas' => [
                'icon_bg' => 'bg-emerald-100',
                'icon_text' => 'text-emerald-600',
                'bar' => 'bg-emerald-400',
                'bar_current' => 'bg-emerald-600',
            ],
            'compras' => [
                'icon_bg' => 'bg-amber-100',
                'icon_text' => 'text-amber-600',
                'bar' => 'bg-amber-400',
                'bar_current' => 'bg-amber-600',
            ],
            'ingresos' => [
                'icon_bg' => 'bg-sky-100',
                'icon_text' => 'text-sky-600',
                'bar' => 'bg-sky-400',
                'bar_current' => 'bg-sky-600',
            ],
            'egresos' => [
                'icon_bg' => 'bg-rose-100',
                'icon_text' => 'text-rose-600',
                'bar' => 'bg-rose-400',
                'bar_current' => 'bg-rose-600',
            ],
        ],
    ],
];
```

Y en el `FinancialDashboardService`, devolver la `key` de la paleta en cada dataset:
```php
return [
    'key' => 'ventas',  // 'compras' | 'ingresos' | 'egresos'
    'label' => 'Ventas',
    ...
];
```

---

## Iconos por widget (Heroicons outline 24x24)

Coherente con el resto del sidebar y el dashboard actual, que usan SVGs Heroicons inline.

| Widget | Heroicon | Path SVG sugerido |
|---|---|---|
| **Ventas** | `receipt-percent` o `currency-dollar` | `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>` (recibo, ya usado en sidebar para "Ventas") |
| **Compras** | `shopping-cart` | `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>` (carrito, ya usado en sidebar para "Compras") |
| **Ingresos** | `arrow-down-on-square` (entrada) | `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>` (flecha hacia caja) |
| **Egresos** | `arrow-up-tray` (salida) | `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>` (flecha hacia arriba/saliendo) |

> Reusar los íconos de Ventas/Compras del sidebar (líneas 110-112 y 132-134 de `sidebar.blade.php`) refuerza la asociación visual.

---

## Estados de la pantalla

| Estado | Qué mostrar | Cuándo ocurre |
|---|---|---|
| **Datos completos** | 4 widgets con números, variaciones y barras llenas | Caso normal con histórico ≥ 2 meses |
| **Sin datos del mes corriente** | KPI = $0, variación = ▼ -100% (si mes anterior > 0) o "—" (si mes anterior = 0), barra del mes en valor mínimo (2px) | Mes recién empezado sin actividad cargada todavía |
| **Sin datos previos** (mes actual > 0, mes anterior = 0) | KPI con monto, variación = "— sin datos previos" en gris | Sistema recién instalado, primer mes de actividad |
| **Todo en cero** (mes actual = 0 y mes anterior = 0) | KPI = $0, variación = "Sin actividad" en gris, todas las barras al mínimo | Empresa sin movimientos |
| **Sin empresa activa** (`active_company_id() === null`) | Banner amarillo arriba: "Seleccioná una empresa desde el header para ver los datos." Los widgets siguen visibles con $0. | Usuario admin sin empresa seleccionada (caso poco común) |
| **Error al cargar dataset** | Widget con `—` en KPI y "Error al cargar datos" en variación, barras vacías. Log en `laravel.log` | Falla de DB o query lenta. NO debe romper toda la pantalla — cada widget falla independientemente |

### Caso especial — variación con valores absurdos

| Situación | Manejo |
|---|---|
| Variación > +999% | Mostrar `▲ >+999%` (evita números ridículos como `+45.000%`) |
| Variación < -999% | No aplica (mínimo posible es -100%) |
| División por cero (mes anterior = 0, mes actual > 0) | `null` → "— sin datos previos" |
| Ambos = 0 | `0%` → "Sin cambios" o "Sin actividad" |

---

## Sidebar admin — Entrada nueva "Recursos Humanos"

### Posición exacta

Insertar **arriba** del bloque actual de RRHH (Personal, Ausencias, Liquidaciones).

En `resources/views/admin/partials/sidebar.blade.php`, **antes de la línea 61** (`@can('personal.section')`):

```blade
@can('rrhh.dashboard')
<!-- Recursos Humanos (Dashboard del módulo) -->
<a href="{{ route('rrhh.index') }}"
   class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors
    {{ request()->routeIs('rrhh.*')
        ? 'bg-zinc-700 text-white'
        : 'text-zinc-300 hover:bg-zinc-700/50 hover:text-white' }}">
    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0M9 14a2 2 0 100-4 2 2 0 000 4zm0 0c-1.5 0-3 .5-3 2v1h6v-1c0-1.5-1.5-2-3-2z"/>
    </svg>
    Recursos Humanos
</a>
@endcan
```

### Decisiones de la entrada

| Decisión | Valor | Razón |
|---|---|---|
| **Posición** | Arriba de "Personal", "Ausencias", "Liquidaciones" | "Recursos Humanos" es el panel general del dominio; los otros 3 son módulos operativos. Visual: panel ▶ módulos. |
| **Ícono** | `id-card with person` Heroicons (carnet con persona) | Diferenciado del actual ícono de "Personal" (grupo de personas). Evoca "panel/registro de personas" sin colisionar. |
| **Permiso** | `rrhh.dashboard` (nuevo) o reusar el gate del actual `route('dashboard')` | Si crear permiso nuevo es overhead, reusar el mismo gate que protege `/dashboard` hoy (admin + contador). El Dev decide. |
| **Highlight activo** | `request()->routeIs('rrhh.*')` | Cubre `rrhh.index` y futuras subrutas si crece (ej: `rrhh.report`) |
| **Sin separador visual** entre la entrada nueva y "Personal" | El bloque RRHH (panel + 3 módulos) queda visualmente cohesionado | Un divider extra fragmentaría innecesariamente. El divider actual de la línea 59 (`border-t border-zinc-700`) ya separa este bloque del de Laboratorio. |

---

## Panel de RRHH (`/rrhh`) — Mudanza 1:1

### Cambios respecto al dashboard actual

| Elemento | Estado actual | Propuesta |
|---|---|---|
| Layout | `<x-admin-layout title="Dashboard">` | `<x-admin-layout title="Recursos Humanos">` |
| H1 | "Panel de Control" | "Panel de Recursos Humanos" |
| Subtítulo | "Resumen de gestión de recursos humanos" | **Sin cambios** (ya está bien) |
| Resto del contenido | KPIs, gráficos, listas, accesos rápidos | **Sin cambios — mudanza literal del HTML** |

### Qué NO cambiar

- Estructura de KPIs principales (4 cards superiores).
- Segunda fila gradient (Promedio Antigüedad / Costo Nómina / De Vacaciones Hoy).
- Gráficos (Empleados por Departamento, Ausencias por Tipo, Contrataciones por Mes, Distribución por Género).
- Listas inferiores (Próximos Cumpleaños, Solicitudes Pendientes, Top Puestos).
- Accesos rápidos en el footer.
- Toda la lógica del controller (mover íntegra a `RrhhController@index`).

### Implementación práctica

```bash
cp resources/views/dashboard.blade.php resources/views/rrhh/index.blade.php
```

Luego editar **solo dos líneas** en `rrhh/index.blade.php`:
```diff
- <x-admin-layout title="Dashboard">
+ <x-admin-layout title="Recursos Humanos">
...
- <h1 class="text-2xl font-bold text-gray-900">Panel de Control</h1>
+ <h1 class="text-2xl font-bold text-gray-900">Panel de Recursos Humanos</h1>
```

Nada más. La mudanza es deliberadamente conservadora.

---

## Responsive

| Breakpoint | Dashboard financiero | Panel RRHH |
|---|---|---|
| `< 768px` (mobile) | 1 columna, widgets stack vertical, gráfico mantiene 12 barras pero en alto reducido (h-28 → h-24) | Ya es responsive (sin cambios) |
| `768-1023px` (tablet) | 1 columna (los widgets quedan más anchos pero más cómodos que en 2 cols apretadas) | Ya es responsive |
| `≥ 1024px` (desktop) | 2 columnas (2x2) | Ya es responsive |

Nota: en mobile, las labels de las barras (rotación -45°) pueden solaparse. Mitigación: en mobile mostrar solo cada 2 meses (`@if($idx % 2 === 0)`) o reducir font-size a `text-[8px]`.

---

## Accesibilidad

| Aspecto | Implementación |
|---|---|
| **Contraste de texto** | Todos los `text-{color}-600` sobre `bg-white` pasan WCAG AA (≥4.5:1) |
| **Contraste de barras** | `bg-emerald-400` sobre `bg-white` ≈ 3:1 (suficiente para elemento gráfico, no texto) |
| **ARIA labels** | Cada widget tiene `role="region"` + `aria-label` con resumen verbal del KPI |
| **Tooltip de barras** | Atributo `title` nativo (lectores de pantalla lo leen) + tooltip Alpine visual |
| **Foco con teclado** | Las barras NO son interactivas (no son links). Si en v1.66.1 se agrega drilldown, sí necesitarán focus. |
| **Variación con flecha + texto** | "▲ +12.3%" no depende solo del color (alguien daltónico ve la flecha + el signo) |

---

## Restricciones técnicas (heredadas del handoff)

| Restricción | Valor |
|---|---|
| Librería de gráficos | **Sin librería externa**. Barras hechas con `<div>` + Tailwind, igual que "Contrataciones por Mes" actual. |
| Frontend | Tailwind v3 + Alpine.js (opcional para tooltip) |
| Backend | Laravel + Blade. Sin Livewire en esta versión. |
| Auto-refresh | NO. Render normal en cada request. |
| Performance | Cada widget = 1-2 queries con `groupBy(YEAR, MONTH)`. Total 6-8 queries en el index. Verificar índices según handoff sección 1. |
| Multi-empresa | Filtra por `active_company_id()` (handoff sección 2 cubre el caso de tablas sin `company_id` directo). |

---

## Componentes Blade a usar / crear

| Componente | Estado | Acción |
|---|---|---|
| `<x-admin-layout>` | Existe | Reusar en ambas vistas (`dashboard.blade.php` y `rrhh/index.blade.php`) |
| `<x-financial-widget>` | Crear | Nuevo en `resources/views/components/financial-widget.blade.php` (ver "Anatomía detallada del widget") |
| Patrón "barras de meses" | Existe (en dashboard.blade.php líneas 233-254) | Adaptar/reusar dentro del widget |

---

## Flujo de uso esperado

1. Admin/contador entra al sistema → ve `/dashboard` (financiero).
2. Identifica de un vistazo: ventas mes vs anterior, compras mes vs anterior, ingresos, egresos.
3. Si quiere detalle, va al módulo correspondiente (Ventas, Compras, Recibos, OP) desde el sidebar.
4. Para gestionar empleados/RRHH, va al sidebar → "Recursos Humanos" → ve el panel mudado.
5. Cambio de empresa activa (header global) → al recargar `/dashboard`, los 4 widgets reflejan la empresa seleccionada.

---

## Lo que el Dev NO debe hacer

- ❌ No agregar Chart.js u otra librería de gráficos.
- ❌ No agregar drilldown desde las barras (eso es v1.66.1).
- ❌ No agregar filtros de período personalizado (eso es v1.66.3).
- ❌ No tocar el contenido del panel de RRHH más allá del header.
- ❌ No cambiar los redirects por rol existentes en `DashboardController@index`.
- ❌ No mezclar Livewire — render Blade normal.
- ❌ No agregar export PDF/Excel.

---

## Checklist visual de aceptación (para QA en navegador)

- [ ] `/dashboard` muestra header "Panel ejecutivo" + subtítulo con mes corriente + badge de empresa
- [ ] 4 widgets visibles en grid 2x2 en desktop, 1 columna en mobile/tablet
- [ ] Cada widget tiene: icono color, label uppercase, KPI grande, variación con flecha, gráfico de barras
- [ ] Mes corriente del gráfico visiblemente destacado (color más saturado)
- [ ] Hover sobre cada barra muestra el monto exacto (tooltip)
- [ ] Variación: ▲ verde si >0, ▼ rojo si <0, gris si 0, "—" si sin datos previos
- [ ] Cambiar la empresa en el header global → al recargar, los 4 widgets cambian
- [ ] `/rrhh` muestra exactamente el contenido actual del dashboard de RRHH (KPIs, gráficos, listas, accesos rápidos)
- [ ] H1 de `/rrhh` dice "Panel de Recursos Humanos"
- [ ] Sidebar admin muestra entrada "Recursos Humanos" arriba de "Personal"
- [ ] Click en "Recursos Humanos" lleva a `/rrhh` y la entrada queda activa
- [ ] Roles `admin` y `contador` acceden a ambos dashboards
- [ ] Roles `recepcion-lab`, `bioquimico`, `compras`, `ventas` siguen siendo redirigidos a sus secciones (sin cambios respecto al actual)
- [ ] Mobile: layout no rompe, labels de barras no se superponen ilegiblemente
- [ ] Sin errores en consola del navegador

---

## Sugerencias para el prompt de Dev

El prompt actual `pendientes/v1.66.0-dashboard-financiero-rrhh.md` ya cubre la implementación. Solo añadir/ajustar respecto a este diseño:

1. **Componente `<x-financial-widget>`:** crear en `resources/views/components/financial-widget.blade.php` con la estructura definida en este doc.
2. **Config centralizado:** crear `config/dashboard.php` con paletas (sección "Configuración centralizada").
3. **Service devuelve `key`:** que `FinancialDashboardService` agregue `key => 'ventas|compras|ingresos|egresos'` al array de cada dataset, para que el componente busque la paleta.
4. **Permiso `rrhh.dashboard`:** decidir si crear nuevo o reusar el gate actual (recomendación: reusar el mismo que protege `/dashboard` hoy, para no inflar permisos).
5. **Iconos en widgets:** usar Heroicons inline (consistente con resto del proyecto), reusando los SVGs de Ventas/Compras del sidebar.

---

## Decisiones tomadas — fundamentos

| Decisión | Por qué |
|---|---|
| Componente Blade reutilizable `<x-financial-widget>` | Los 4 widgets son estructuralmente idénticos. DRY en la vista. |
| Color del widget = identidad, no juicio | Evitar falsos positivos visuales (ej: "rojo en compras = malo"). El juicio lo da la variación. |
| Variación SIEMPRE verde/rojo según signo | Patrón universal en dashboards (Stripe, Mercury, Linear). Predecible para el usuario. |
| Sidebar: "Recursos Humanos" arriba de "Personal" | Panel general del dominio antes que módulos operativos. Pirámide de detalle: dashboard ▶ submódulos. |
| Mudanza de RRHH 1:1 sin rediseño | El handoff lo pide explícitamente. Reduce alcance y riesgo. Si se quiere rediseñar RRHH, sale como versión nueva separada. |
| Sin Chart.js | Mantiene el bundle liviano y elimina una dependencia. El patrón de barras con `<div>` ya está validado en el dashboard actual. |
| Tooltip Alpine en hover | Mejora UX al ver montos exactos sin saturar la vista. Es opcional — si se quiere simplificar, queda solo el `title` nativo. |
| 12 meses históricos | Suficiente para ver estacionalidad. Más sería ruido. |
| 1 decimal en variación % | Evita números ridículos (`+12.345678%`) sin perder precisión útil. |
| Mes parcial sin advertencia visual | El usuario ya entiende que el mes en curso es parcial — la barra más corta lo comunica. Si surge confusión, se agrega tooltip "mes parcial al día N" en v1.66.1. |

---

## Próximos pasos

1. **Dev** retoma con `pendientes/v1.66.0-dashboard-financiero-rrhh.md`, lee este doc + el handoff PM, y ejecuta los 11 pasos del prompt.
2. **QA navegador** sigue el "Checklist visual de aceptación" de este doc.
3. **Versiones futuras** (v1.66.1+) en el ROADMAP están alineadas a posibles necesidades emergentes:
   - v1.66.1 — Drilldown desde barras
   - v1.66.2 — Más KPIs (tesorería, deudores, deuda a proveedores)
   - v1.66.3 — Filtros de período personalizado
   - v1.66.4 — Comparación interanual
   - v1.66.5 — Export PDF
