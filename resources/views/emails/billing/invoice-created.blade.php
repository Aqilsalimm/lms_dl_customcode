<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tagihan Baru - Drastha Learning</title>
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f4f7f9; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f4f7f9; padding-bottom: 40px; }
        .main { background-color: #ffffff; margin: 0 auto; width: 100%; max-width: 600px; border-spacing: 0; color: #1a2b49; border-radius: 16px; overflow: hidden; margin-top: 40px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .header { background-color: #264790; padding: 40px; text-align: center; }
        .content { padding: 40px; line-height: 1.6; }
        .invoice-box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; margin: 24px 0; }
        .btn { display: inline-block; background-color: #264790; color: #ffffff !important; padding: 14px 28px; border-radius: 12px; text-decoration: none; font-weight: bold; font-size: 16px; margin-top: 20px; transition: background-color 0.3s; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #64748b; }
        h1 { margin: 0; font-size: 24px; font-weight: 800; color: #ffffff; }
        h2 { margin: 0 0 16px; font-size: 20px; font-weight: 700; color: #1a2b49; }
    </style>
</head>
<body>
    <div class="wrapper">
        <table class="main">
            <tr>
                <td class="header">
                    <h1>Drastha Learning</h1>
                </td>
            </tr>
            <tr>
                <td class="content">
                    <h2>Halo, {{ $invoice->subscription->user->name }}</h2>
                    <p>Tagihan baru untuk kelas <strong style="color: #264790;">{{ $invoice->subscription->course->title }}</strong> telah diterbitkan.</p>
                    
                    <p>Silakan segera lakukan pembayaran sebelum tanggal jatuh tempo untuk menghindari penangguhan akses kelas Anda.</p>

                    <div class="invoice-box">
                        <table width="100%">
                            <tr>
                                <td style="padding-bottom: 8px; font-size: 14px; color: #64748b;">Nomor Tagihan</td>
                                <td style="padding-bottom: 8px; font-size: 14px; font-weight: 700; text-align: right;">{{ $invoice->invoice_number }}</td>
                            </tr>
                            <tr>
                                <td style="padding-bottom: 8px; font-size: 14px; color: #64748b;">Total Tagihan</td>
                                <td style="padding-bottom: 8px; font-size: 18px; font-weight: 800; text-align: right; color: #264790;">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td style="font-size: 14px; color: #64748b;">Tanggal Jatuh Tempo</td>
                                <td style="font-size: 14px; font-weight: 700; text-align: right;">{{ \Carbon\Carbon::parse($invoice->due_date)->translatedFormat('d F Y') }}</td>
                            </tr>
                        </table>
                    </div>

                    <p>Klik tombol di bawah ini untuk melihat detail tagihan dan melakukan pembayaran melalui Midtrans.</p>

                    <div style="text-align: center;">
                        <a href="{{ url('/billing/suspended?invoice_id=' . $invoice->id) }}" class="btn">Bayar Sekarang</a>
                    </div>

                    <p style="margin-top: 30px; font-size: 14px; color: #64748b;">Jika Anda memiliki kendala, silakan hubungi tim dukungan kami melalui WhatsApp di 0812-3485-9768.</p>
                </td>
            </tr>
        </table>
        <div class="footer">
            <p>&copy; 2026 Drastha Learning. All Rights Reserved.<br>
            Jl Budi Luhur B/2 Wagir Indah Kwangsan, Sedati Sidoarjo</p>
        </div>
    </div>
</body>
</html>
