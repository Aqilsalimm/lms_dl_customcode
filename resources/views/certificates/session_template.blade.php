<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Sertifikat {{ $sessionTitle ?? 'Penyelesaian Sesi' }}</title>
    <style>
        @page {
            margin: 0px;
            size: a4 landscape;
        }
        body {
            margin: 0px;
            padding: 0px;
            font-family: 'Helvetica', 'Arial', sans-serif;
            width: 100%;
            height: 100%;
            position: relative;
            background-color: #ffffff;
            @if(!empty($background))
            background-image: url('{{ $background }}');
            background-size: 100% 100%;
            background-repeat: no-repeat;
            background-position: center center;
            @endif
        }
        .certificate-wrapper {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        .name-container {
            position: absolute;
            top: {{ $nameYPosition ?? 44 }}%;
            width: 100%;
            text-align: center;
            font-size: 34px;
            font-weight: bold;
            color: #1A2B49;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 0 40px;
            box-sizing: border-box;
        }
        .title-container {
            position: absolute;
            top: {{ $titleYPosition ?? 56 }}%;
            width: 100%;
            text-align: center;
            font-size: 20px;
            color: #264790;
            padding: 0 50px;
            box-sizing: border-box;
            line-height: 1.4;
        }
        .title-container strong {
            font-size: 23px;
            color: #1A2B49;
            display: block;
            margin-top: 4px;
        }
        .code-container {
            position: absolute;
            bottom: 30px;
            left: 50px;
            font-size: 11px;
            color: #64748B;
            font-family: monospace;
        }
        .date-container {
            position: absolute;
            bottom: 30px;
            right: 50px;
            font-size: 12px;
            color: #475569;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="certificate-wrapper">
        <div class="name-container">
            {{ $studentName }}
        </div>
        <div class="title-container">
            Telah berhasil menyelesaikan bab/sesi pembelajaran:
            <strong>{{ $sessionTitle }}</strong>
        </div>
        @if(!empty($certCode))
        <div class="code-container">
            ID Sertifikat: {{ $certCode }}
        </div>
        @endif
        <div class="date-container">
            Diterbitkan: {{ $date }}
        </div>
    </div>
</body>
</html>
