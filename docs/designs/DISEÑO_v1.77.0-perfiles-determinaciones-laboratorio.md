# Diseño: v1.77.0 — Perfiles globales de determinaciones por tipo de laboratorio

> Documento de diseño para **AGENTE_PROGRAMADOR** (contexto obligatorio antes de implementar).
> **Modo:** Nueva pantalla (ABM catálogo global) + extensión de flujos existentes (alta/edición de admisión en **tres** módulos: Lab clínico, Muestras aguas/alimentos, Lab veterinario).
> **Diseñado por:** AGENTE_DESIGNER.
> **Insumos:** Requerimiento funcional v1.77.0, `BLUEPRINT.md` (módulos Lab clínico / Lab muestras / Vet), `.agents/AgenteDesigner/DESIGN_SYSTEM.md`.

---

## Propósito

Permitir definir **perfiles reutilizables** (catálogo **sin empresa**) que agrupan determinaciones según el **tipo de laboratorio** (clínico | veterinario | aguas/alimentos), y **aplicar** uno o más perfiles al crear o editar una admisión/protocolo, **sumando** determinaciones ya cargadas **sin duplicar**. Las admisiones ya tomadas **no se alteran** si se elimina u oculta un perfil; la **auditoría** debe dejar traza explícita de qué perfiles se aplicaron (ids y nombres).

**Usuarios del ABM y de la aplicación en admisión:** roles **Administrador**, **Bioquímico**, **Recepción lab**, **Técnico lab** (según permisos que defina implementación; la UI asume el mismo criterio de acceso que el resto del laboratorio).

---

## Reglas de negocio con impacto en UI

| Regla | Implicación de diseño |
|--------|------------------------|
| Catálogo global (no por empresa) | En listado/alta no aparece selector de empresa; copy claro: “Catálogo del sistema” o “Perfiles globales”. |
| Un perfil = nombre + tipo de lab + lista de tests | Formulario en dos pasos visuales: identidad (nombre + tipo) + composición (lista de determinaciones). |
| En admisión: sumar sin duplicar | Tras “Aplicar”, mensaje que cuantifica **agregadas** vs **omitidas por duplicado** (no solo “éxito”). |
| En edición con resultados: no pisar filas ni valores | Bloque de perfiles y feedback **no** sugieren reemplazo masivo; copy: “Solo se agregan determinaciones faltantes”. |
| Eliminar perfil no revierte admisiones | Modal de confirmación que explique que **protocolos ya cargados no cambian**; opción **ocultar/inactivar** si el producto la define para evitar borrados. |
| Auditoría | Vista o panel “Historial de aplicaciones” en detalle de admisión **o** línea en timeline/bitácora con **ID + nombre** de cada perfil aplicado, usuario y fecha/hora. |

---

## Navegación y ubicación sugerida

- **ABM de perfiles:** subsección bajo **Admin** o **Lab → Configuración** (coherente con otros catálogos globales). Título sugerido: **“Perfiles de determinaciones”**.
- **Breadcrumb ejemplo:** `Inicio › Laboratorio › Perfiles de determinaciones`
- **Entrada sidebar:** una sola línea; el filtro por tipo de lab se resuelve **en la página** (tabs o pills), no como tres ítems de menú.

---

## 1. Listado + alta / edición / baja (u ocultación) de perfiles

### Layout general

- Misma plantilla que el patrón **tabla + filtros** del `DESIGN_SYSTEM.md` (`<x-admin-layout>`, card blanca, sombra suave).
- **Acción principal** (arriba a la derecha): **“Nuevo perfil”** (`bg-indigo-600`, icono `bi-plus-lg`).

### Filtrado o seccionado por tipo de laboratorio

**Recomendación (preferida):** **Tabs horizontales** bajo el título, con cuatro estados:

| Tab | Contenido del listado |
|-----|------------------------|
| Todos | Todos los perfiles (columna “Tipo” visible). |
| Clínico | Solo `clínico`. |
| Veterinario | Solo `veterinario`. |
| Aguas / alimentos | Solo `aguas/alimentos`. |

- En mobile, los tabs pueden convertirse en **select** “Tipo de laboratorio” para no romper el layout.
- **Alternativa aceptable:** un único listado con **filtro dropdown** “Tipo: Todos | …” alineado a la izquierda del buscador; menos escaneable que tabs pero más compacto.

### Barra de herramientas del listado

- **Búsqueda por texto** (nombre del perfil), debounce o submit según volumen.
- Opcional: orden por nombre asc/desc.

### Tabla de datos

