# 🤖 AgenteProgramador

Agente de ejecución del proyecto Labit. Toma prompts de la cola (`agent-bootstrap/prompts/pendientes/`), los implementa con verificación técnica, y los commitea con Gitflow.

---

## Cómo invocar

```
Lee .agents/AgenteProgramador/AGENTE_WORKFLOW.md y ejecutá el ciclo.
```

---

## Qué hace

1. Detecta el siguiente prompt disponible en la cola
2. Lo reclama con lock atómico por git (soporta multi-agente)
3. Lo ejecuta paso a paso con verificación técnica (lint + tests)
4. QA en navegador
5. Commitea, taggea y mergea (Gitflow: feature → develop → master)
6. Actualiza STATUS.md y presenta checkpoint

---

## Archivos

| Archivo | Propósito |
|---------|-----------|
| `AGENTE_WORKFLOW.md` | Prompt principal — leer para arrancar |
| `PROMPT_TAREA.template.md` | Plantilla para generar nuevos prompts de tarea |
