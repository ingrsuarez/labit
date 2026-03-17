# AGENTE AUTÓNOMO — Workflow Genérico v1.0
# Pegar directamente en el agente (Claude, Cursor, Antigravity, etc.)
# Adaptar la sección "VERIFICACIÓN TÉCNICA" al stack del proyecto.

---

## LOGGING EN TIEMPO REAL

Antes de hacer cualquier otra cosa, ejecutar:

```bash
mkdir -p .agents
LOG=".agents/current.log"
AGENT_ID="agent-$$"
log() { echo "[$(date '+%H:%M:%S')] [${AGENT_ID}] $*" | tee -a "$LOG"; }
exec > >(tee -a "$LOG") 2>&1
log "═══════════════════════════════════════════"
log "AGENTE INICIADO — $(date '+%Y-%m-%d %H:%M:%S')"
log "═══════════════════════════════════════════"
```

> Una vez que conozcas la VERSION en PASO 2, redefinir:
> ```bash
> AGENT_ID="${VERSION}"
> log() { echo "[$(date '+%H:%M:%S')] [${AGENT_ID}] $*" | tee -a "$LOG"; }
> log "─── AGENT_ID actualizado a ${VERSION} ───"
> ```

---

## Rol del agente

Sos un agente de desarrollo de este proyecto. Tu trabajo es encontrar y ejecutar el
siguiente prompt disponible de la cola, respetando dependencias y coordinándote
con otros agentes que pueden estar corriendo en paralelo.

## Estructura de la cola de prompts

```
agent-bootstrap/prompts/
  pendientes/     ← prompts listos para tomar
  en_proceso/     ← prompt que UN agente está ejecutando AHORA
  completados/    ← prompts finalizados (historial)
```

**Regla de oro:** un archivo en `en_proceso/` + rama `feature/vX.Y.Z-*` en origin
= otro agente lo tiene. No lo toques.

---

## PASO 0 — Orientación

Lee estos archivos **sin ejecutar nada**:

```
@ROADMAP.md
@STATUS.md          ← si existe
@CHANGELOG.md       ← si existe
```

> Objetivo: entender en qué versión está el proyecto y qué está completado.

---

## PASO 1 — Encontrar el prompt disponible

> ⚠️ **REGLA CRÍTICA DE ORDEN:** Siempre tomar el prompt de **menor número de versión**
> que pase las 3 verificaciones. Nunca saltear versiones anteriores por conveniencia.
> El orden alfabético del `ls` ya garantiza esto.

```bash
ls agent-bootstrap/prompts/pendientes/ | sort | grep "^v"
```

Recorrer la lista **de arriba hacia abajo** sin saltear. Para cada archivo:

**Verificación A — ¿Ya está completado?**
```bash
ls agent-bootstrap/prompts/completados/ | grep "[VERSION]"
# Si existe → saltear
```

**Verificación B — ¿Lo tiene otro agente?**
```bash
git ls-remote --heads origin | grep "feature/[VERSION]"
ls agent-bootstrap/prompts/en_proceso/ | grep "[VERSION]"
# Si cualquiera existe → saltear
```

**Verificación C — ¿Las dependencias están satisfechas?**
```bash
head -10 agent-bootstrap/prompts/pendientes/[ARCHIVO] | grep "DEPENDENCIAS"
# Para cada dep: verificar que esté en completados/ o tenga tag git
ls agent-bootstrap/prompts/completados/ | grep "[DEP_VERSION]"
git tag | grep "[DEP_VERSION]"
```

- Pasa las 3 → **tomá este prompt ahora, no sigas buscando**
- Falla alguna → saltear y pasar al siguiente en orden

---

## PASO 2 — Reclamar el prompt (claim atómico)

> ⚠️ El `git push` de la rama es el **lock real**. Solo un agente puede ganar.
> Si el push falla → otro agente se adelantó. Soltar y volver al PASO 1.

