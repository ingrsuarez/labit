# Diseño: v1.19.0 — Consulta de padrón AFIP por CUIT

> Documento de diseño para el programador.
> Modificación de pantalla existente: formulario de crear/editar cliente.

---

## Propósito

Permitir al usuario consultar los datos fiscales de un CUIT contra el padrón de AFIP
directamente desde el formulario de cliente, autocompletando razón social, condición de IVA,
domicilio fiscal, actividad económica y estado del CUIT. Previene errores de facturación
electrónica por tipo de comprobante incorrecto (A vs B).

---

## Layout general

Se modifica el formulario existente de `customer/create.blade.php` y `customer/edit.blade.php`.
No se crea pantalla nueva. El layout admin con sidebar se mantiene.

---

## Cambios propuestos respecto a la versión actual

| Elemento | Estado actual | Propuesta |
|----------|--------------|-----------|
| Campo CUIT | Input solo, sin acción asociada | Input + botón "Consultar AFIP" a la derecha (inline) |
| Condición IVA | Dropdown manual editable | Dropdown que se autocompleta y queda readonly tras consulta AFIP, con botón "desbloquear" |
| Razón Social | Input manual | Se autocompleta desde AFIP (editable, el usuario puede corregir) |
| Dirección | Input manual | Se autocompleta desde domicilio fiscal AFIP |
| Ciudad | Input manual | Se autocompleta desde AFIP |
| Provincia | Select manual | Se autocompleta desde AFIP |
| Código Postal | Input manual | Se autocompleta desde AFIP |
| Actividad económica | No existe | Campo nuevo readonly, solo se llena desde AFIP |
| Estado CUIT | No existe | Badge visual nuevo debajo del campo CUIT |
| Feedback de consulta | No existe | Spinner durante consulta, mensajes de éxito/error |

---

## Secciones de la pantalla modificada

### Sección 1 — Campo CUIT con botón de consulta

```
┌─────────────────────────────────────────────────────────────┐
│  CUIT *                                                     │
│  ┌──────────────────────────┐  ┌──────────────────────┐    │
│  │  XX-XXXXXXXX-X           │  │ 🔍 Consultar AFIP    │    │
│  └──────────────────────────┘  └──────────────────────┘    │
│                                                             │
│  [Badge: ✅ CUIT Activo]  o  [Badge: ❌ CUIT Inactivo]     │
│  [Texto: "Actividad: Servicios de laboratorio..."]          │
└─────────────────────────────────────────────────────────────┘
```

- El botón "Consultar AFIP" va a la derecha del input de CUIT, en la misma línea.
- Estilo del botón: `bg-indigo-600 text-white hover:bg-indigo-700` (acción primaria del design system).
- Ícono: `bi bi-search` antes del texto.
- El botón se deshabilita si el CUIT tiene menos de 11 dígitos (sin guiones).
- Al hacer clic, el botón muestra spinner y texto "Consultando...".

### Sección 2 — Badge de estado CUIT

Aparece debajo del campo CUIT solo después de una consulta exitosa.

| Estado CUIT | Badge |
|-------------|-------|
| Activo | `bg-green-100 text-green-800` — "CUIT Activo" |
| Inactivo | `bg-red-100 text-red-800` — "CUIT Inactivo" |
| No encontrado | `bg-yellow-100 text-yellow-800` — "CUIT no encontrado en AFIP" |

Debajo del badge, en `text-xs text-gray-500`, mostrar la actividad económica principal.

### Sección 3 — Condición IVA con lock

```
┌─────────────────────────────────────────────────────────────┐
│  Condición IVA          [🔓 Verificado por AFIP]            │
│  ┌──────────────────────────────────────────────────┐       │
│  │  Responsable Inscripto                        🔒 │       │
│  └──────────────────────────────────────────────────┘       │
│  Texto: "Dato verificado por AFIP. Desbloquear edición."   │
└─────────────────────────────────────────────────────────────┘
```

