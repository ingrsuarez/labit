# Runbook operativo — Dashboard de monitoreo API (v1.53.0)

> Panel en `/admin/api-monitor`. Acceso: roles con permission `lab-admissions.index` (bioquímico, técnico, recepción).
> Sección técnica adicional: permission `api-clients.manage` (admin IT).

---

## 1. Interpretar los counters del dashboard

| Counter | Qué significa |
|---|---|
| **Batches recibidos** | Cantidad de requests `POST /api/v1/results/batch` recibidos en el período |
| **Mensajes ingestados** | Mensajes HL7 procesados exitosamente (todos sus OBX se guardaron) |
| **Mensajes parciales** | Mensajes donde algunos OBX se guardaron y otros se rechazaron |
| **Mensajes rechazados** | Mensajes completamente rechazados (protocolo no encontrado, o todos los OBX ya validados) |
| **ítems** (sublabel) | Suma de OBX individuales ingestados + sobrescritos desde los contadores de `result_batches` |

Los counters se actualizan automáticamente cada **10 segundos** mientras el dashboard está abierto.

---

## 2. Qué hacer ante un rechazo `ALREADY_VALIDATED`

**Contexto:** LISCOM intentó enviar el resultado de un equipo para un protocolo que ya fue validado por un bioquímico en labit. La regla del sistema **nunca** sobrescribe valores ya validados.

**Pasos:**

1. En el dashboard, aparece un banner violeta si hay rechazos del tipo en las últimas 24 hs.
2. Hacé click en "Ver mensajes rechazados →" o ir a `/admin/api-monitor/ingestions?razon=ALREADY_VALIDATED`.
3. En el listado de mensajes, identificar el protocolo y clicar en "Ver →" para el detalle.
4. En el detalle del mensaje, la tabla de ítems muestra:
   - Qué test fue rechazado (`labit_test_id`)
   - Quién lo validó (`validated_by_name`) y cuándo (`validated_at`)
5. Si el resultado del equipo es el correcto y el bioquímico validó un valor anterior incorrecto:
   - Ir al protocolo en labit (link directo en la vista de detalle de la ingestion).
   - Desvalidar la determinación correspondiente (botón en el protocolo).
   - Pedirle al técnico que reenvíe el resultado desde LISCOM.
6. Si el resultado del bioquímico es correcto, no hay acción a tomar. El rechazo es esperado.

---

## 3. Identificar una sede desconectada

La tabla **"Estado de las sedes"** en el dashboard muestra el badge de salud de cada cliente API:

| Badge | Significado | Acción sugerida |
|---|---|---|
| **Activo** | Última actividad hace menos de 1 hora | Normal |
| **Inactivo (hs)** | Entre 1 y 12 horas sin actividad | Normal fuera de horario laboral. Verificar si es horario de operación |
| **Sin datos (días)** | Entre 12 horas y 3 días | Verificar con el técnico de la sede si LISCOM está corriendo |
| **Desconectado** | Más de 3 días sin actividad | Contactar soporte de la sede. Verificar API key activa |
| **Nunca usado** | La key existe pero nunca fue usada | Normal si es una key nueva aún no desplegada |

**Si la sede dice "Desconectado":**
1. Verificar en `/admin/api-clients` (link "Detalle key" en la tabla) que la key esté activa (`Activo: Sí`).
2. Pedirle al técnico de la sede que verifique que LISCOM esté corriendo y configurado con la URL y key correctas.
3. Si la key fue revocada accidentalmente, regenerarla desde el CRUD de API keys y entregarla al técnico.

---

## 4. Ajustar la retención de logs

Los registros de `result_batches` y `result_ingestions` se eliminan automáticamente con el comando `api:cleanup`, que corre diariamente a las 03:00.

**Variable de entorno:**
```
API_LOG_RETENTION_DAYS=90   # default: 90 días
```

**Para ver cuántos registros serían borrados sin borrar nada:**
```bash
php artisan api:cleanup --dry-run
```

**Para borrar manualmente con retención diferente:**
```bash
php artisan api:cleanup --days=60
```

**Para borrar sin confirmación interactiva (útil en cron):**
```bash
php artisan api:cleanup --no-interaction
```

> El borrado elimina ingestions primero, luego batches (respeta la FK cascadeOnDelete).

---

## 5. Acceso y permisos

| Permission | Qué puede ver |
|---|---|
| `lab-admissions.index` | Dashboard completo: counters, estado de sedes, listado de batches y mensajes, detalle con link al protocolo |
| `api-clients.manage` | Además: payload raw del request (en detalle de batch), info técnica del mensaje (external_message_id, timestamp exacto), botón "Gestionar API keys", link "Detalle key" en la tabla de sedes |

Los roles que tienen `lab-admissions.index` por defecto: **bioquimico**, **tecnico-lab**, **recepcion-lab**.
El permission `api-clients.manage` lo tiene el rol **admin**.

---

## 6. Drill-down completo (bioquímico)

```
Dashboard
  → [Batches recibidos] → lista de batches con filtros (sede, fecha, solo rechazos)
      → [Ver →] → detalle de batch → lista de mensajes
          → [Ver →] → detalle de mensaje → tabla de OBX
              → [Ver en labit →] → protocolo clínico/muestra/vet en labit
```

---

## 7. Dependencias

| Versión | Componente |
|---|---|
| v1.46.0 | `ApiClient` con `last_used_at`, `requests_count`, permission `api-clients.manage` |
| v1.51.0 | `ResultBatch` + `ResultIngestion`, endpoint `POST /api/v1/results/batch` |
| v1.52.0 | LISCOM (repo separado) que consume el endpoint de v1.51.0 |
| v1.53.0 | Este dashboard |
