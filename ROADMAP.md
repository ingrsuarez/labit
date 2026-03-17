# ROADMAP — Labit

> Versiones planificadas, en progreso y completadas del proyecto.
> Última actualización: 2026-03-17

---

## Convenciones

- **vX.0.0** — Major: cambio significativo de arquitectura o funcionalidad
- **v0.X.0** — Minor: nueva feature o módulo
- **v0.0.X** — Patch: fix, mejora menor, documentación

---

## Completadas

| Versión | Nombre | Fecha | Notas |
|---|---|---|---|
| v1.0.0 | Línea base del proyecto | 2026-03-14 | Documentación del estado existente |
| v1.0.1 | README del proyecto | 2026-03-14 | README.md completo |
| v1.1.0 | Normalización de line endings | 2026-03-16 | .gitattributes + .editorconfig + renormalización |
| v1.2.0 | Infraestructura AFIP | 2026-03-14 | AfipService, certificados, WSAA, WSFEv1 |
| v1.3.0 | Facturación electrónica WSFEv1 | 2026-03-14 | Autorización automática, CAE, PDF con QR |
| v1.3.1 | Fix AFIP CondicionIVAReceptorId + ImpTotal | 2026-03-15 | RG 5616, cálculo correcto de totales |
| v1.4.0 | Notas de crédito electrónicas | 2026-03-17 | NC A/B/C con AFIP, comprobante asociado |

---

## En progreso

| Versión | Nombre | Estado | Rama |
|---|---|---|---|

---

## Planificado

| Versión | Nombre | Estimación | Dependencias | Prompt |
|---|---|---|---|---|
| v1.5.0 | Lector QR facturas de compra | 2h | v1.3.0 | `pendientes/v1.5.0-qr-reader-compras.md` |

---

## Áreas candidatas (sin planificar)

- **UI/UX**: auditoría visual, migración de componentes, design system
- **Testing**: suite de tests automatizados, cobertura mínima
- **DevOps**: CI/CD, ambientes de staging, deploy automatizado
- **Seguridad**: auditoría de permisos, 2FA, logs de acceso

---

## Progreso general

```
Completadas:  7 versiones (v1.0.0 → v1.4.0)
Planificadas: 1 versión (v1.5.0)
En proceso:   0
Próxima:      v1.5.0 — Lector QR facturas de compra
```

---

> Este documento se actualiza al finalizar cada versión o sesión de planificación.
