<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; background-color: #f5f5f5; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <!-- Header -->
        <div style="background-color: #00838f; padding: 25px 30px; text-align: center;">
            <h1 style="color: #ffffff; margin: 0; font-size: 22px;">IPAC Laboratorio</h1>
            <p style="color: #b2dfdb; margin: 5px 0 0; font-size: 13px;">Laboratorio de Aguas y Alimentos</p>
        </div>

        <!-- Contenido -->
        <div style="padding: 30px;">
            <p style="font-size: 14px; color: #333; margin-bottom: 15px;">
                Estimado/a <strong>{{ $quote->customer_name }}</strong>,
            </p>

            <p style="font-size: 14px; color: #333; margin-bottom: 20px;">
                Le enviamos adjunto el presupuesto <strong>{{ $quote->quote_number }}</strong> con el detalle de las determinaciones solicitadas.
            </p>

            <!-- Resumen -->
            <div style="background-color: #f5f5f5; border-radius: 6px; padding: 20px; margin-bottom: 20px;">
                <h3 style="color: #00838f; margin: 0 0 12px; font-size: 14px; text-transform: uppercase;">Resumen del Presupuesto</h3>
                
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 4px 0; font-size: 13px; color: #666;">Número</td>
                        <td style="padding: 4px 0; font-size: 13px; color: #333; text-align: right; font-weight: bold;">{{ $quote->quote_number }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; font-size: 13px; color: #666;">Fecha</td>
                        <td style="padding: 4px 0; font-size: 13px; color: #333; text-align: right;">{{ $quote->created_at->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; font-size: 13px; color: #666;">Determinaciones</td>
                        <td style="padding: 4px 0; font-size: 13px; color: #333; text-align: right;">{{ $quote->items->count() }}</td>
                    </tr>
                    @if($quote->valid_until)
                    <tr>
                        <td style="padding: 4px 0; font-size: 13px; color: #666;">Válido hasta</td>
                        <td style="padding: 4px 0; font-size: 13px; color: #333; text-align: right;">{{ $quote->valid_until->format('d/m/Y') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td colspan="2" style="border-top: 1px solid #ddd; padding-top: 8px; margin-top: 8px;"></td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; font-size: 15px; color: #00838f; font-weight: bold;">TOTAL</td>
                        <td style="padding: 4px 0; font-size: 15px; color: #00838f; text-align: right; font-weight: bold;">${{ number_format($quote->total, 2, ',', '.') }}</td>
                    </tr>
                </table>
            </div>

            <p style="font-size: 13px; color: #666; margin-bottom: 5px;">
                Encontrará el presupuesto completo en el archivo PDF adjunto.
            </p>

            <p style="font-size: 13px; color: #666;">
                Ante cualquier consulta, no dude en contactarnos.
            </p>
        </div>

        <!-- Footer -->
        <div style="background-color: #f9f9f9; padding: 20px 30px; border-top: 1px solid #eee;">
            <p style="font-size: 12px; color: #999; margin: 0;">
                <strong>IPAC Laboratorio de Aguas y Alimentos</strong><br>
                Leguizamón 356 - Neuquén<br>
                TEL: 0299-6227547 | www.ipac.com.ar
            </p>
        </div>
    </div>
</body>
</html>