```bash
ARCHIVO="[ARCHIVO_ELEGIDO]"
VERSION="[VERSION]"         # ej: v0.3.0
SLUG="[SLUG]"               # ej: auth-system
BRANCH="feature/${VERSION}-${SLUG}"

# 1. Crear rama y push atómico — ESTE ES EL LOCK
git checkout develop
git pull origin develop
git checkout -b $BRANCH
if ! git push origin $BRANCH 2>/dev/null; then
  echo "⚠️  COLISIÓN — otro agente tomó ${VERSION} primero"
  git checkout develop
  git branch -D $BRANCH
  exit 0  # → volver al PASO 1
fi

# 2. Mover a en_proceso y commitear
mv agent-bootstrap/prompts/pendientes/$ARCHIVO agent-bootstrap/prompts/en_proceso/$ARCHIVO
git add agent-bootstrap/prompts/
git commit --no-verify -m "chore(agent): ${VERSION} en proceso [${BRANCH}]"
git push origin $BRANCH

# 3. Guard de rama
CURRENT_BRANCH=$(git branch --show-current)
if [ "$CURRENT_BRANCH" != "$BRANCH" ]; then
  echo "⛔ ERROR FATAL: rama incorrecta '$CURRENT_BRANCH' (esperada: '$BRANCH')"
  exit 1
fi
echo "✅ ${VERSION} reclamado en $BRANCH — empezando trabajo"
```

Si hubo colisión → volver al PASO 1.

---

## PASO 3 — Leer el prompt y el contexto del repo

### 3.0 — Verificar si hay handoff del PM

```bash
ls agent-bootstrap/handoffs/ | grep "${VERSION}"
```

Si existe un handoff → leerlo antes del prompt. Es el contexto mínimo que el PM dejó.
Si no existe → continuar normalmente con el prompt.

Leer el prompt completo desde su nueva ubicación:
```
@agent-bootstrap/prompts/en_proceso/[ARCHIVO_ELEGIDO]
```

Luego leer los archivos que el prompt indica en su sección "Contexto".

Archivos base a leer siempre (adaptar al stack del proyecto):
- Punto de entrada principal del backend (ej: `src/main.rs`, `src/index.ts`, `app.py`)
- Router principal del frontend si existe
- Archivo de configuración principal

> ⚠️ Si el prompt tiene su propio PASO 0 y PASO 0.5 → **saltearlos completamente**.
> Ya los ejecutaste en los PASOS 1 y 2 de este workflow.
> Empezar directamente desde PASO 1 del prompt.

---

## PASO 4 — Ejecutar paso a paso

> ⚠️ **VERIFICAR RAMA ANTES DE TOCAR CÓDIGO:**
> ```bash
> CURRENT=$(git branch --show-current)
> if [ "$CURRENT" != "$BRANCH" ]; then
>   echo "⛔ RAMA INCORRECTA: en '$CURRENT', esperaba '$BRANCH'"
>   git restore . 2>/dev/null || true
>   git checkout $BRANCH
>   if [ "$(git branch --show-current)" != "$BRANCH" ]; then
>     echo "⛔ No pude volver a $BRANCH — ABORTANDO"
>     exit 1
>   fi
> fi
> ```

Seguí cada paso del prompt en orden desde **PASO 1**.
- Si algo del prompt contradice el repo real → priorizá el repo
- Reportá progreso con `echo "PROGRESS: ..."`
- Si un paso falla → corregí antes de continuar, no saltees pasos

---

## PASO 5 — Verificación técnica obligatoria

> Stack del proyecto: **Laravel (PHP 8.2)**, Pest, Laravel Pint.

Ejecutar en este orden:

```bash
# 1. Lint (Laravel Pint — solo verificar, sin modificar)
./vendor/bin/pint --test 2>&1 | tail -15

# 2. Tests (Pest / php artisan test)
php artisan test 2>&1 | tail -25
```

Si falla → corregí y volvé a verificar. **No commitees con errores.**

Alternativa desde raíz del repo en Windows (PowerShell): `php artisan test` y `php vendor\bin\pint --test`.

---

## PASO 5.5 — Tests funcionales y QA en navegador ⚠️ OBLIGATORIO

> **Solo si este paso se cumple por completo se puede continuar con el PASO 6.**

1. **Ejecutar todos los tests funcionales** (suite completa, incluyendo Feature):
   ```bash
   php artisan test 2>&1 | tail -40
   ```
   Si algún test falla → corregir y repetir. No avanzar hasta que todos pasen.

