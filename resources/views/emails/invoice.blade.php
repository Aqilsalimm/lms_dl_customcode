<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice Pembayaran Drastha Learning</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #F8FAFC;
            color: #1A2B49;
            margin: 0;
            padding: 40px 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.02);
            border: 1px solid #E2E8F0;
        }
        .header {
            background: linear-gradient(135deg, #264790 0%, #44A6D9 100%);
            padding: 40px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .header p {
            margin: 10px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .content {
            padding: 40px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #1A2B49;
        }
        .body-text {
            font-size: 15px;
            line-height: 1.6;
            color: #4A5568;
            margin-bottom: 30px;
        }
        .details-card {
            background-color: #F8FAFC;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid #E2E8F0;
            margin-bottom: 30px;
        }
        .details-row {
            margin-bottom: 12px;
            font-size: 14px;
            overflow: hidden;
        }
        .details-row:last-child {
            margin-bottom: 0;
            padding-top: 12px;
            border-top: 1px dashed #E2E8F0;
        }
        .label {
            color: #718096;
            font-weight: 500;
            float: left;
        }
        .value {
            color: #1A2B49;
            font-weight: 700;
            float: right;
            text-align: right;
        }
        .btn {
            display: inline-block;
            background-color: #264790;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 30px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 15px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(38, 71, 144, 0.2);
            transition: all 0.3s ease;
        }
        .footer {
            background-color: #F8FAFC;
            padding: 30px 40px;
            text-align: center;
            font-size: 12px;
            color: #A0AEC0;
            border-top: 1px solid #E2E8F0;
            clear: both;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Pembayaran Berhasil!</h1>
            <p>Terima kasih atas pendaftaran Anda di Drastha Learning</p>
        </div>
        <div class="content">
            <div class="greeting">Halo, {{ $user->name }}!</div>
            <p class="body-text">
                Pembayaran Anda untuk pembelian kelas di platform kami telah berhasil diterima dan dikonfirmasi. Dokumen invoice resmi telah kami lampirkan pada email ini dalam format PDF.
            </p>
            
            <div class="details-card">
                <div class="details-row">
                    <span class="label">Nomor Invoice</span>
                    <span class="value">INV-DRSTH-{{ $order->id }}</span>
                </div>
                <div class="details-row">
                    <span class="label">Produk</span>
                    <span class="value">{{ $buyable->title }}</span>
                </div>
                <div class="details-row">
                    <span class="label">Tanggal Transaksi</span>
                    <span class="value">{{ $order->updated_at->format('d M Y H:i') }} WIB</span>
                </div>
                <div class="details-row">
                    <span class="label">Metode Pembayaran</span>
                    <span class="value">{{ strtoupper($order->payment_type ?? 'Midtrans') }}</span>
                </div>
                <div class="details-row">
                    <span class="label">Total Pembayaran</span>
                    <span class="value" style="color: #264790; font-size: 16px;">Rp {{ number_format($order->amount, 0, ',', '.') }}</span>
                </div>
            </div>

            <div style="text-align: center; margin-top: 10px;">
                <a href="{{ url('/dashboard') }}" class="btn">Mulai Belajar Sekarang</a>
            </div>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} Drastha Learning. All rights reserved.</p>
            <p>Jika Anda memiliki pertanyaan, silakan hubungi tim support kami.</p>
        </div>
    </div>
</body>
</html>