| Columna | Contenido |
|---------|-----------|
| Nombre | Texto principal en negrita. |
| Tipo | Badge de color distinto por tipo (ej. `bg-blue-100` clínico, `bg-amber-100` vet, `bg-teal-100` aguas/alimentos). |
| Determinaciones | Número (“12 prácticas”) + link **“Ver”** o ícono que abre panel lateral/modal con lista compacta (código + nombre). |
| Actualizado | Fecha corta (si existe en datos). |
| Estado | Si hay **ocultación**: badge Activo / Inactivo. |
| Acciones | **Editar** (lápiz), **Eliminar** o **Desactivar** (según modelo). |

- **Paginación:** sí, coherente con resto del admin (ej. 15–25 por página).
- **Acciones por fila:** no más de 3 íconos visibles; overflow “⋯” solo si hace falta.

### Estados del listado

| Estado | Qué mostrar |
|--------|--------------|
| Cargando | Skeleton en filas de tabla o spinner centrado en el área de tabla. |
| Vacío (sin perfiles en ese tab) | Ilustración o icono `bi-folder2-open`, título “No hay perfiles para este tipo”, texto breve, CTA **“Crear perfil”** si el usuario puede crear. |
| Error de carga | Alert roja (`border-l-4 border-red-400`), mensaje amigable, botón **“Reintentar”**. |
| Con datos | Tabla estándar + paginación. |

### Alta / edición de perfil

**Layout:** formulario en **una columna** en mobile; en desktop **dos columnas** solo si mejora densidad (ej. nombre + tipo arriba; lista de tests abajo full width).

**Campos obligatorios:**

1. **Nombre del perfil** — input texto; placeholder ej. “Perfil chequeo básico”.
2. **Tipo de laboratorio** — select o radio group con las tres opciones; **inmutable en edición** si negocio lo exige (si es inmutable, campo deshabilitado + tooltip “El tipo no se puede cambiar para no invalidar referencias”).

**Composición — determinaciones:**

- **Tom Select** (o patrón ya usado en catálogos de tests) con **búsqueda multi-select**, filtrando automáticamente el catálogo del **mismo tipo de lab** que el perfil.
- Debajo: **tabla o lista ordenable** de ítems seleccionados (código, nombre, acción quitar).
- Si no hay ítems: texto de ayuda “Agregue determinaciones con el buscador”.
- **Validación:** al menos una determinación antes de guardar (mensaje bajo el bloque).

**Acciones del formulario:**

- **Guardar** (`bg-green-600`, `bi-check-lg`).
- **Cancelar** → vuelve al listado con confirmación solo si hay cambios sin guardar (modal estándar).

### Baja u ocultación

- **Eliminar:** modal de confirmación con:
  - Título: “¿Eliminar perfil [nombre]?”
  - Cuerpo: “Las admisiones que ya aplicaron este perfil **no se modifican**. Dejará de estar disponible para nuevas aplicaciones.”
  - Botones: **Cancelar** (secundario), **Eliminar** (rojo).
- Si el producto usa **desactivar** en lugar de borrar físico: copiar del modal debe hablar de “dejar de mostrar en nuevas admisiones” sin prometer borrado definitivo.

---

## 2. UX en crear / editar admisión (tres módulos)

**Alcance:** mismos principios en:

- Lab clínico — flujo actual de prácticas (`lab/admissions/create`, `edit`).
- Muestras aguas/alimentos — determinaciones en `sample/create`, `edit`.
- Lab veterinario — `vet/admissions/create`, `edit`.

### Ubicación del bloque “Perfiles”

- Colocar el bloque **encima** o **inmediatamente antes** del buscador actual de prácticas/determinaciones, dentro de la misma card/sección de “Prácticas” / “Determinaciones”, para que el usuario entienda el orden: **primero perfiles (paquetes), luego búsqueda fina**.

### Controles

1. **Selector múltiple de perfiles** compatibles con el **tipo de módulo** actual (solo perfiles del tipo clínico en admisión clínica, etc.). Etiqueta sugerida: **“Aplicar perfiles guardados”**.
2. Botón **“Aplicar perfiles”** (no auto-aplicar al elegir del dropdown, para evitar aplicaciones accidentales al navegar opciones).
3. Opcional pero recomendable: texto de ayuda una línea: “Las determinaciones ya cargadas no se duplican.”

### Feedback tras aplicar (crítico)

Tras la acción exitosa, mostrar **toast o alert verde** persistente hasta la siguiente navegación o 8–10 s, con contenido estructurado:

- **Perfiles aplicados:** chips con nombre (y opcionalmente id pequeño en tooltip para soporte).
- **Resumen numérico:** “Se agregaron **N** determinaciones. **M** ya estaban en el pedido y se omitieron.”
- Si N=0 y M>0: mensaje amarillo tipo info: “Todas las determinaciones de estos perfiles ya estaban cargadas.”