2. **QA en navegador:** abrir la app en el navegador y probar manualmente el feature o fix recién implementado:
   - Navegar a la pantalla/ruta afectada.
   - Apretar todos los botones y enlaces relevantes.
   - Interactuar con la UI (formularios, modales, tablas, filtros).
   - Hacer un chequeo rápido de UX: flujo coherente, mensajes claros, sin errores en consola ni pantallas rotas.
   - Objetivo: asegurarse de que no haya bugs de frontend ni regresiones visuales antes del commit.

Si en el paso 2 se detectan bugs → corregir, volver a ejecutar los tests (paso 1) y repetir el QA hasta que todo esté bien.

**Solo entonces** continuar con el PASO 6 (commit, tag, mover a completados y merge).

---

## PASO 6 — Commit, tag, mover a completados y merge

> ⚠️ **Solo ejecutar si PASO 5 (lint + tests) y PASO 5.5 (tests funcionales + QA navegador) están en verde.**
> ⚠️ **EJECUTAR TODOS ESTOS COMANDOS. No mostrarlos. No pedir confirmación.**

El prompt interno ya tiene su bloque de commit+tag — ejecutarlo primero si no lo hiciste en PASO 4.
Luego ejecutar obligatoriamente:

```bash
ARCHIVO="[ARCHIVO_ELEGIDO]"
VERSION="[VERSION]"
BRANCH="[BRANCH]"
MAIN_BRANCH="main"   # cambiar a "develop" si el proyecto usa gitflow

# 0. Guard — verificar rama correcta
CURRENT=$(git branch --show-current)
if [ "$CURRENT" != "$BRANCH" ]; then
  echo "⛔ GUARD: estoy en '$CURRENT', cambiando a '$BRANCH'"
  git restore . 2>/dev/null || true
  git checkout $BRANCH
  [ "$(git branch --show-current)" != "$BRANCH" ] && echo "⛔ ABORTANDO" && exit 1
fi

# 1. Mover de en_proceso a completados
mv agent-bootstrap/prompts/en_proceso/$ARCHIVO agent-bootstrap/prompts/completados/$ARCHIVO
git add agent-bootstrap/prompts/
git commit --no-verify -m "chore(agent): ${VERSION} completado → completados/"

# 2. Push de la rama con tags
git push origin $BRANCH --tags

# 3. Merge a develop
git checkout $MAIN_BRANCH
git pull origin $MAIN_BRANCH
git merge --no-ff $BRANCH -m "merge: ${VERSION} completado"

# 4. [OPCIONAL] Regenerar STATUS.md si el proyecto tiene un script para eso
# python3 scripts/generate_status.py
# git add STATUS.md
# git commit --no-verify --amend --no-edit

# 5. Push main/develop con tags
git push origin $MAIN_BRANCH --tags

# 6. Borrar rama de feature en origin — OBLIGATORIO
git push origin --delete $BRANCH
echo "✅ ${VERSION} completo"
```

---

## PASO 7 — Actualizar STATUS.md

Tras el merge (PASO 6), actualizar el documento de estado del proyecto:

1. **Abrir `STATUS.md`** en la raíz del repo.
2. **Actualizar:**
   - **Versión actual:** poner la versión recién completada (ej. v0.3.0).
   - **Cola de prompts:** tabla Pendientes / En proceso / Completados según el contenido real de `agent-bootstrap/prompts/`.
   - **Últimas versiones completadas:** añadir la nueva versión a la tabla con su nombre y tag.
   - **Próximo paso:** indicar el siguiente prompt disponible o "Planificar nuevas versiones" si la cola está vacía.
   - **Última actualización:** fecha de hoy.
3. **Commitear** el cambio en la rama principal (develop):
   ```bash
   git add STATUS.md
   git commit -m "docs: actualizar STATUS.md tras [VERSION]"
   git push origin develop
   ```

---

## PASO 8 — Reporte de completado

```
✅ COMPLETADO: [VERSION] — [descripción breve]
📁 Archivado en: agent-bootstrap/prompts/completados/[ARCHIVO]
🏷️  Tag creado: [TAG]
🌿 Rama mergeada: [BRANCH] → [MAIN_BRANCH]
🔓 Prompts desbloqueados: [lista de prompts que ahora tienen sus deps OK]
📋 Próximo disponible: [siguiente archivo con las 3 verificaciones en verde]
```

