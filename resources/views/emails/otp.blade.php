<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Kode OTP Drastha Learning</title>
    <style>
        body {
            font-family: 'Inter', Helvetica, Arial, sans-serif;
            background-color: #f3f4f6;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }
        .header {
            background-color: #0f172a;
            padding: 30px;
            text-align: center;
        }
        .content {
            padding: 40px 30px;
            text-align: center;
            color: #1f2937;
        }
        .content h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #0f172a;
        }
        .content p {
            font-size: 16px;
            line-height: 24px;
            color: #4b5563;
            margin-bottom: 30px;
        }
        .otp-code {
            display: inline-block;
            font-size: 36px;
            font-weight: 800;
            letter-spacing: 6px;
            color: #2563eb;
            background-color: #eff6ff;
            padding: 15px 30px;
            border-radius: 8px;
            border: 1px dashed #2563eb;
            margin-bottom: 30px;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
            border-top: 1px solid #f3f4f6;
        }
        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <span style="font-size: 20px; font-weight: bold; color: #ffffff; letter-spacing: 1px;">DRASTHA LEARNING</span>
        </div>
        <div class="content">
            <h1>Kode Verifikasi Keamanan</h1>
            <p>Gunakan kode OTP di bawah ini untuk memverifikasi pendaftaran atau akses masuk Anda di Drastha Learning. Kode ini berlaku selama 10 menit.</p>
            <div class="otp-code">{{ $otpCode }}</div>
            <p style="font-size: 14px; color: #9ca3af; margin-bottom: 0;">Jika Anda tidak merasa meminta kode ini, abaikan saja email ini.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} Drastha Learning. All rights reserved.</p>
            <p>Sistem E-Learning Modern & Profesional</p>
        </div>
    </div>
</body>
</html>
