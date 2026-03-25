# ROADMAP — Labit

> Versiones planificadas, en progreso y completadas del proyecto.
> Última actualización: 2026-03-24

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

| v1.5.4 | Tests faltantes y jerarquía padre-hijo completa | 2026-03-22 | 27 tests hijos, 37 relaciones, badge padre, fix cascada |
| v1.6.0 | Formato tabular en PDFs de informes | 2026-03-23 | Tabla con Análisis/Resultado/Unidad/Ref, padres bold, hijos indentados |
| v1.6.1 | Filtrar nomencladores de dropdowns y crear Particular | 2026-03-23 | Seeder Particular, filtrar type!=nomenclador en 5 controladores |
| v1.7.0 | Cobro a particulares y control de deuda | 2026-03-23 | Migración payment fields, cobro parcial/total, deudores, 3 medios de pago |
| v1.8.0 | Búsqueda activa en protocolos de muestras | 2026-03-23 | Filtrado Alpine.js client-side, sin paginación, búsqueda instantánea |
| v1.9.0 | Firma digital de validadores y nombre automático de PDF | 2026-03-24 | Upload firma en perfil, firma en PDF, nombre descriptivo de archivo |
| v1.10.0 | Importación de nomencladores desde Excel | 2026-03-24 | 8 nomencladores base desde .xlsx, 297 tests nuevos, 8826 prácticas |
| v1.11.0 | Importación de obras sociales desde Excel | 2026-03-24 | Seeder obras sociales desde .xlsx, asociación a nomencladores base |
| v1.11.1 | Configuración de correos del laboratorio | 2026-03-25 | LabSetting key-value, 2 cuentas, firma HTML |
| v1.11.2 | Buscador en dropdown de obra social | 2026-03-24 | Combobox Alpine.js en admisiones y pacientes |
| v1.12.0 | PDF protocolos lab clínico + envío email | 2026-03-25 | PDF tabular, firma validador, envío con LabSetting |
| v1.13.0 | Nomenclador en tiempo real (sin duplicación) | 2026-03-25 | Precio = nbu_units base × nbu_value OS, sin copiar prácticas |
| v1.13.1 | Fix searchTests usa nomenclador base | 2026-03-25 | Hotfix: searchTests no usaba fallback a nomenclador base |

---

## En progreso

| Versión | Nombre | Estado | Rama |
|---|---|---|---|

---

## Planificado

| Versión | Nombre | Estimación | Dependencias | Prompt |
|---|---|---|---|---|
| v1.14.0 | Precios en protocolos de aguas y alimentos | 2-3h | ninguna | `prompts/pendientes/v1.14.0-precios-aguas-alimentos.md` |
| v1.14.1 | Otros valores de referencia en determinaciones | 30min | ninguna | `prompts/pendientes/v1.14.1-otros-valores-referencia.md` |
| v1.15.0 | Sub-padres y orden fijo de determinaciones | 2-3h | ninguna | `prompts/pendientes/v1.15.0-sub-padres-orden-fijo.md` |

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
Completadas:  31 versiones (v1.0.0 → v2.3.0)
Planificadas: 3
En proceso:   0
Próxima:      v1.14.0 — Precios en protocolos de aguas y alimentos
```

---

> Este documento se actualiza al finalizar cada versión o sesión de planificación.
