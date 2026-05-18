# Diseño: v1.103.0 — Determinaciones calculadas por fórmula

> Documento de diseño para **AGENTE_PROGRAMADOR** (contexto obligatorio antes de implementar).
> **Modo:** Extensión de pantallas existentes (nomenclador + carga de resultados en tres módulos).
> **Diseñado por:** AGENTE_DESIGNER (invocado desde sesión PM).
> **Insumos:** Decisiones de planificación v1.103.0, `test/index.blade.php` (modales create/edit), Livewire `lab-admission-results-table` / `vet-admission-results-table`, `sample/load-results.blade.php`, `DESIGN_SYSTEM.md`.

---

## Propósito

Permitir definir en el **nomenclador** una práctica cuyo resultado se obtiene por **fórmula aritmética** entre otras determinaciones del catálogo (ej. **Índice de Castelli** = Colesterol total ÷ HDL). En la **carga de resultados del protocolo** (lab clínico, veterinario, muestras), el campo se **autocompleta en vivo** cuando todos los operandos del protocolo tienen valor numérico válido; el campo sigue siendo **editable**, pero **cualquier cambio en un operando recalcula y reemplaza** el valor mostrado (el cálculo manda).

**Usuarios:** Administrador / bioquímico (configuración en nomenclador); recepción, técnico y bioquímico (carga y validación de resultados).

---

## Reglas de negocio con impacto en UI

| Regla | Implicación de diseño |
|--------|------------------------|
| Recálculo siempre pisa valor manual | No mostrar toggle “bloquear cálculo”; copy de ayuda: “Se actualiza al cambiar las prácticas de la fórmula”. |
| Operandos faltantes, no numéricos o ÷0 | Campo calculado **vacío**; sin modal de error bloqueante. |
| Decimales | Mostrar según `decimals` de la práctica calculada; default 2. |
| Práctica calculada debe estar en el protocolo | No UI de auto-agregar ni sugerencias al armar pedido. |
| Ingesta LISCOM/equipos | Sin feedback en pantalla de ingesta; en nomenclador, nota: “Los equipos no deben enviar resultado para esta práctica”. |
| Solo operandos del mismo protocolo | En runtime no se muestran operandos externos; vacío hasta que existan en la grilla. |
| Operadores permitidos | `+`, `−`, `×`, `÷`, paréntesis; sin funciones (`sqrt`, `IF`, etc.). |

---

## 1. Nomenclador — Editor de fórmula (modal crear / editar)

### Ubicación

Bloque nuevo **ancho completo** (`md:col-span-2`) en los modales `#modal-create` y `#modal-edit` de `resources/views/test/index.blade.php`, **después** del checkbox “Exenta si queda vacía” y **antes** de “Análisis Padres” (o al final de la grilla si mejora el scroll).

También replicar el mismo bloque en el flujo de nomenclador veterinario (`$vetNomenclator`) con mismos patrones visuales (acento `amber` solo en botones primarios del módulo vet si aplica; el bloque de fórmula puede mantener neutro `teal/gray`).

### Layout del bloque

```
┌─────────────────────────────────────────────────────────────┐
│ Determinación calculada                          [toggle]   │
│ Cuando está activa, el resultado se obtiene por fórmula…    │
├─────────────────────────────────────────────────────────────┤
│ [ Solo visible si toggle ON ]                               │
│                                                             │
│ Agregar a la fórmula:                                       │
│ [ Buscar determinación… ▼ Tom Select / combobox ]           │
│ [ ( ] [ ) ] [ + ] [ − ] [ × ] [ ÷ ]                         │
│                                                             │
│ Expresión:                                                  │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ [COL-T] ÷ [HDL]                              (chips)    │ │
│ └─────────────────────────────────────────────────────────┘ │
│ Vista legible: Colesterol total ÷ HDL                       │
│                                                             │
│ Operandos usados:                                           │
│ • COL-T — Colesterol total   [quitar]                       │
│ • HDL — HDL                  [quitar]                       │
│                                                             │
│ ⚠ Una práctica con fórmula no debe recibir resultados       │
│   desde equipos (LISCOM).                                   │
└─────────────────────────────────────────────────────────────┘
```

### Toggle principal

- **Label:** “Determinación calculada”
- **Subtexto:** “El resultado se calcula automáticamente en el protocolo a partir de otras prácticas.”
- **OFF (default):** ocultar editor; al guardar, fórmula = null.
- **ON:** mostrar editor; validar expresión no vacía y al menos un operando referenciado.

### Selector de determinaciones (operandos)

