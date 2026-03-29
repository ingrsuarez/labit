# Facturación Electrónica AFIP (WSFEv1) — Guía de Implementación

## Resumen

Implementación completa de facturación electrónica argentina usando el Web Service de Facturación Electrónica versión 1 (WSFEv1) de AFIP/ARCA. Soporta multi-empresa, Facturas A/B/C, Notas de Crédito, generación de PDF con QR obligatorio (RG 4892) y manejo de Puntos de Venta electrónicos.

**Stack**: Laravel 11 + PHP 8.2 + mPDF + SOAP nativo + BaconQrCode

---

## Arquitectura

```
app/
├── Services/
│   └── AfipService.php          # Servicio central (WSAA + WSFEv1)
├── Models/
│   ├── Company.php              # Empresa (certificados AFIP por empresa)
│   ├── PointOfSale.php          # Punto de Venta (electrónico o manual)
│   ├── SalesInvoice.php         # Factura con campos AFIP
│   └── Customer.php             # Cliente (condición IVA, CUIT/DNI)
├── Http/Controllers/
│   └── SalesInvoiceController.php  # Store, PDF, retryAfip
config/
│   └── afip.php                 # Config por defecto (fallback si no hay empresa)
resources/views/sales-invoices/
│   └── pdf.blade.php            # Template PDF con footer ARCA (QR + CAE)
database/migrations/
│   ├── create_companies_table           # Incluye afip_cert_path, afip_key_path, afip_production
│   ├── create_points_of_sale_table      # PV base
│   ├── add_electronic_fields_to_points_of_sale  # is_electronic, afip_pos_number
│   └── add_afip_fields_to_sales_invoices        # cae, cae_expiration, afip_voucher_number, etc.
```

---

## 1. Base de Datos

### Tabla `companies`

Cada empresa tiene su propia configuración AFIP. Los campos clave son:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `cuit` | string, unique | CUIT de la empresa (ej: `30-71922759-3`) |
| `tax_condition` | string | "IVA Responsable Inscripto", "Monotributista", etc. |
| `afip_cert_path` | string, nullable | Ruta relativa al certificado `.crt` desde `base_path()` |
| `afip_key_path` | string, nullable | Ruta relativa a la clave privada `.pem` desde `base_path()` |
| `afip_production` | boolean | `false` = homologación, `true` = producción |

```php
// Migración
$table->string('afip_cert_path')->nullable();
$table->string('afip_key_path')->nullable();
$table->boolean('afip_production')->default(false);
```

### Tabla `points_of_sale`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `company_id` | FK | Empresa dueña |
| `code` | string(5) | Código visual (ej: `00002`) |
| `is_electronic` | boolean | Si es electrónico, se usa AFIP |
| `afip_pos_number` | int, nullable | Número registrado en AFIP |

```php
// Migración adicional
$table->boolean('is_electronic')->default(false)->after('is_active');
$table->unsignedInteger('afip_pos_number')->nullable()->after('is_electronic');
```

### Tabla `sales_invoices` — Campos AFIP

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `cae` | string(20) | Código de Autorización Electrónica |
| `cae_expiration` | date | Fecha de vencimiento del CAE |
| `afip_voucher_number` | unsigned int | Número de comprobante asignado por AFIP |
| `afip_result` | string(20) | `A` (aprobado), `R` (rechazado), `O` (observado) |
| `afip_response` | json | Respuesta completa de AFIP |
| `is_electronic` | boolean | Si fue emitida por web service |

```php
// Migración
Schema::table('sales_invoices', function (Blueprint $table) {
    $table->string('cae', 20)->nullable();
    $table->date('cae_expiration')->nullable();
    $table->unsignedInteger('afip_voucher_number')->nullable();
    $table->string('afip_result', 20)->nullable();
    $table->json('afip_response')->nullable();
    $table->boolean('is_electronic')->default(false);
});
```

---

## 2. Servicio AFIP (`AfipService`)

### Inicialización

El constructor resuelve certificados desde la empresa activa o desde `config/afip.php`:

```php
public function __construct(?Company $company = null)
{
    $company = $company ?? active_company();

    $this->cuit = $company?->cuit
        ? str_replace('-', '', $company->cuit)
        : config('afip.cuit');
    $this->certPath = $company?->afip_cert_path
        ? base_path($company->afip_cert_path)
        : base_path(config('afip.cert_path'));
    $this->keyPath = $company?->afip_key_path
        ? base_path($company->afip_key_path)
        : base_path(config('afip.key_path'));
    $this->production = $company?->afip_production
        ?? (bool) config('afip.production');
}
```

