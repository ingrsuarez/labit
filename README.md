# Labit

> Sistema integral de gestión para laboratorios clínicos y ambientales.

**Labit** es una plataforma web que centraliza la operación, administración y comercio de laboratorios de análisis clínicos y ambientales en un único entorno. Desde la recepción de muestras y emisión de resultados hasta la facturación, compras, gestión de personal y control de calidad.

Desarrollado sobre **Laravel 11** con interfaz moderna construida con **Tailwind CSS**, **Alpine.js** y **Livewire 3**, ofrece una experiencia ágil, responsive y segura con control de acceso basado en roles y permisos granulares.

---

## Módulos

| Módulo | Descripción |
|--------|-------------|
| **Laboratorio clínico** | Pacientes, admisiones con protocolo, determinaciones/tests, nomenclador por obra social, carga y validación de resultados, reportes mensuales |
| **Laboratorio de muestras** | Muestras de aguas y alimentos, determinaciones, protocolo en PDF, etiquetas con código de barras, envío por email |
| **Ventas y facturación** | Clientes, servicios, presupuestos, facturas de venta con IVA, puntos de venta, recibos de cobro |
| **Compras e inventario** | Proveedores, insumos con stock mínimo, movimientos de stock, flujo completo: cotización → OC → remito → factura → orden de pago |
| **Recursos humanos** | Legajos de empleados, organigrama, conceptos salariales, liquidación de sueldos (individual y masiva), vacaciones, ausencias/licencias, documentación |
| **Gestión de calidad** | No conformidades con seguimiento y acciones correctivas, circulares internas con firma digital |
| **Portal del empleado** | Dashboard personal, equipo, directorio, organigrama, recibos de sueldo, solicitudes de vacaciones/licencias, lectura y firma de circulares |

---

## Stack tecnológico

| Capa | Tecnología |
|------|------------|
| Backend | PHP 8.2+, Laravel 11 |
| Frontend | Blade, Livewire 3, Alpine.js |
| CSS | Tailwind CSS 3 |
| Base de datos | MySQL 8+ |
| Autenticación | Jetstream + Fortify + Sanctum |
| Roles y permisos | Spatie Laravel Permission |
| PDF | DomPDF + mPDF |
| Excel | Maatwebsite Excel |
| Códigos de barra | Picqer Barcode Generator |
| Build | Vite |
| Iconos | Bootstrap Icons |

---

## Requisitos

- PHP >= 8.2
- Composer
- Node.js >= 18 y npm
- MySQL >= 8.0
- Extensiones PHP: `mbstring`, `xml`, `bcmath`, `curl`, `zip`, `gd`, `soap`

---

## Instalación

```bash
# 1. Clonar el repositorio
git clone https://github.com/ingrsuarez/labit.git
cd labit

# 2. Instalar dependencias PHP
composer install

# 3. Instalar dependencias frontend
npm install

# 4. Configurar entorno
cp .env.example .env
php artisan key:generate

# 5. Configurar base de datos en .env
# DB_CONNECTION=mysql
# DB_DATABASE=labit
# DB_USERNAME=root
# DB_PASSWORD=

# 6. Ejecutar migraciones
php artisan migrate

# 7. Ejecutar seeders (roles, permisos, datos iniciales)
php artisan db:seed

# 8. Compilar assets
npm run build        # producción
# npm run dev        # desarrollo con hot reload

# 9. Iniciar el servidor
php artisan serve
```

La aplicación estará disponible en `http://localhost:8000`.

---

## Roles del sistema

| Rol | Alcance |
|-----|---------|
| **Administrador** | Acceso total a todos los módulos |
| **Contador** | Liquidaciones, compras, ventas |
| **Compras** | Módulo de compras e inventario |
| **Ventas** | Módulo de ventas y facturación |
| **Empleado** | Acceso exclusivo al portal del empleado |

---

## Estructura del proyecto

```
labit/
├── app/
│   ├── Http/Controllers/    → ~45 controladores
│   ├── Models/              → 51 modelos Eloquent
│   ├── Services/            → Lógica de negocio
│   ├── Exports/             → Exportaciones Excel
│   └── Mail/                → Mailables
├── database/
│   ├── migrations/          → 90 migraciones
│   └── seeders/             → Seeders de roles, permisos, datos
├── resources/views/         → 48 directorios de vistas Blade
├── routes/web.php           → Rutas principales
├── config/                  → Configuración
└── storage/                 → Archivos generados
```

---

## Licencia

Uso interno — proyecto propietario.