- **Patrón:** Tom Select o combobox existente en el proyecto (misma UX que búsqueda de padres en el mismo modal).
- **Búsqueda:** por código y nombre.
- **Al elegir:** insertar **token/chip** en la expresión (no solo listar abajo). Representación visual: pill `bg-teal-100 text-teal-800` con código, tooltip con nombre completo.
- **Excluir de la lista:** la propia determinación en edición; opcionalmente prácticas que ya son “solo padre-título” sin resultado (implementación: preferir **solo hojas / sin hijos** si el backend lo distingue fácil; si no, permitir cualquier test y documentar que padres-título sin valor numérico dejan la fórmula vacía).
- **No permitir** seleccionar la misma práctica calculada en cadena (validación al guardar).

### Barra de operadores

Botones tipo **teclado calculadora**, tamaño compacto:

| Botón | Inserta |
|-------|---------|
| `(` `)` | Paréntesis |
| `+` `−` `×` `÷` | Operadores (guardar internamente como `+`, `-`, `*`, `/`) |

- Estilo: `px-3 py-1.5 border border-gray-300 rounded-lg text-sm font-mono hover:bg-gray-50`.
- **Accesibilidad:** `aria-label` en cada botón (“Agregar división”, etc.).

### Área de expresión

- **Contenedor:** borde `border-gray-300 rounded-lg p-3 min-h-[3rem]`, fondo `bg-gray-50`.
- **Contenido:** secuencia de chips (determinaciones) y texto de operadores/números literales si el producto los permite en v1 (**recomendación v1:** solo determinaciones + operadores; **constantes numéricas fuera de scope** salvo que PM lo abra después).
- **Edición:** permitir **Retroceso** para quitar último token; botón “Limpiar expresión” secundario (`text-sm text-gray-500`) con confirmación si hay contenido.
- **Vista legible** debajo (solo lectura): nombres de prácticas en lugar de códigos, ej. `Colesterol total ÷ HDL`.

### Lista “Operandos usados”

- Tabla compacta o lista con código, nombre, acción **Quitar** (elimina todas las apariciones del token en la expresión o solo de la lista — preferir quitar token de la expresión).

### Validación en formulario (mensajes bajo el bloque)

| Caso | Mensaje |
|------|---------|
| Toggle ON sin tokens | “Defina al menos una práctica en la fórmula.” |
| Paréntesis desbalanceados | “Revise los paréntesis de la expresión.” |
| Expresión termina en operador | “La expresión no puede terminar en un operador.” |
| Referencia circular | “La fórmula no puede incluir esta misma determinación.” |

### Estados del bloque (nomenclador)

| Estado | Qué mostrar |
|--------|-------------|
| Toggle OFF | Solo toggle + una línea de ayuda. |
| Toggle ON, sin expresión | Editor vacío + placeholder en área: “Agregue prácticas y operadores”. |
| Toggle ON, expresión válida | Chips + vista legible + operandos listados. |
| Error de validación | `text-red-600 text-sm` bajo el bloque; borde del área en `border-red-500`. |

### Listado de determinaciones (tabla índice)

- Columna opcional **“Calc.”** con ícono `bi-calculator` o badge **“Calculada”** (`bg-violet-100 text-violet-800`) si tiene fórmula definida.
- Tooltip en fila: vista legible corta de la fórmula.

---

## 2. Carga de resultados — Lab clínico y Veterinario (Livewire)

### Pantallas

- `resources/views/livewire/lab/lab-admission-results-table.blade.php`
- `resources/views/livewire/vet/vet-admission-results-table.blade.php`

### Indicador en fila (práctica calculada)

Junto al nombre de la práctica (misma celda que código/nombre), **después** del nombre:

- Ícono `bi-calculator` (`text-violet-600`), `title="Resultado calculado"`.
- Opcional badge pequeño **“Calc.”** solo en desktop; en mobile solo ícono.

### Campo resultado

- **Mismo input** `type="text"` que el resto (consistencia con decimales locales `,` / `.`).
- **Clases adicionales** cuando la práctica tiene fórmula y el valor proviene del motor en sesión: borde sutil `border-violet-300` o fondo `bg-violet-50/50` (diferenciar sin parecer disabled).
- **No** usar `readonly`: el usuario puede tipear; al cambiar un operando, Livewire/Alpine **sobrescribe** con el nuevo calculado.
- **Placeholder** cuando vacío por operandos incompletos: `—` o “Sin calcular” (`text-gray-400`), no un error rojo.

### Comportamiento en tiempo real