### Endpoints WSAA y WSFEv1

```
Homologación:
  WSAA: https://wsaahomo.afip.gov.ar/ws/services/LoginCms?WSDL
  WSFE: https://wswhomo.afip.gov.ar/wsfev1/service.asmx?WSDL

Producción:
  WSAA: https://wsaa.afip.gov.ar/ws/services/LoginCms?WSDL
  WSFE: https://servicios1.afip.gov.ar/wsfev1/service.asmx?WSDL
```

### Flujo de Autenticación (WSAA)

1. Se genera un TRA (Ticket de Requerimiento de Acceso) XML con `uniqueId`, `generationTime`, `expirationTime` y `service=wsfe`.
2. Se firma el TRA con PKCS#7 usando el certificado y clave privada de la empresa.
3. Se envía el CMS firmado a `loginCms` vía SOAP.
4. Se recibe un TA (Ticket de Acceso) con `token` y `sign`.
5. El TA se cachea en `storage/app/afip/ta_wsfe_{cuit}_{env}.json` durante 11 horas.

### Creación de Comprobante (`createVoucher`)

Flujo completo:

1. Se determina el tipo de comprobante AFIP (Factura A=1, B=6, C=11).
2. Se consulta el último número autorizado (`FECompUltimoAutorizado`).
3. Se arma el request `FECAESolicitar` con:
   - `Concepto`: 3 (servicios)
   - `DocTipo`: 80 (CUIT) o 99 (sin identificar, para Consumidor Final)
   - `DocNro`: CUIT del receptor (0 si es Consumidor Final)
   - `CondicionIvaReceptor`: según la condición IVA del cliente (ver tabla abajo)
   - IVA desglosado por alícuota
   - Tributos opcionales (percepciones, otros impuestos)
4. Se envía y se procesa la respuesta (CAE, fecha vencimiento, resultado).

### Mapeo de Condición IVA Receptor

```
Para Factura A:
  Responsable Inscripto → 1
  Monotributista → 6

Para Factura B:
  Exento → 4
  Consumidor Final → 5
  Monotributista → 6
```

### Mapeo de Alícuotas IVA

```
0%    → Id 3
10.5% → Id 4
21%   → Id 5
27%   → Id 6
```

### Inyección de `CondicionIVAReceptorId` (RG 5616)

AFIP requiere el campo `CondicionIVAReceptorId` desde RG 5616 pero el WSDL oficial no lo incluye. Se usa una clase `AfipSoapClient` que extiende `SoapClient` y modifica el XML SOAP antes de enviarlo:

```php
class AfipSoapClient extends SoapClient
{
    private ?int $condicionIvaReceptorId = null;

    public function __doRequest($request, $location, $action, $version, $oneWay = false): ?string
    {
        if ($this->condicionIvaReceptorId !== null && strpos($request, 'FECAESolicitar') !== false) {
            // Inyecta <CondicionIVAReceptorId> después de <DocNro>
            $pattern = '/<([^>]*?)DocNro>(\d+)<\/([^>]*?)DocNro>/';
            // ... inserta el tag via regex
        }
        return parent::__doRequest(...);
    }
}
```

---

## 3. Controlador — Flujo de Emisión

En `SalesInvoiceController::store()`:

```php
// 1. Detectar si el PV es electrónico
$pointOfSale = PointOfSale::find($request->point_of_sale_id);
$isElectronic = $pointOfSale && $pointOfSale->is_electronic;

// 2. Si es electrónico, el invoice_number es nullable (lo asigna AFIP)
$invoiceNumberRules = $isElectronic
    ? 'nullable|string'
    : 'required|string|unique:...';

// 3. Crear la factura
$invoice = SalesInvoice::create([...  'is_electronic' => $isElectronic]);

// 4. Si es electrónica, solicitar CAE
if ($isElectronic) {
    $afip = new AfipService();
    $result = $afip->createVoucher($invoice);

    if ($result['result'] === 'A' || $result['result'] === 'O') {
        $invoice->update([
            'cae' => $result['cae'],
            'cae_expiration' => $result['cae_expiration'],
            'afip_voucher_number' => $result['voucher_number'],
            'afip_result' => $result['result'],
            'afip_response' => $result['full_response'],
            'invoice_number' => str_pad($result['voucher_number'], 8, '0', STR_PAD_LEFT),
        ]);
    }
}
```

