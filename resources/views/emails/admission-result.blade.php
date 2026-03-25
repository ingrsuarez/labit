<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; font-size: 14px; color: #333;">
    <p>Estimado/a cliente,</p>

    <p>Adjunto encontrará el informe de resultados del protocolo
       <strong>{{ $admission->protocol_number }}</strong>.</p>

    @if($customMessage)
        <p>{{ $customMessage }}</p>
    @endif

    <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">

    @if($signature)
        {!! $signature !!}
    @else
        <p>Atentamente,<br>Laboratorio IPAC</p>
    @endif
</body>
</html>
