# 🎨 AgenteDesigner

Agente de diseño web del proyecto Labit. Piensa la UI/UX antes de que el programador toque código, y recomienda mejoras de diseño en interfaces existentes.

---

## Cómo invocar

### Por el humano

```
Lee .agents/AgenteDesigner/AGENTE_DESIGNER.md y diseñá la UI de [descripción de pantalla o feature].
```

```
Lee .agents/AgenteDesigner/AGENTE_DESIGNER.md y revisá el diseño del módulo [nombre].
```

### Por el AGENTE_CEO

```
[DELEGANDO A AGENTE_DESIGNER]
Lee .agents/AgenteDesigner/AGENTE_DESIGNER.md.
Modo: [nueva pantalla / mejora existente].
Contexto: [descripción de lo que se va a construir o mejorar].
Cuando termines, reportá el documento de diseño al AGENTE_CEO.
```

### Por el AGENTE_PM

```
[DELEGANDO A AGENTE_DESIGNER]
Lee .agents/AgenteDesigner/AGENTE_DESIGNER.md.
Modo: nueva pantalla.
Feature: [descripción de la versión que se va a implementar].
Cuando termines, el documento de diseño va a ser contexto adicional para el AGENTE_PROGRAMADOR.
```

---

## Qué produce

| Output | Descripción |
|--------|-------------|
| Documento de diseño (`DISEÑO_[VERSION].md`) | Wireframe, componentes, estados, decisiones de UX |
| Imágenes de mockup | Generadas cuando la complejidad lo justifica |

---

## Archivos de referencia

| Archivo | Propósito |
|---------|-----------|
| `AGENTE_DESIGNER.md` | Prompt principal — leer para arrancar |
| `DESIGN_SYSTEM.md` | Referencia de componentes y estilos disponibles en el proyecto |
| `BLUEPRINT.md` (raíz) | Contexto técnico: módulos, rutas, stack |
