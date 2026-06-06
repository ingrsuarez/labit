# Space10 — Upload de informes lab clínico

Labit puede subir PDFs de protocolos clínicos validados al portal Space10 (SPACE4CLINIC) vía API Sanctum.

## Variables de entorno (Labit)

```env
SPACE10_ENABLED=true
SPACE10_API_URL=http://localhost/space10/public/api/upload/lab
SPACE10_API_TOKEN=<token Sanctum>
SPACE10_TIMEOUT=30
```

## Token Sanctum (Space10)

En el proyecto Space10, con un usuario que tenga institución activa:

```bash
php artisan tinker
$user = \App\Models\User::find(1);
$user->createToken('labit-upload')->plainTextToken;
```

Copiar el token en `SPACE10_API_TOKEN` de Labit (solo se muestra una vez).

## Comportamiento

- **Auto-upload al enviar email** (individual o masivo) **solo si** el destinatario del correo es el **email del paciente**. No se sube si el mail va a obra social, empresa laboral u otro destino.
- **Batch manual** “Subir a Space10” desde listado de admisiones clínicas (sin depender del email).
- **Fecha en Space10:** se envía la fecha del día (`d-m-Y`, ej. `06-06-2026`) para el nombre `lab-{dni}-{fecha}.pdf`, igual que la carga manual en Space10.
- Idempotencia: columna `admissions.space10_uploaded_at`.
