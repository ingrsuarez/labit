# Diseño UI — Columna Protocolo + sede (`/lab/admissions`)

**Pantalla:** `resources/views/lab/admissions/index.blade.php`  
**Modo:** mejora de pantalla existente  
**Fecha:** 2026-05-10  
**Problema:** El número de protocolo, badge **Fact.** y badge de **sede** en una sola línea alargaban la columna y la tabla completa.

---

## Decisión

| Elemento | Antes | Después |
|----------|--------|---------|
| Protocolo + Fact. + sede | Todo en flujo horizontal (`inline`) | **Línea 1:** enlace al protocolo + badge Fact. (si aplica). **Línea 2:** badge de sede (`inline-flex self-start`) o “Sin sede”. |
| Celda | `break-all` + `max-w` estrecho | **Sin** `max-w` en la celda; número de protocolo con **`whitespace-nowrap`** (el ID no se parte); sede sigue en segunda línea con `truncate` si hace falta. |
| Accesibilidad | Sin cambio semántico | El enlace sigue siendo el protocolo; la sede es información secundaria debajo. |

## Qué no cambia

- Colores de badges (teal link, verde Fact., azul sede, ámbar sin sede).
- Resto de columnas y filtros.

## Referencia visual

Patrón alineado a la columna **Paciente** (nombre + DNI en dos líneas): misma jerarquía “dato principal / dato secundario”.
