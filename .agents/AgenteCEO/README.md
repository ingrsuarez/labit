# 🎯 AgenteCEO

Agente orquestador del proyecto Labit. Lee el estado, presenta el panorama y delega al PM (planificación), Programador (ejecución), QA (calidad) o Designer (diseño).

---

## Cómo invocar

```
Lee .agents/AgenteCEO/AGENTE_CEO.md y arrancá.
```

---

## Qué hace

| Modo | Descripción |
|------|-------------|
| Ejecución | Delega al AgenteProgramador para ejecutar la próxima versión |
| Planificación | Delega al AgentePM para definir nuevas versiones |
| QA | Delega al AgenteQA para verificar calidad |
| Diseño | Delega al AgenteDesigner para diseñar interfaces |
| Reporte | Muestra estado detallado del proyecto |
| Cierre | Resume la sesión y cierra |

---

## Archivos

| Archivo | Propósito |
|---------|-----------|
| `AGENTE_CEO.md` | Prompt principal — leer para arrancar |