### Endpoint `nextNumber`

Consulta AJAX para obtener el próximo número de comprobante:

```php
if ($pos->is_electronic) {
    $afip = new AfipService();
    $voucherTypeId = AfipService::getVoucherTypeId($request->voucher_type);
    $last = $afip->getLastVoucher($pos->afip_pos_number, $voucherTypeId);
    return response()->json([
        'next_number' => str_pad($last + 1, 8, '0', STR_PAD_LEFT),
        'is_electronic' => true,
    ]);
}
```

### Reintentar AFIP (`retryAfip`)

Permite reintentar la autorización si falló la primera vez (la factura existe con `is_electronic=true` pero sin CAE).

---

## 4. PDF con Footer ARCA (QR + CAE)

### Dependencias

```bash
composer require carlos-meneses/laravel-mpdf
composer require bacon/bacon-qr-code
```

### Generación del QR (RG 4892)

El QR se genera en el **controlador** (no en la vista) porque mPDF necesita los datos pre-calculados para el footer:

```php
// Datos del QR según especificación AFIP
$qrJson = json_encode([
    'ver' => 1,
    'fecha' => $invoice->issue_date->format('Y-m-d'),
    'cuit' => (int) $cuit,
    'ptoVta' => $pos->afip_pos_number,
    'tipoCmp' => $voucherTypeId,
    'nroCmp' => (int) $invoice->afip_voucher_number,
    'importe' => $total,
    'moneda' => 'PES',
    'ctz' => 1,
    'tipoDocRec' => 80 o 99,
    'nroDocRec' => (int) $cuitReceptor,
    'tipoCodAut' => 'E',
    'codAut' => (int) $invoice->cae,
]);
$qrUrl = 'https://www.afip.gob.ar/fe/qr/?p=' . base64_encode($qrJson);

// Renderizar QR como SVG data URI
$renderer = new ImageRenderer(
    new RendererStyle(200),
    new SvgImageBackEnd()
);
$qrSvg = (new Writer($renderer))->writeString($qrUrl);
$qrDataUri = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);
```

### Footer fijo al pie de página con mPDF

Se usa `<htmlpagefooter>` de mPDF para que el bloque ARCA aparezca siempre al pie de la página (no inline con el contenido):

```html
<!-- Se define ANTES del contenido para aplicarse desde la página 1 -->
<htmlpagefooter name="arcaFooter">
    <div style="border-top: 2px solid #333; padding-top: 8px;">
        <table width="100%">
            <tr>
                <td style="width: 110px;">
                    <img src="{{ $qrDataUri }}" style="width: 100px; height: 100px;">
                </td>
                <td>
                    ARCA - AGENCIA DE RECAUDACIÓN Y CONTROL ADUANERO
                    CAE: {{ $invoice->cae }}
                    Fecha de Vto. del CAE: {{ $invoice->cae_expiration }}
                </td>
            </tr>
        </table>
        <div>{{ $barcodeNumerico }}</div>
        <div>{{ $empresa }} — CUIT {{ $cuit }} — {{ $condicionIva }}</div>
    </div>
</htmlpagefooter>
<sethtmlpagefooter name="arcaFooter" value="on" />
```

### Márgenes del PDF

```php
$pdf = PDF::loadView('sales-invoices.pdf', $data, [], [
    'margin_top' => 10,
    'margin_bottom' => $hasFooter ? 52 : 15,  // Reservar espacio para el footer ARCA
    'margin_footer' => 5,                       // 5mm desde el borde inferior
    'margin_left' => 12,
    'margin_right' => 12,
    'format' => 'A4',
]);
```

### Código de barras numérico

Código de verificación compuesto por: CUIT + Tipo Cbte (3 dígitos) + PV (5 dígitos) + CAE + Fecha Vto CAE + Dígito Verificador (módulo 10).

---

## 5. Configuración AFIP — Paso a Paso

### 5.1 Generar clave privada y CSR

```bash
openssl genrsa -out ipac_key.pem 2048

openssl req -new -key ipac_key.pem \
  -subj "/C=AR/O=MI EMPRESA SAS/CN=MI EMPRESA SAS/serialNumber=CUIT 30XXXXXXXX3" \
  -out ipac_csr.csr
```

### 5.2 Obtener certificado en AFIP

