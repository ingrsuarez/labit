# ROADMAP — Labit

> Versiones planificadas, en progreso y completadas del proyecto.
> Última actualización: 2026-03-22

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
| v1.4.1 | Fix guardado de resultados de protocolo | 2026-03-17 | Formularios anidados → submitAction() |
| v1.5.1 | Roles y permisos del módulo de laboratorio | 2026-03-17 | 3 roles, 15 permisos, middleware + @can |
| v1.5.2 | Roles y permisos del módulo de muestras | 2026-03-17 | Extiende roles con permisos de muestras |
| v1.5.3 | Seeder de jerarquía padre-hijo de prácticas | 2026-03-20 | 10 relaciones para 3 padres, 26 tests pendientes de crear |
| v2.0.0 | Infraestructura multi-empresa | 2026-03-21 | Modelo Company, pivot, middleware, CRUD, selector en header |
| v2.1.0 | Ventas y cobros multi-empresa | 2026-03-22 | company_id en ventas, filtrado por empresa, AfipService multi-empresa |

| v2.2.0 | Compras y pagos multi-empresa | 2026-03-22 | company_id en purchase_quotation_requests, purchase_orders, delivery_notes, purchase_invoices, payment_orders |
| v2.2.1 | Fix columnas vacías en vista de protocolo | 2026-03-22 | Reemplazar template x-if por x-show en tabla de determinaciones |
| v2.3.0 | RRHH multi-empresa | 2026-03-22 | company_id en employees, payrolls, leaves, documents |

---

## En progreso

| Versión | Nombre | Estado | Rama |
|---|---|---|---|

---

## Planificado

| Versión | Nombre | Estimación | Dependencias | Prompt |
|---|---|---|---|---|
| v1.5.4 | Tests faltantes y jerarquía padre-hijo completa | 1h | v1.5.3 | `pendientes/v1.5.4-tests-faltantes-jerarquia-completa.md` |

---

## Áreas candidatas (sin planificar)

- **Lector QR facturas de compra**: feature de v1.5.0, deprimerizada en favor de multi-empresa
- **UI/UX**: auditoría visual, migración de componentes, design system
- **Testing**: suite de tests automatizados, cobertura mínima
- **DevOps**: CI/CD, ambientes de staging, deploy automatizado
- **Seguridad**: auditoría de permisos, 2FA, logs de acceso

---

## Progreso general

```
Completadas:  18 versiones (v1.0.0 → v2.3.0)
Planificadas: 1 versión (v1.5.4)
En proceso:   0
Próxima:      v1.5.4 — Tests faltantes y jerarquía padre-hijo completa
```

---

> Este documento se actualiza al finalizar cada versión o sesión de planificación.
