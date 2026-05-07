<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; font-size: 14px; color: #333;">
    <p>Estimado/a cliente,</p>

    <p>
        Adjunto encontrará los informes de resultados de los siguientes protocolos:
    </p>

    <ul style="margin: 12px 0; padding-left: 20px;">
        @foreach($samples as $sample)
            <li><strong>{{ $sample->protocol_number }}</strong></li>
        @endforeach
    </ul>

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