1. Usuario carga o modifica resultado en **operando** (fila hoja).
2. Para cada fila con fórmula en el protocolo, el cliente (Livewire `updated` o listener Alpine en el mismo formulario) recalcula.
3. Si evaluable → escribe valor formateado con `decimals`.
4. Si no evaluable → limpia el input de la calculada.
5. Al **guardar** protocolo, persiste el valor que esté en el input (calculado o el último tipeado antes de un recálculo).

### Jerarquía padre-hijo

- Las prácticas **calculadas** suelen ser **hojas** (sin hijos). Si una calculada fuera padre-título, seguir regla actual: fila padre sin input de resultado.
- Operandos pueden ser hijos bajo un padre (ej. HDL bajo perfil lipídico); el recálculo usa el `test_id` de la fila hoja en el protocolo.

### Validación / estados de protocolo

- Misma regla que cualquier práctica: si la calculada está vacía y **no** es `empty_result_exempt`, cuenta para pendiente/completo según `ProtocolStatusCalculator` actual.
- Copy en ayuda contextual (tooltip del ícono calculadora): “Se completa cuando todas las prácticas de la fórmula tienen resultado numérico en este protocolo.”

---

## 3. Carga de resultados — Muestras (`sample/load-results`)

### Consistencia

Replicar **mismos indicadores** (ícono calculadora, borde/fondo violeta suave, placeholder vacío) en la grilla de determinaciones de `resources/views/sample/load-results.blade.php`.

### Implementación sugerida (para el dev, no código aquí)

- Alpine.js o script existente del formulario: mapa `test_id → valor` al `input`/`change` de cada fila; función de evaluación compartida con el backend (misma regla de redondeo).
- Al submit, mismos valores que en pantalla.

---

## 4. Flujos de usuario

### Configurar Índice de Castelli (admin)

1. Nomenclador → Nueva / Editar determinación.
2. Activar “Determinación calculada”.
3. Buscar “Colesterol total” → inserta chip.
4. Pulsar `÷`.
5. Buscar “HDL” → inserta chip.
6. Ver vista legible “Colesterol total ÷ HDL”.
7. Guardar.

### Cargar protocolo (bioquímico)

1. Protocolo con Colesterol total, HDL e Índice de Castelli pedidos.
2. Ingresa Colesterol total → Castelli sigue vacío.
3. Ingresa HDL → Castelli se completa con valor redondeado.
4. Cambia Colesterol total → Castelli se actualiza al instante.
5. Edita Castelli manualmente a “5,0” → si luego cambia HDL, el valor pasa a ser el recalculado (regla acordada).

---

## 5. Componentes y estilo (DESIGN_SYSTEM)

| Elemento | Patrón |
|----------|--------|
| Modales nomenclador | Existente: `max-w-2xl`, scroll `max-h-[90vh]` — considerar `max-w-3xl` solo si el editor queda apretado. |
| Toggle | Mismo estilo que checkbox “Exenta si queda vacía”. |
| Chips / tokens | Pills Tailwind `rounded-full px-2 py-0.5 text-xs font-medium`. |
| Botones operador | Secundarios grises, `font-mono`. |
| Acento calculada | Violeta (`violet-100` / `violet-600`) para no confundir con padre-título (`teal`) ni validado (`green`). |
| Iconografía | Bootstrap Icons `bi-calculator`. |

---

## 6. Qué NO diseñar en esta versión

- Pantalla aparte “Editor de fórmulas”; todo vive en el modal existente.
- Sugerencias al armar admisión (“¿Agregar Castelli?”).
- Historial de “quién pisó el cálculo manual”.
- Constantes numéricas en la fórmula (ej. `× 100`).
- Funciones estadísticas o condicionales.

---

## 7. Criterios de aceptación visual (checklist QA)

- [ ] Toggle OFF no muestra editor; guardar no deja fórmula residual.
- [ ] Chips y operadores se ven en create y edit; vista legible coincide con tokens.
- [ ] Listado nomenclador marca prácticas calculadas.
- [ ] Lab clínico, vet y muestras: ícono calculadora en filas con fórmula.
- [ ] Recálculo al cambiar operando sin recargar página (lab/vet Livewire; muestras JS).
- [ ] Campo vacío si falta operando o ÷0, sin alert bloqueante.
- [ ] Decimales respetan configuración de la práctica calculada.
- [ ] Input calculado no parece deshabilitado pero se distingue visualmente.

---

## Referencia para el programador

- Decisiones PM: recálculo siempre pisa manual; tres módulos; vacío si inválido; LISCOM ignora; no auto-alta en protocolo.
- Archivo técnico de fórmula: reemplazar uso de `tests.formula` string libre por estructura validada (JSON) + expresión legible para UI/PDF.
- Evaluador compartido: backend (ingesta + guardado) y frontend (tiempo real).
