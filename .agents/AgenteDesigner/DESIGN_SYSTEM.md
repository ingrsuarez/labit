# DESIGN SYSTEM — Labit (Sistema de gestión para laboratorios)

> Referencia de componentes, estilos y patrones visuales disponibles en el proyecto.
> Fuente de verdad del AGENTE_DESIGNER al proponer o revisar interfaces.

---

## 🎨 Stack visual

| Tecnología | Rol | Notas |
|-----------|-----|-------|
| **Tailwind CSS 3** | Utilidades de estilos | + @tailwindcss/forms, @tailwindcss/typography |
| **Alpine.js** | Comportamiento JS liviano | Toggles, tooltips, dropdowns, x-data, x-show |
| **Livewire 3** | Componentes reactivos | Formularios, tablas dinámicas, interactividad compleja |
| **Bootstrap Icons** | Iconografía | `<i class="bi bi-nombre"></i>` |
| **Tom Select** | Dropdowns con búsqueda | Selects mejorados |
| **Chart.js** | Gráficos | Barras, líneas, tortas (reportes mensuales) |
| **Vite 4** | Build tool | Compilación de assets |

---

## 🏗️ Layouts disponibles

### Layout Admin (`resources/views/layouts/admin.blade.php`)
- **Sidebar** izquierdo con navegación por secciones (Lab, Ventas, Compras, RRHH, Calidad)
- **Header** top con nombre del usuario y acciones
- **Área de contenido** central
- **Uso:** todas las rutas del panel administrativo

### Layout Portal (`resources/views/layouts/portal.blade.php`)
- **Header** superior simplificado
- **Menú** reducido (solo funciones del empleado)
- **Uso:** todas las rutas `/portal/*`

---

## 🧱 Componentes de UI comunes

### Botones

```blade
<a href="{{ route('modulo.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition">
    <i class="bi bi-plus-lg me-1"></i> Nuevo
</a>

<button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
    <i class="bi bi-check-lg me-1"></i> Guardar
</button>

<button type="button" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
    <i class="bi bi-trash me-1"></i> Eliminar
</button>

<a href="{{ route('modulo.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
    <i class="bi bi-arrow-left me-1"></i> Volver
</a>
```

### Cards

```blade
<div class="bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Título</h3>
    {{-- Contenido --}}
</div>
```

### Tablas

```blade
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Columna</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach ($items as $item)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item->nombre }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
```

### Badges de estado

```blade
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Aprobado</span>
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Rechazado</span>
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pendiente</span>
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">En proceso</span>
```

### Alertas / Flash messages

```blade
@if (session('success'))
<div class="bg-green-50 border-l-4 border-green-400 p-4 mb-4">
    <p class="text-green-700">{{ session('success') }}</p>
</div>
@endif

@if (session('error'))
<div class="bg-red-50 border-l-4 border-red-400 p-4 mb-4">
    <p class="text-red-700">{{ session('error') }}</p>
</div>
@endif
```

### Formularios

```blade
<div class="mb-4">
    <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre</label>
    <input type="text" id="nombre" name="nombre" value="{{ old('nombre') }}"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
    @error('nombre') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
</div>
```

---

## 📐 Patrones de layout frecuentes

### Página con tabla + filtros (patrón más común)

```
┌─────────────────────────────────────────────────────┐
│  [Título del módulo]           [+ Nuevo botón]      │
│                                                     │
│  ┌──────────────────────────────────────────────┐   │
│  │ 🔍 Buscar...    [Filtro 1 ▼] [Filtro 2 ▼]   │   │
│  └──────────────────────────────────────────────┘   │
│                                                     │
│  ┌──────────────────────────────────────────────┐   │
│  │ Col A      │ Col B      │ Col C   │ Acciones │   │
│  │────────────┼────────────┼─────────┼──────────│   │
│  │ ...        │ ...        │ Badge   │ 👁 ✏️ 🗑️  │   │
│  └──────────────────────────────────────────────┘   │
│                                                     │
│  [ < 1 2 3 > ]   Mostrando 1-10 de 45 resultados   │
└─────────────────────────────────────────────────────┘
```

### Página de detalle (show)

```
┌─────────────────────────────────────────────────────┐
│  [← Volver]  [Título #NNN]        [Editar] [PDF]   │
│                                                     │
│  ┌─────────────────────┐  ┌────────────────────┐   │
│  │  Datos principales  │  │  Datos AFIP        │   │
│  │  Campo 1: valor     │  │  CAE: XXXXXX       │   │
│  │  Campo 2: valor     │  │  Vto: DD/MM/AAAA   │   │
│  └─────────────────────┘  └────────────────────┘   │
│                                                     │
│  ┌──────────────────────────────────────────────┐   │
│  │ Tabla de ítems / detalle                      │   │
│  └──────────────────────────────────────────────┘   │
│                                                     │
│  ┌──────────────────────────────────────────────┐   │
│  │ Totales                                       │   │
│  └──────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────┘
```

---

## 🎨 Paleta de colores

| Uso | Clase Tailwind | Descripción |
|-----|---------------|-------------|
| Acción primaria | `bg-indigo-600`, `hover:bg-indigo-700` | Botones principales |
| Éxito / Guardar | `bg-green-600`, `text-green-700` | Confirmaciones, estado aprobado |
| Peligro / Eliminar | `bg-red-600`, `text-red-700` | Acciones destructivas |
| Advertencia | `bg-yellow-100`, `text-yellow-800` | Alertas |
| Info / AFIP | `bg-indigo-50`, `border-indigo-500` | Datos electrónicos AFIP |
| Neutral | `bg-gray-200`, `text-gray-700` | Botones secundarios |
| Texto principal | `text-gray-900` | Títulos |
| Texto secundario | `text-gray-500` | Labels, metadata |
| Bordes | `border-gray-200` | Separadores, cards |
| Fondo | `bg-gray-50` o `bg-white` | Fondo del área de contenido |

---

## ✏️ Tipografía

| Elemento | Clases | Uso |
|----------|--------|-----|
| Título de página | `text-2xl font-semibold text-gray-900` | H1 de cada pantalla |
| Subtítulo | `text-lg font-medium text-gray-700` | H2 de sección |
| Label | `text-sm font-medium text-gray-700` | Labels de formulario |
| Texto body | `text-sm text-gray-600` | Contenido general |
| Texto pequeño | `text-xs text-gray-500` | Fechas, badges, helper text |

---

## 🔒 Permisos y condicionales de UI

```blade
@can('ventas.section')
    <a href="{{ route('sales-invoices.create') }}">Nueva factura</a>
@endcan

@can('credit-notes.create')
    <a href="{{ route('credit-notes.create', ['sales_invoice_id' => $invoice->id]) }}">Crear NC</a>
@endcan
```

**Regla:** Si un usuario no tiene permiso para una acción, el botón no debe aparecer en la UI.

---

## 📱 Responsive

El sistema es principalmente para **desktop** (app de gestión interna). Aun así:

- Las tablas deben ser horizontalmente scrolleables: `overflow-x-auto`
- Los formularios se apilan en mobile: `grid grid-cols-1 md:grid-cols-2`
- Los cards usan `grid grid-cols-1 lg:grid-cols-2` para adaptarse
