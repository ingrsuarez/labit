# 📋 AgentePM

Agente de planificación del proyecto Labit. Conversa con el equipo/cliente, define versiones y genera prompts ejecutables en `agent-bootstrap/prompts/pendientes/`.

---

## Cómo invocar

```
Lee .agents/AgentePM/AGENTE_PM.md y arrancá una sesión de planificación.
```

### Modos disponibles

| Modo | Descripción |
|------|-------------|
| Revisión | Revisa el estado y sugiere próximas versiones |
| Feature | Define una feature nueva o pedido del cliente |
| Cliente | Lenguaje no técnico, traduce requerimientos a versiones |
| Curaduría | Reorganiza prioridades del roadmap |

---

## Qué produce

| Output | Ubicación |
|--------|-----------|
| Prompts de tarea | `agent-bootstrap/prompts/pendientes/vX.Y.Z-slug.md` |
| Actualización del ROADMAP | `ROADMAP.md` en la raíz |

---

## Archivos

| Archivo | Propósito |
|---------|-----------|
| `AGENTE_PM.md` | Prompt principal — leer para arrancar |