- Después de consulta AFIP, el select se setea al valor correspondiente y queda `disabled`.
- Se agrega un `<input type="hidden">` con el mismo name para que se envíe en el POST.
- Badge `bg-indigo-50 text-indigo-700 border border-indigo-200` con texto "Verificado por AFIP" al lado del label.
- Link "Desbloquear edición" en `text-xs text-indigo-600 underline cursor-pointer` que remueve el disabled.
- Si el usuario desbloquea, el badge cambia a `bg-yellow-50 text-yellow-700` — "Editado manualmente".

### Sección 4 — Campos nuevos

Agregar en la grilla existente (después de Descuento):

| Campo | Tipo | Ubicación |
|-------|------|-----------|
| Actividad económica | Input text readonly | 1 columna, después de Descuento |
| Estado CUIT | No es campo de form — es badge visual bajo el CUIT | Bajo el input de CUIT |

---

## Estados de la interacción

| Estado | Qué mostrar |
|--------|-------------|
| Inicial (sin consulta) | Formulario normal, todos los campos editables, sin badges |
| Consultando | Botón con spinner + "Consultando...", campos deshabilitados temporalmente |
| Consulta exitosa | Campos autocompletados, badge verde, IVA bloqueado con badge "Verificado" |
| CUIT no encontrado | Badge amarillo bajo CUIT, campos quedan editables manualmente |
| Error de servicio AFIP | Alerta `bg-red-50 border-l-4 border-red-400` con "No se pudo conectar con AFIP. Podés cargar los datos manualmente." |
| Edición manual post-consulta | Si el usuario desbloquea IVA, badge cambia a "Editado manualmente" |

---

## Flujo de acciones

1. Usuario ingresa CUIT en el campo (formato libre: con o sin guiones)
2. Hace clic en "Consultar AFIP" (o el botón se habilita automáticamente al detectar 11 dígitos)
3. Spinner en el botón, campos grises momentáneamente
4. Respuesta de AFIP llega (~1-2 seg):
   - **Éxito:** Se autocompletan name, tax, address, city, state, postal, afip_activity. Badge verde. IVA bloqueado.
   - **No encontrado:** Badge amarillo. Campos quedan libres para carga manual.
   - **Error de conexión:** Alerta roja. Campos quedan libres.
5. Usuario revisa datos, ajusta lo que quiera (excepto IVA que está bloqueado).
6. Si necesita cambiar IVA, hace clic en "Desbloquear edición".
7. Guarda el formulario normalmente.

---

## Componentes Blade/Alpine a usar

| Componente | Origen | Uso |
|------------|--------|-----|
| Alpine.js `x-data` | Existente | Estado del componente de consulta AFIP |
| `fetch()` nativo | Existente | Llamada AJAX al endpoint de consulta |
| Badges del design system | Existente | Estado CUIT, verificación IVA |
| Spinner | Nuevo (inline SVG animado) | Dentro del botón durante consulta |
| Alerta `bg-red-50` | Existente | Error de conexión AFIP |

---

## Qué NO cambiar

- Layout general del formulario (grid 1/2/3 columnas)
- Campos de email, teléfono, país, descuento — no se tocan
- Estilo de botones del footer (Cancelar / Guardar)
- Validaciones server-side existentes
- Flujo de submit del formulario

---

## Justificación de decisiones

1. **Botón manual vs auto-consulta al blur:** Botón explícito porque la consulta a AFIP tiene latencia y no queremos disparar requests innecesarios mientras el usuario tipea. Además, el servicio tiene rate limiting.

2. **IVA bloqueado post-consulta:** Si AFIP dice que es Responsable Inscripto, no debería poder cambiarse a Monotributista manualmente — eso causaría el error que queremos prevenir. El "desbloquear" es un escape hatch para casos excepcionales.

3. **Campos autocompletados editables (excepto IVA):** La razón social o dirección de AFIP puede estar desactualizada o tener un formato diferente al que usa el sistema. El usuario debe poder corregir.

4. **Sin campo visible de actividad económica en la grilla principal:** Se muestra como texto descriptivo debajo del badge de CUIT para no agregar ruido al formulario. Se guarda en la DB pero es informativo.
