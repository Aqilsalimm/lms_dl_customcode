<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengingat Kelas Live - Drastha Learning</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #F4F7F9;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .wrapper {
            width: 100%;
            background-color: #F4F7F9;
            padding: 40px 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #FFFFFF;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.03);
            border: 1px solid #E2E8F0;
        }
        .header {
            background-color: #264790;
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            color: #FFFFFF;
            font-size: 24px;
            font-weight: 800;
            margin: 0;
            letter-spacing: -0.5px;
        }
        .header p {
            color: #E2E8F0;
            font-size: 14px;
            margin: 10px 0 0 0;
            font-weight: 500;
        }
        .content {
            padding: 40px 30px;
        }
        .content p {
            color: #4A5568;
            font-size: 16px;
            line-height: 1.6;
            margin: 0 0 20px 0;
        }
        .details-card {
            background-color: #F8FAFC;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid #EDF2F7;
            margin-bottom: 30px;
        }
        .details-row {
            display: flex;
            margin-bottom: 12px;
            border-bottom: 1px solid #EDF2F7;
            padding-bottom: 12px;
        }
        .details-row:last-child {
            margin-bottom: 0;
            border-bottom: none;
            padding-bottom: 0;
        }
        .details-label {
            font-weight: 700;
            color: #718096;
            width: 150px;
            font-size: 14px;
        }
        .details-value {
            color: #1A2B49;
            font-weight: 600;
            font-size: 14px;
            flex: 1;
        }
        .btn-container {
            text-align: center;
            margin-bottom: 30px;
        }
        .btn {
            display: inline-block;
            background-color: #44A6D9;
            color: #FFFFFF !important;
            text-decoration: none;
            font-weight: 700;
            padding: 14px 32px;
            border-radius: 9999px;
            font-size: 14px;
            box-shadow: 0 4px 14px rgba(68, 166, 217, 0.3);
            transition: all 0.2s ease;
        }
        .footer {
            background-color: #F8FAFC;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #EDF2F7;
        }
        .footer p {
            color: #A0AEC0;
            font-size: 12px;
            margin: 0 0 10px 0;
            line-height: 1.5;
        }
        .footer a {
            color: #264790;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <h1>Drastha Learning</h1>
                <p>Pengingat Jadwal Kelas Live</p>
            </div>
            
            <div class="content">
                <p>Halo <strong>{{ $instructorName }}</strong>,</p>
                <p>Ini adalah pengingat otomatis bahwa kelas live Anda akan dimulai dalam waktu kurang dari 24 jam. Pastikan Anda telah mempersiapkan materi dan siap pada waktu yang ditentukan.</p>
                
                <div class="details-card">
                    <div class="details-row">
                        <div class="details-label">Judul Kelas</div>
                        <div class="details-value">{{ $courseTitle }}</div>
                    </div>
                    <div class="details-row">
                        <div class="details-label">Waktu Mulai</div>
                        <div class="details-value">{{ $startDate }}</div>
                    </div>
                    <div class="details-row">
                        <div class="details-label">Zona Waktu</div>
                        <div class="details-value">{{ $timezone }}</div>
                    </div>
                    <div class="details-row">
                        <div class="details-label">Tipe Platform</div>
                        <div class="details-value">{{ $platform }}</div>
                    </div>
                </div>
                
                @if($meetingUrl)
                <div class="btn-container">
                    <a href="{{ $meetingUrl }}" class="btn" target="_blank">Gabung Kelas Live</a>
                </div>
                @endif
                
                <p style="margin-bottom: 0;">Terima kasih atas dedikasi Anda mengajar di Drastha Learning.</p>
            </div>
            
            <div class="footer">
                <p>Email ini dikirimkan secara otomatis oleh sistem Drastha Learning LMS.</p>
                <p>&copy; {{ date('Y') }} Drastha Berkah Sentosa. Hak Cipta Dilindungi.</p>
                <p>Butuh bantuan? Hubungi <a href="mailto:admin@drasthalearning.com">admin@drasthalearning.com</a></p>
            </div>
        </div>
    </div>
</body>
</html>
