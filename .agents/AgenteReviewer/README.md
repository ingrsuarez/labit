# 🔍 AgenteReviewer

Agente de revisión de código del proyecto Labit. Inspecciona diffs, detecta problemas de calidad, seguridad y consistencia, y genera un reporte antes del merge.

---

## Cómo invocar

### Por el humano

```
Lee .agents/AgenteReviewer/AGENTE_REVIEWER.md y revisá el último diff.
```

```
Lee .agents/AgenteReviewer/AGENTE_REVIEWER.md y revisá los cambios de la versión [vX.Y.Z].
```

### Por el AGENTE_CEO

```
[DELEGANDO A AGENTE_REVIEWER]
Lee .agents/AgenteReviewer/AGENTE_REVIEWER.md.
Scope: [diff de vX.Y.Z / rama feature/vX.Y.Z-slug].
Cuando termines, reportá el resultado al AGENTE_CEO.
```

---

## Qué produce

| Output | Descripción |
|--------|-------------|
| Reporte de revisión | Impreso en el chat con severidad: BLOCKER / WARNING / SUGGESTION |
| Prompts de fix críticos | `agent-bootstrap/prompts/pendientes/fix-*.md` si hay BLOCKERs |

---

## Diferencia con AgenteQA

| | AgenteQA | AgenteReviewer |
|---|---|---|
| **Revisa** | Comportamiento funcional | Código fuente |
| **Herramientas** | Tests automáticos + navegador | git diff + análisis estático |
| **Output** | Bugs funcionales | Problemas de calidad/seguridad |
| **Cuándo** | Después de cada versión | Antes de mergear |
