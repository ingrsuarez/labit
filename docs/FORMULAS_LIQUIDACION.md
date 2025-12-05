# Fórmulas de Liquidación de Sueldos

## Índice
1. [Sueldo Básico](#1-sueldo-básico)
2. [Horas Extras](#2-horas-extras)
3. [Antigüedad](#3-antigüedad)
4. [Haberes Adicionales](#4-haberes-adicionales)
5. [Deducciones](#5-deducciones)
6. [Licencias y Novedades](#6-licencias-y-novedades)
7. [Totales](#7-totales)

---

## 1. Sueldo Básico

### Básico Proporcional (según jornada)
```
Básico Proporcional = (Horas Semanales del Empleado / Horas Base de la Categoría) × Sueldo Base de la Categoría
```

**Ejemplo:**
- Categoría: Administrativo con básico $500.000 y 48hs semanales
- Empleado trabaja 30hs semanales
- Básico Proporcional = (30 / 48) × $500.000 = **$312.500**

---

## 2. Horas Extras

### Valor Hora Base (CCT 108/75 FATSA-CADIME/CEDIM)
```
Valor Hora = Sueldo Básico / 204
```
> El divisor 204 corresponde a: 48 hs semanales × 4.25 semanas = 204

### Horas Extras 50% (días hábiles)
```
Horas Extras 50% = Valor Hora × 1.5 × Cantidad de Horas
```

### Horas Extras 100% (feriados y fines de semana)
```
Horas Extras 100% = Valor Hora × 2 × Cantidad de Horas
```

**Ejemplo:**
- Básico: $300.000
- Valor Hora = $300.000 / 204 = $1.470,59
- 8 horas extras 50% = $1.470,59 × 1.5 × 8 = **$17.647,06**
- 4 horas extras 100% = $1.470,59 × 2 × 4 = **$11.764,71**

---

## 3. Antigüedad

### Años de Antigüedad
```
Años = Fecha Fin del Período - Fecha de Ingreso (solo años completos)
```

### Cálculo de Antigüedad
```
Base de Antigüedad = Sueldo Básico + Total Horas Extras

Antigüedad = Base de Antigüedad × Años × 2%
```

**Según ley argentina:** 2% por cada año completo de antigüedad.

**Ejemplo:**
- Básico: $500.000
- Horas Extras: $50.000
- Años de antigüedad: 5
- Antigüedad = ($500.000 + $50.000) × 5 × 0.02 = **$55.000**

---

## 4. Haberes Adicionales

### Conceptos por Porcentaje
```
Importe = Base de Cálculo × (Porcentaje / 100)
```

### Bases de Cálculo Disponibles
| Base | Fórmula |
|------|---------|
| `basic` | Sueldo Básico |
| `basic_antiguedad` | Sueldo Básico + Antigüedad |
| `basic_hours` | Sueldo Básico + Horas Extras |
| `basic_hours_antiguedad` | Sueldo Básico + Horas Extras + Antigüedad |

### Conceptos por Monto Fijo
```
Importe = Valor Fijo del Concepto
```

### Conceptos por Monto Fijo Proporcional
```
Importe = Valor Fijo × (Horas Semanales Empleado / Horas Base Categoría)
```

**Ejemplo: Seguro de Fidelidad**
- Valor fijo: $218.471,74
- Empleado trabaja: 30 hs semanales
- Categoría base: 48 hs semanales
- Importe = $218.471,74 × (30 / 48) = **$136.544,84**

### Ejemplo: Zona 30%
- Base: Básico + Antigüedad = $500.000 + $55.000 = $555.000
- Zona 30% = $555.000 × 0.30 = **$166.500**

### Ejemplo: Adicional Título (15% del básico)
- Adicional Título = $500.000 × 0.15 = **$75.000**

---

## 5. Deducciones

### Base para Deducciones
```
Base Deducciones = Total Haberes Remunerativos (excluye No Remunerativos)
```

> **Importante:** Los conceptos marcados como "No Remunerativo" NO se incluyen en la base para calcular deducciones.

### Deducciones Legales (Argentina)

| Concepto | Porcentaje | Fórmula |
|----------|------------|---------|
| Jubilación | 11% | Base Remunerativa × 0.11 |
| INSS/PLEY 19032 | 3% | Base Remunerativa × 0.03 |
| Ley 23660 (Obra Social) | 2.55% | Base Remunerativa × 0.0255 |
| Aporte Solidario | 1% | Base Remunerativa × 0.01 |
| ANSSAL | 0.45% | Base Remunerativa × 0.0045 |

### Total Deducciones
```
Total Deducciones = Σ (Cada Deducción)
```

**Ejemplo:**
- Total Remunerativo: $1.500.000
- Jubilación = $1.500.000 × 0.11 = $165.000
- INSS/PLEY = $1.500.000 × 0.03 = $45.000
- Ley 23660 = $1.500.000 × 0.0255 = $38.250
- Aporte Solidario = $1.500.000 × 0.01 = $15.000
- ANSSAL = $1.500.000 × 0.0045 = $6.750
- **Total Deducciones = $270.000**

---

## 6. Licencias y Novedades

### Vacaciones
```
Días de Vacaciones según ley argentina:
- Hasta 5 años de antigüedad: 14 días corridos
- De 5 a 10 años: 21 días corridos
- De 10 a 20 años: 28 días corridos
- Más de 20 años: 35 días corridos

Descuento Básico por Vacaciones = Básico × (Días Vacaciones / 30)

Importe Vacaciones = Básico × (Días Vacaciones / 25)
```

> **Nota:** Para el cálculo de días hábiles de vacaciones se excluyen fines de semana y feriados nacionales.

### Enfermedad (con certificado aprobado)
```
Descuento Básico = Básico × (Días Enfermedad / 30)

Importe Enfermedad = Básico × (Días Enfermedad / 30)
```
> El empleado cobra los días de enfermedad pero se descuentan del básico trabajado.

### Inasistencia (sin certificado o no aprobado)
```
Descuento Inasistencia = Básico × (Días Inasistencia / 30)
```
> Los días de inasistencia NO se pagan, se descuentan del básico.

### Básico Trabajado (después de novedades)
```
Básico Trabajado = Básico Original - (Vacaciones + Enfermedad + Inasistencia)
```

---

## 7. Totales

### Total Haberes
```
Total Haberes = Básico Trabajado + Horas Extras + Antigüedad + Σ(Haberes Adicionales) + Σ(No Remunerativos)
```

### Total Remunerativo
```
Total Remunerativo = Total Haberes - Σ(No Remunerativos)
```

### Total No Remunerativo
```
Total No Remunerativo = Σ(Conceptos marcados como No Remunerativo)
```

### Neto a Cobrar
```
Neto a Cobrar = Total Haberes - Total Deducciones
```

---

## Resumen de Orden de Cálculo

1. **Básico Proporcional** (según horas semanales)
2. **Descuentos por novedades** (vacaciones, enfermedad, inasistencia)
3. **Horas Extras** (50% y 100%)
4. **Antigüedad** (sobre básico + horas extras)
5. **Haberes Adicionales** (según su base de cálculo configurada)
6. **Haberes No Remunerativos**
7. **Deducciones** (sobre total remunerativo)
8. **Neto a Cobrar**

---

## Referencias Legales

- **CCT 108/75 FATSA-CADIME/CEDIM** - Convenio Colectivo de Trabajo
- **Ley 20.744** - Ley de Contrato de Trabajo (Argentina)
- **Ley 23.660** - Obras Sociales
- **Ley 24.241** - Sistema Integrado de Jubilaciones y Pensiones

---

*Documento generado para el sistema IPAC - Diciembre 2025*

