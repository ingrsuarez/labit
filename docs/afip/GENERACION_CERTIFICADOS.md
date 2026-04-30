# Generación de Certificados AFIP para Facturación Electrónica

> Guía completa para generar la clave privada, el CSR y obtener el certificado
> firmado por AFIP/ARCA, compatible con la integración del proyecto LABIT
> (`config/afip.php` → `storage/app/afip/cert.pem` y `key.pem`).

---

## Índice

1. [Conceptos previos](#conceptos-previos)
2. [Requisitos](#requisitos)
3. [Flujo general](#flujo-general)
4. [Paso 1 — Generar la clave privada (`.key`)](#paso-1--generar-la-clave-privada-key)
5. [Paso 2 — Generar el CSR](#paso-2--generar-el-csr-certificate-signing-request)
6. [Paso 3 — Subir el CSR a AFIP y obtener el `.crt`](#paso-3--subir-el-csr-a-afip-y-obtener-el-crt)
7. [Paso 4 — Instalar los archivos en el proyecto](#paso-4--instalar-los-archivos-en-el-proyecto)
8. [Paso 5 — Verificación local](#paso-5--verificación-local)
9. [Paso 6 — Probar la conexión desde Laravel](#paso-6--probar-la-conexión-desde-laravel)
10. [Apartado Windows (WAMP / PowerShell / CMD)](#apartado-windows-wamp--powershell--cmd)
11. [Script automatizado para Windows](#script-automatizado-para-windows-bat)
12. [Errores frecuentes](#errores-frecuentes)
13. [Renovación del certificado](#renovación-del-certificado)
14. [Buenas prácticas de seguridad](#buenas-prácticas-de-seguridad)

---

## Conceptos previos

| Archivo                          | ¿Qué es?                                                            | ¿Se sube a AFIP? |
|----------------------------------|---------------------------------------------------------------------|------------------|
| `.key` / `.pem` (privada)        | Clave privada RSA. Identidad criptográfica del sistema.             | **Nunca**        |
| `.csr`                           | Solicitud de firma. Contiene los datos públicos + clave pública.    | **Sí**           |
| `.crt` / `.cer` / `.pem` (cert)  | Certificado firmado por la CA de AFIP.                              | **Se descarga**  |

> `.crt`, `.cer` y `.pem` son intercambiables si están en formato **PEM** (texto base64
> con cabeceras `-----BEGIN ... -----`). No requieren conversión.

### Entornos AFIP

- **Homologación (testing):** WSASS — https://wsass-homo.afip.gob.ar
- **Producción (real):** Administrador de Certificados Digitales en el portal de AFIP.

---

## Requisitos

- OpenSSL 1.1.1+ instalado.
- CUIT habilitado y clave fiscal nivel 3 (para producción).
- Acceso al servicio "Administrador de Relaciones" de AFIP.

---

## Flujo general

```
                    ┌──────────────┐
  openssl genrsa →  │ private.key  │  Clave privada — NUNCA se sube
                    └──────┬───────┘
                           │
                  openssl req -new
                           │
                           ▼
                    ┌──────────────┐
                    │ request.csr  │  Solicitud — SE SUBE a AFIP
                    └──────┬───────┘
                           │
              [Portal AFIP / WSASS firma]
                           │
                           ▼
                    ┌──────────────┐
                    │   cert.crt   │  Certificado firmado — SE DESCARGA
                    └──────┬───────┘
                           │
                           ▼
              storage/app/afip/cert.pem  ← cert.crt
              storage/app/afip/key.pem   ← private.key
```

---

## Paso 1 — Generar la clave privada (`.key`)

Genera una clave RSA de 2048 bits **sin passphrase** (AFIP/AfipSDK requieren leerla automáticamente).

```bash
openssl genrsa -out private.key 2048
```

> ⚠️ **NO uses `-des3`** ni ninguna opción que agregue passphrase. La librería
> `afipsdk/afip.php` no la soporta.

---

## Paso 2 — Generar el CSR (Certificate Signing Request)

Formato exacto que exige AFIP en el `subject`:

| Campo          | Significado                                       | Ejemplo                                  |
|----------------|---------------------------------------------------|------------------------------------------|
| `C`            | País                                              | `AR`                                     |
| `O`            | Organización (razón social tal como figura en AFIP) | `IPAC Laboratorio de Aguas y Alimentos` |
| `CN`           | Common Name (alias del sistema)                   | `labitsystem`                            |
| `serialNumber` | Literal `CUIT` + espacio + CUIT sin guiones       | `CUIT 20123456789`                       |

### Comando (Linux/Mac/Git Bash)

```bash
openssl req -new \
  -key private.key \
  -subj "/C=AR/O=IPAC Laboratorio de Aguas y Alimentos/CN=labitsystem/serialNumber=CUIT 20XXXXXXXXX" \
  -out request.csr
```

### Modo interactivo (si tu shell rompe los espacios)

```bash
openssl req -new -key private.key -out request.csr
```

Y al ser preguntado:

```
Country Name (2 letter code): AR
State or Province Name:       (vacío, Enter)
Locality Name:                (vacío, Enter)
Organization Name (O):        IPAC Laboratorio de Aguas y Alimentos
Organizational Unit Name:     (vacío, Enter)
Common Name (CN):             labitsystem
Email Address:                (vacío, Enter)
A challenge password:         (vacío, Enter)
An optional company name:     (vacío, Enter)
```

> ⚠️ El modo interactivo **NO permite** definir `serialNumber`. Para AFIP es
> obligatorio, así que conviene siempre usar `-subj`. Si tu shell lo complica,
> usá el script `.bat` del apartado Windows.

---

## Paso 3 — Subir el CSR a AFIP y obtener el `.crt`

### A) Homologación (WSASS)

1. Ingresar a https://wsass-homo.afip.gob.ar/wsass/portal/post-login.aspx con clave fiscal.
2. **Crear certificado de homologación**.
3. Definir un alias (mismo `CN` que en el CSR, ej. `labitsystem`).
4. Pegar el contenido completo de `request.csr` (incluyendo cabeceras
   `-----BEGIN CERTIFICATE REQUEST-----` y `-----END CERTIFICATE REQUEST-----`).
5. AFIP devuelve el `.crt` para descargar/copiar.
6. **Autorizar Web Service** → seleccionar `wsfe` (Facturación Electrónica).

### B) Producción

1. Adherir en AFIP el servicio **"Administrador de Certificados Digitales"**.
2. **Agregar alias** → pegar el `.csr` → AFIP firma y devuelve el `.crt`.
3. Ir a **"Administrador de Relaciones"** → autorizar `wsfe` con ese certificado
   como **Computador Fiscal**.

---

## Paso 4 — Instalar los archivos en el proyecto

`config/afip.php` espera los archivos en:

```
storage/app/afip/cert.pem   ← certificado firmado por AFIP
storage/app/afip/key.pem    ← clave privada
```

Renombrar es suficiente (no hace falta convertir si están en formato PEM):

```bash
mv private.key       storage/app/afip/key.pem
mv certificate.crt   storage/app/afip/cert.pem
```

### Si AFIP entrega el certificado en formato DER (binario)

```bash
openssl x509 -inform DER -in certificate.cer -out cert.pem -outform PEM
```

---

## Paso 5 — Verificación local

```bash
# 1) Ver datos del certificado (CUIT, validez, alias)
openssl x509 -in storage/app/afip/cert.pem -text -noout

# 2) Confirmar que la clave y el cert son del mismo par (los hashes deben coincidir)
openssl x509 -noout -modulus -in storage/app/afip/cert.pem | openssl md5
openssl rsa  -noout -modulus -in storage/app/afip/key.pem  | openssl md5
```

---

## Paso 6 — Probar la conexión desde Laravel

Con `AfipService` ya registrado (ver `agent-bootstrap/prompts/completados/v1.2.0-afip-infraestructura.md`):

```
GET /admin/afip/test-connection
```

Internamente llama a `getServerStatus()` (FEDummy). Si responde:

```
AppServer:  OK
DbServer:   OK
AuthServer: OK
```

→ los certificados están correctamente configurados y autorizados.

---

## Apartado Windows (WAMP / PowerShell / CMD)

### 1) Localizar OpenSSL

WAMP incluye OpenSSL dentro de Apache. Buscalo en:

```
C:\wamp64\bin\apache\apache2.x.x.x\bin\openssl.exe
```

(reemplazar `apache2.x.x.x` por la versión instalada).

Alternativas:
- **Git for Windows** trae OpenSSL en `C:\Program Files\Git\usr\bin\openssl.exe`.
- **OpenSSL nativo** desde https://slproweb.com/products/Win32OpenSSL.html

### 2) Agregar OpenSSL al PATH (opcional pero recomendado)

**PowerShell (sólo sesión actual):**

```powershell
$env:Path += ";C:\wamp64\bin\apache\apache2.4.62\bin"
```

**De forma permanente (Sistema → Variables de entorno → PATH):**
- Agregar `C:\wamp64\bin\apache\apache2.4.62\bin`
- Cerrar y abrir una nueva terminal.

### 3) Verificar instalación

```powershell
openssl version
```

Debería responder algo como `OpenSSL 1.1.1w  11 Sep 2023`.

### 4) Generar archivos desde PowerShell

```powershell
# Crear carpeta de trabajo
New-Item -ItemType Directory -Force -Path C:\afip-certs
Set-Location C:\afip-certs

# 1) Clave privada
openssl genrsa -out private.key 2048

# 2) CSR (¡las comillas dobles son importantes en PowerShell!)
openssl req -new `
  -key private.key `
  -subj "/C=AR/O=IPAC Laboratorio de Aguas y Alimentos/CN=labitsystem/serialNumber=CUIT 20XXXXXXXXX" `
  -out request.csr

# 3) Ver el CSR para copiarlo a AFIP
Get-Content request.csr
```

> ⚠️ **PowerShell** usa el backtick `` ` `` para continuación de línea (no `\`).

### 5) Generar archivos desde CMD

```cmd
mkdir C:\afip-certs
cd /d C:\afip-certs

openssl genrsa -out private.key 2048

openssl req -new -key private.key -subj "/C=AR/O=IPAC Laboratorio de Aguas y Alimentos/CN=labitsystem/serialNumber=CUIT 20XXXXXXXXX" -out request.csr

type request.csr
```

> En CMD, si la razón social tiene espacios y se rompe, escapá las comillas
> internas con `\"` o usá el script `.bat` del apartado siguiente.

### 6) Copiar los archivos al proyecto

```powershell
Copy-Item C:\afip-certs\private.key       C:\wamp64\www\labit\storage\app\afip\key.pem
Copy-Item C:\afip-certs\certificate.crt   C:\wamp64\www\labit\storage\app\afip\cert.pem
```

O en CMD:

```cmd
copy C:\afip-certs\private.key       C:\wamp64\www\labit\storage\app\afip\key.pem
copy C:\afip-certs\certificate.crt   C:\wamp64\www\labit\storage\app\afip\cert.pem
```

### 7) Verificar el `.env`

```
AFIP_CUIT=20XXXXXXXXX
AFIP_CERT_PATH=storage/app/afip/cert.pem
AFIP_KEY_PATH=storage/app/afip/key.pem
AFIP_PRODUCTION=false
```

---

## Script automatizado para Windows (.bat)

> El script ya está incluido en el repositorio: **`scripts/afip/generar-afip.bat`**.
> Sólo hacé doble click sobre él (o ejecutalo desde CMD) y respondé las preguntas.

### Qué hace

- **Detecta automáticamente OpenSSL** en: PATH, WAMP (`C:\wamp64\bin\apache\apache*\bin\`),
  Git for Windows, OpenSSL nativo (slproweb) y XAMPP.
- Pide CUIT (con validación de 11 dígitos), razón social, alias y carpeta de salida
  (default `C:\afip-certs`).
- Si ya existían `private.key`/`request.csr`, hace **backup automático** antes de sobrescribir.
- Genera la **clave privada** RSA 2048 sin passphrase.
- Genera el **CSR** con el `subject` correcto para AFIP (`/C=AR/O=.../CN=.../serialNumber=CUIT XXX`).
- Muestra el contenido del CSR en pantalla.
- Pregunta si querés **copiarlo al portapapeles** (`clip.exe`) para pegarlo directo en AFIP.
- Pregunta si querés **abrir la carpeta** en el Explorador.

### Uso paso a paso

1. **Doble click** en `scripts/afip/generar-afip.bat` (o ejecutarlo desde CMD/PowerShell).
2. El script detecta OpenSSL automáticamente y muestra la ruta usada.
3. Ingresar:
   - **CUIT** (11 dígitos sin guiones — se valida la longitud)
   - **Razón Social** (tal como figura en AFIP, con espacios y acentos)
   - **Alias** del sistema (ej: `labitsystem`)
   - **Carpeta de salida** (Enter para usar `C:\afip-certs`)
4. Confirmar con `S`.
5. El script genera `private.key` y `request.csr` en la carpeta indicada,
   muestra el CSR en pantalla y ofrece:
   - **Copiar al portapapeles** (recomendado: `S` y pegar directo en AFIP con `Ctrl+V`).
   - **Abrir la carpeta** en el Explorador de Windows.
6. Subir el CSR a AFIP:
   - Homologación: https://wsass-homo.afip.gob.ar
   - Producción: Administrador de Certificados Digitales (clave fiscal nivel 3).
7. Descargar el `.crt` firmado por AFIP.
8. Copiar los dos archivos al proyecto:

   ```cmd
   copy C:\afip-certs\private.key      C:\wamp64\www\labit\storage\app\afip\key.pem
   copy <descarga>\certificate.crt     C:\wamp64\www\labit\storage\app\afip\cert.pem
   ```

9. En el portal de AFIP: **Administrador de Relaciones** → autorizar el Web Service `wsfe`.
10. Probar la conexión con `GET /admin/afip/test-connection`.

### Notas

- Si el script encuentra una `private.key` previa en la carpeta de destino, **hace
  backup automático** (sufijo con timestamp) antes de sobrescribir.
- Si OpenSSL no está disponible, el script muestra opciones de instalación
  (Git for Windows, OpenSSL nativo de slproweb, WAMP, XAMPP).
- Para auditar o adaptar el script, abrir directamente
  [`scripts/afip/generar-afip.bat`](../../scripts/afip/generar-afip.bat).

---

## Errores frecuentes

| Error                                                 | Causa                                            | Solución                                                                 |
|-------------------------------------------------------|--------------------------------------------------|--------------------------------------------------------------------------|
| `Computador no autorizado para acceder al servicio`   | Certificado generado pero no vinculado a `wsfe`  | Ir a "Administrador de Relaciones" y autorizar el WS                     |
| `Token ya utilizado` / Token caducado                 | Cache del WSAA viejo                             | Borrar `storage/framework/cache` o el TA cacheado por AfipSDK            |
| `unable to load Private Key`                          | La key tiene passphrase o está corrupta          | Regenerar con `openssl genrsa` SIN `-des3`                               |
| `Certificate has expired`                             | Caducó (homo: 2 años; prod: hasta 2 años)        | Repetir desde Paso 2 (podés reusar la misma `.key`)                      |
| `Subject Attribute serialNumber has no known NID`     | Versión vieja de OpenSSL                         | Actualizar OpenSSL a 1.1.1+ o usar `2.5.4.5=CUIT 20XXXX...`              |
| Error al pegar el CSR en AFIP                         | Falta cabecera `-----BEGIN CERTIFICATE REQUEST-----` | Asegurarse de copiar el contenido completo, sin espacios extra       |
| Razón social con `&` o caracteres especiales          | Shell interpreta el carácter                     | Escapar (`^&` en CMD, `` `& `` en PowerShell) o usar el script `.bat`    |

---

## Renovación del certificado

Cuando el certificado caduque (homologación: 2 años; producción: hasta 2 años):

1. **Reusar la misma `private.key`** (no hace falta regenerarla).
2. Generar un **nuevo CSR** desde el Paso 2.
3. Subirlo a AFIP (mismo alias) → obtener el nuevo `.crt`.
4. Reemplazar `storage/app/afip/cert.pem`.
5. Borrar el cache de tokens de AfipSDK.

---

## Buenas prácticas de seguridad

- ✅ La `.key` **NUNCA** se sube al repositorio. Verificar `.gitignore`:

  ```
  storage/app/afip/*.pem
  storage/app/afip/*.key
  storage/app/afip/*.crt
  ```

- ✅ Hacer **backup cifrado** de la `.key` (1Password, Bitwarden, Vault, etc.).
- ✅ Permisos restrictivos en el server de producción:

  ```bash
  chmod 600 storage/app/afip/key.pem
  chmod 644 storage/app/afip/cert.pem
  ```

- ✅ Usar **certificados distintos** para homologación y producción.
- ✅ En producción, definir un **alias descriptivo** por aplicación (ej: `labit-prod`)
  para poder revocar sólo ese certificado si hace falta.
- ❌ No compartir la `.key` por mail, Slack ni chat.
- ❌ No reutilizar el mismo certificado en múltiples sistemas distintos.

---

## Referencias

- AfipSDK PHP: https://github.com/AfipSDK/afip.php
- WSASS Homologación: https://wsass-homo.afip.gob.ar
- Documentación oficial WSAA: https://www.afip.gob.ar/ws/documentacion/wsaa.asp
- Manual desarrollador WSFEv1: https://www.afip.gob.ar/fe/documentos/manual_desarrollador_COMPG_v2_10.pdf
- Implementación interna: `docs/FACTURACION_ELECTRONICA_AFIP.md`
- Prompt de infraestructura: `agent-bootstrap/prompts/completados/v1.2.0-afip-infraestructura.md`
