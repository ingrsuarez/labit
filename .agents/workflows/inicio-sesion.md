---
description: Inicio de sesión de cualquier agente — carga reglas y skills antes de trabajar
---

# Workflow: Inicio de sesión del agente

Este workflow debe ejecutarse **al comienzo de cada conversación** antes de realizar cualquier tarea.

## Paso 1 — Leer las reglas globales

Leer el archivo de reglas obligatorias del proyecto:

```
@.agents/REGLAS_AGENTES.md
```

> Estas reglas aplican a todos los agentes sin excepción. Interiorizarlas antes de continuar.

## Paso 2 — Revisar las skills disponibles

Listar las skills instaladas en `.agents/skills/`:

```
@.agents/skills/
```

Identificar cuáles son relevantes para la tarea que el usuario pidió.

## Paso 3 — Leer las skills relevantes

Para **cada skill relevante** a la tarea actual, leer su `SKILL.md` completo:

```
@.agents/skills/[nombre-skill]/SKILL.md
```

> Si la tarea involucra múltiples áreas (ej.: diseño + Vue + responsive), leer **todas** las skills aplicables antes de escribir una sola línea de código.

## Paso 4 — Confirmar contexto y proceder

Una vez leídas las reglas y las skills aplicables, el agente está listo para ejecutar la tarea solicitada, siguiendo las instrucciones de cada skill al pie de la letra.