1. Ir a **AFIP > Administración de Certificados Digitales**.
2. Crear un nuevo **Alias** (ej: `mi-sistema`).
3. Subir el `.csr` generado.
4. Descargar el `.crt` firmado por AFIP.

### 5.3 Autorizar Web Service

En **Administrador de Relaciones de Clave Fiscal**:
- La empresa (CUIT de la empresa, no personal) debe autorizar a sí misma para el servicio **"Facturación Electrónica"**.
- La relación es: `{CUIT_empresa}-{CUIT_empresa}-ws://wsfe-{CUIT_empresa}`.

### 5.4 Registrar Punto de Venta

En **AFIP > ABM Puntos de Venta**: crear un PV con tipo **"Web Services"**.

### 5.5 Configurar en la app

```php
Company::updateOrCreate(
    ['cuit' => '30-XXXXXXXX-X'],
    [
        'afip_cert_path' => 'customer_files/arca/mi-cert.crt',  // Relativa a base_path()
        'afip_key_path' => 'customer_files/arca/mi-key.pem',
        'afip_production' => true,
    ]
);

PointOfSale::updateOrCreate(
    ['company_id' => $company->id, 'afip_pos_number' => 2],
    [
        'code' => '00002',
        'name' => 'Sucursal (Web Service)',
        'is_electronic' => true,
        'is_active' => true,
    ]
);
```

### 5.6 Directorio de tokens

Crear `storage/app/afip/` con permisos de escritura:

```bash
mkdir -p storage/app/afip
chmod 775 storage/app/afip
```

---

## 6. Problemas Conocidos y Soluciones

### SSL "dh key too small"

Los servidores de AFIP usan parámetros Diffie-Hellman débiles. Solución: bajar el nivel de seguridad SSL:

```php
'stream_context' => stream_context_create([
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'ciphers' => 'DEFAULT@SECLEVEL=0',
    ],
]),
```

### "Computador no autorizado"

La empresa debe tener una **relación de Clave Fiscal** donde ella misma se autorice para Facturación Electrónica. No alcanza con la relación del representante personal.

### Error 10243 — "Condición IVA receptor no válida"

El tipo de comprobante no coincide con la condición IVA del receptor:
- Factura A: solo para Responsable Inscripto o Monotributista
- Factura B: para Consumidor Final, Exento, Monotributista
- No emitir Factura B a un Responsable Inscripto con CUIT

### "ya posee un TA" (WSAA)

El servicio reporta que ya hay un Token de Acceso vigente que no tenemos cacheado (puede pasar si se eliminó el archivo de cache). El servicio implementa retry automático con espera de 5 segundos.

### Numeración — Facturas manuales previas

Si ya existen facturas emitidas manualmente en AFIP para un PV, el sistema consulta `FECompUltimoAutorizado` y continúa desde ahí. No hay conflicto.

---

## 7. Archivos Sensibles

Los certificados (`.crt`, `.pem`, `.key`) se guardan en `customer_files/arca/` y están excluidos del repositorio vía `.gitignore`:

```
customer_files/
```

Se deben copiar manualmente al servidor de producción.

---

## 8. Dependencias Composer

```json
{
    "carlos-meneses/laravel-mpdf": "*",
    "bacon/bacon-qr-code": "^3.0"
}
```

- **carlos-meneses/laravel-mpdf**: Wrapper de mPDF para Laravel. Soporta `<htmlpagefooter>` para footers fijos.
- **bacon/bacon-qr-code**: Genera QR como SVG, compatible con data URIs para incrustar en PDFs.

---

## 9. Config Fallback (`config/afip.php`)

Si la empresa activa no tiene certificados propios, se usan valores de `.env`:

```php
return [
    'cuit' => env('AFIP_CUIT'),
    'cert_path' => env('AFIP_CERT_PATH', storage_path('app/afip/cert.pem')),
    'key_path' => env('AFIP_KEY_PATH', storage_path('app/afip/key.pem')),
    'production' => env('AFIP_PRODUCTION', false),
    'emisor' => [
        'razon_social' => env('AFIP_RAZON_SOCIAL'),
        'domicilio' => env('AFIP_DOMICILIO'),
        'condicion_iva' => env('AFIP_CONDICION_IVA'),
        'inicio_actividades' => env('AFIP_INICIO_ACTIVIDADES'),
        'ingresos_brutos' => env('AFIP_IIBB'),
    ],
];
```