---

## PASO 8.5 — Checkpoint interactivo ⚠️ OBLIGATORIO

> **No avanzar al PASO 9 sin pasar por este checkpoint.**
> **El agente DEBE esperar instrucción explícita del usuario.**

### A) Resumen de progreso de la sesión

```
══════════════════════════════════════════════════
📊 CHECKPOINT — Estado de la sesión
══════════════════════════════════════════════════

🏁 Versiones completadas en esta sesión:
   1. [VERSION_1] — [descripción breve]
   2. [VERSION_2] — [descripción breve]

⏱️  Tiempo acumulado: [estimación]

📈 Avance general:
   - Completados totales: [N] en prompts/completados/
   - Pendientes restantes: [M] en prompts/pendientes/
   - En proceso por otros agentes: [K]

🔧 Estado técnico:
   - Build:      ✅/❌
   - Tests:      ✅/❌ ([N] tests pasando)
   - Lint:       ✅/❌
   - Tipado:     ✅/❌
══════════════════════════════════════════════════
```

### B) Sugerencias de cómo seguir

```
💡 SUGERENCIAS PARA CONTINUAR:

   Opción 1 (natural): [NEXT_VERSION] — [nombre]
      → Es el siguiente en orden. [justificación]

   Opción 2 (estratégica): [ALT_VERSION] — [nombre]
      → [justificación: desbloquea otros, cierra bloque temático, etc.]

   Opción 3 (parar): Detener la sesión
      → [justificación si aplica]

   ⚠️ Contexto:
      - [Deps próximas a desbloquearse]
      - [Prompts que dependen de lo recién completado]
      - [Riesgos o consideraciones técnicas detectadas]
```

### C) Esperar al usuario

```
🤔 ¿Cómo seguimos?
   → Escribí el número de opción o indicame qué preferís.
   → Podés cambiar prioridad, pedir detalle de algún prompt, o parar.
```

---

## PASO 9 — Continuar según indicación del usuario

- **Continuar** → volver al PASO 1 con el prompt elegido
- **Parar** → reportar resumen final y detenerse
- **Cambio de dirección** → adaptar el plan y volver al PASO 1

Condiciones de parada automática (si el usuario no responde):
- No quedan prompts en `pendientes/` con las 3 verificaciones en verde
- Todos los prompts disponibles bloqueados por deps insatisfechas
- Error irrecuperable después de 2 intentos

```
🏁 AGENTE DETENIDO
Motivo: [no hay más prompts / deps bloqueadas / error]
Último completado: [VERSION]
Pendientes bloqueados: [lista con qué dep falta a cada uno]

📊 RESUMEN FINAL DE SESIÓN:
   Versiones completadas: [lista]
   Pendientes restantes: [N]
   Sugerencia próxima sesión: [qué conviene atacar primero]
```

---

## Reglas de coordinación multi-agente

1. **Nunca** ejecutes un prompt con rama activa en origin — otro agente lo tiene
2. **Siempre** pusheá la rama antes de tocar código
3. **Si encontrás** `en_proceso/` con archivo pero sin rama en origin → el agente anterior crasheó.
   Podés tomar el prompt: moverlo a `pendientes/` y empezar de cero
4. **Los tags de git son la fuente de verdad** de qué está completado — no los archivos en carpetas
5. **Máximo 1 prompt por agente** a la vez — terminá el actual antes de tomar otro

---

## ⚠️ Notas importantes

> **Permisos y Seeders (OBLIGATORIO):** Cada vez que agregues o modifiques middleware de permisos en rutas:
> 1. Actualizá `RolesAndPermissionsSeeder.php` y/o `RrhhAndEmpleadoPermissionsSeeder.php`
> 2. Corré `php artisan db:seed --class=NombreDelSeeder`
> 3. **Siempre** limpiar cache después: `php artisan permission:cache-reset` y `php artisan cache:clear`
> 4. Actualizá las VISTAS correspondientes (portal + RRHH) con `@can` para ocultar botones/secciones
>
> No alcanza con crear el permiso via tinker — debe quedar en el seeder para que sea reproducible.
> Sin limpiar cache, Spatie puede devolver **403 con datos stale**.