**No** limpiar el multi-select al aplicar: el usuario puede querer ver qué eligió o aplicar otro conjunto; sí ofrecer link **“Limpiar selección”** secundario.

### Edición con resultados cargados

- Banner informativo discreto (`bg-blue-50` o `border-l-4 border-blue-400`) si el protocolo ya tiene resultados: “Puede seguir aplicando perfiles: solo se **agregan** filas nuevas; no se modifican valores existentes.”
- Si una determinación del perfil ya existe pero **sin resultado**, el comportamiento es el mismo que alta (omitida por duplicado).

### Coherencia de términos por módulo

| Módulo | Término en UI para “determinación” |
|--------|-----------------------------------|
| Clínico | “Práctica” / “Prácticas” (como hoy en admisiones). |
| Muestras | “Determinación” (como en `sample/create`). |
| Veterinario | Alinear con vistas vet existentes (prácticas o determinaciones según copy actual del módulo). |

El bloque de perfiles puede titularse **“Perfiles de [prácticas|determinaciones]”** según módulo.

---

## 3. Estados vacíos, errores y confirmaciones

### Vacíos

| Contexto | Mensaje / acción |
|----------|------------------|
| No hay perfiles definidos para ese tipo de lab | En el bloque de admisión: empty state compacto “No hay perfiles configurados para este tipo de laboratorio” + link **“Gestionar perfiles”** si el rol tiene acceso al ABM. |
| Usuario sin permiso al ABM | Sin link; solo texto informativo. |

### Errores

| Situación | Tratamiento UI |
|-----------|----------------|
| Fallo red al aplicar perfiles | Alert roja; tabla de prácticas **sin cambios**; botón reintentar en la alerta. |
| Perfil eliminado/inactivo entre selección y aplicación | Mensaje claro: “El perfil X ya no está disponible”; refrescar lista de perfiles. |
| Conflicto de catálogo (test no disponible para obra social / convenio — si aplica solo clínico) | Listar en el feedback los ítems **no agregados** por motivo de negocio (sublista colapsable), además del contador N/M. |

### Confirmaciones

- Eliminar/desactivar perfil: modal (ver §1).
- Salir del formulario de perfil con cambios: “¿Descartar cambios?”
- No hace falta confirmación extra para **Aplicar perfiles** en admisión (la acción es aditiva y reversible quitando ítems manualmente si el usuario lo permite hoy).

---

## 4. Auditoría visible

**Objetivo:** registro claro con **ids y nombres** de perfiles aplicados.

**Propuesta de UI (elegir una en implementación; pueden combinarse):**

1. **Sección en vista detalle** del protocolo (`show`): subtítulo **“Perfiles aplicados”**, lista cronológica: fecha/hora, usuario, **nombre del perfil** (id en texto monoespaciado pequeño o tooltip “ID: 123”).
2. Si ya existe **bitácora / actividad** en la ficha, una línea de evento: “Aplicó perfiles: Perfil A (id), Perfil B (id)”.

**No** depender solo de logs server-side invisibles para el usuario operativo.

---

## 5. Componentes y patrones (`DESIGN_SYSTEM.md`)

- Layout: `layouts/admin`, cards `bg-white rounded-lg shadow`, tablas con `thead bg-gray-50`.
- Botones primarios/secundarios/peligro como en la guía.
- Badges por tipo de laboratorio y estados.
- Alerts flash éxito/error y border-l para errores inline.
- **Tom Select** para multi-select de perfiles y para armado del perfil en ABM.
- **Alpine.js:** chips de feedback, tabs responsive, modal de confirmación.
- **Livewire:** listados filtrados, aplicación de perfiles con respuesta N/M sin recargar página completa si el resto del formulario ya es reactivo.

---

## 6. Mockups

Complejidad **media** (nuevo bloque + ABM estándar). **No** se exige imagen generada si el programador sigue este documento y las vistas hermanas; opcional: wireframe de la sección “Perfiles” sobre `lab/admissions/create` para alinear marketing interno.

---

## 7. Checklist de entrega para programador

- [ ] Ruta y permisos del ABM alineados con roles admin / bioquímico / recepción / técnico lab.
- [ ] Tabs o filtro por tipo de lab en índice de perfiles.
- [ ] Create/edit perfil con multi-select de determinaciones filtrado por tipo.
- [ ] Bloque “Aplicar perfiles” en **create y edit** de los tres módulos; feedback N agregadas / M omitidas.
- [ ] Empty states y errores según §3.
- [ ] Modal eliminar/desactivar con copy de no reversión en admisiones pasadas.
- [ ] Auditoría visible con id + nombre en detalle (o bitácora).
- [ ] Copy consistente: “global”, “sin duplicar”, “no pisa resultados”.

---

**Fin del documento — v1.77.0 Perfiles globales de determinaciones**
