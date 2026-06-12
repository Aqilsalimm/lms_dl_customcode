<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice - {{ $order->id }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333333;
            margin: 0;
            padding: 0;
            font-size: 14px;
            line-height: 1.4;
        }
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 10px;
        }
        table {
            width: 100%;
            line-height: inherit;
            text-align: left;
            border-collapse: collapse;
        }
        table td {
            padding: 8px;
            vertical-align: top;
        }
        table tr td:nth-child(2) {
            text-align: right;
        }
        .header-table td {
            padding-bottom: 40px;
        }
        .logo {
            font-size: 26px;
            font-weight: bold;
            color: #264790;
        }
        .logo span {
            color: #44A6D9;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: 800;
            color: #1A2B49;
            text-transform: uppercase;
            margin: 0;
        }
        .invoice-id {
            font-size: 14px;
            color: #718096;
            margin-top: 5px;
        }
        .details-table {
            margin-bottom: 40px;
        }
        .details-table td {
            padding-bottom: 20px;
        }
        .details-title {
            font-size: 12px;
            font-weight: bold;
            color: #718096;
            text-transform: uppercase;
            border-bottom: 1px solid #E2E8F0;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .item-table {
            width: 100%;
            margin-bottom: 40px;
        }
        .item-table th {
            background-color: #264790;
            color: #ffffff;
            text-align: left;
            padding: 10px;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .item-table th:last-child {
            text-align: right;
        }
        .item-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #F1F5F9;
        }
        .item-table tr.item td {
            font-weight: 600;
            color: #1A2B49;
        }
        .item-table tr.item td:last-child {
            text-align: right;
        }
        .totals-table {
            width: 50%;
            float: right;
            margin-bottom: 40px;
        }
        .totals-table td {
            padding: 6px 10px;
            font-size: 13px;
        }
        .totals-table td:last-child {
            text-align: right;
            font-weight: bold;
        }
        .totals-table tr.grand-total td {
            font-size: 16px;
            color: #264790;
            border-top: 2px solid #264790;
            padding-top: 10px;
        }
        .status-stamp {
            border: 3px solid #10B981;
            color: #10B981;
            font-size: 20px;
            font-weight: 800;
            text-transform: uppercase;
            padding: 8px 20px;
            border-radius: 8px;
            display: inline-block;
            margin-top: 20px;
            transform: rotate(-5deg);
        }
        .footer {
            border-top: 1px solid #E2E8F0;
            padding-top: 20px;
            text-align: center;
            font-size: 11px;
            color: #A0AEC0;
            position: absolute;
            bottom: 30px;
            left: 0;
            right: 0;
        }
    </style>
</head>
<body>
    <div class="invoice-box">
        <!-- Header -->
        <table class="header-table">
            <tr>
                <td>
                    <div class="logo">Drastha<span>Learning</span></div>
                    <div style="font-size: 11px; color: #718096; margin-top: 5px; line-height: 1.5;">
                        Jl. Raya Pondok Gede No. 27, Jakarta Timur<br>
                        info@drasthalearning.com<br>
                        +62 812-3456-7890
                    </div>
                </td>
                <td>
                    <div class="invoice-title">INVOICE</div>
                    <div class="invoice-id">INV/{{ $order->created_at->format('Ymd') }}/{{ $order->id }}</div>
                    <div style="font-size: 12px; color: #4A5568; margin-top: 10px;">
                        Tanggal: <strong>{{ $order->created_at->format('d M Y') }}</strong><br>
                        Status: <strong style="color: #10B981;">LUNAS (PAID)</strong>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Billing Info -->
        <table class="details-table">
            <tr>
                <td style="width: 50%;">
                    <div class="details-title">Diterbitkan Untuk</div>
                    <strong>{{ $user->name }}</strong><br>
                    Email: {{ $user->email }}<br>
                    Peran: {{ ucfirst($user->role) }}
                </td>
                <td style="width: 50%;">
                    <div class="details-title">Detail Pembayaran</div>
                    Metode: <strong>{{ strtoupper($order->payment_type ?? 'Midtrans') }}</strong><br>
                    Status Transaksi: <strong>Settlement</strong><br>
                    Waktu: {{ $order->updated_at->format('d M Y H:i') }} WIB
                </td>
            </tr>
        </table>

        <!-- Items Table -->
        <table class="item-table">
            <thead>
                <tr>
                    <th>Item Deskripsi</th>
                    <th>Tipe</th>
                    <th style="text-align: right;">Harga Satuan</th>
                </tr>
            </thead>
            <tbody>
                <tr class="item">
                    <td>{{ $buyable->title }}</td>
                    <td>{{ $order->buyable_type === 'App\Models\Course' ? 'Course' : 'Bundle' }}</td>
                    <td style="text-align: right;">Rp {{ number_format($buyable->price, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Totals & Stamp -->
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%; vertical-align: middle;">
                    <div class="status-stamp">LUNAS</div>
                </td>
                <td style="width: 50%;">
                    <table class="totals-table" style="width: 100%;">
                        <tr>
                            <td style="color: #718096;">Subtotal:</td>
                            <td>Rp {{ number_format($buyable->price, 0, ',', '.') }}</td>
                        </tr>
                        @if($order->discount_amount > 0)
                        <tr>
                            <td style="color: #E53E3E;">Diskon (Kupon):</td>
                            <td style="color: #E53E3E;">- Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                        <tr class="grand-total">
                            <td>Total Bayar:</td>
                            <td>Rp {{ number_format($order->amount, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Footer -->
        <div class="footer">
            Terima kasih telah mempercayakan pembelajaran Anda bersama Drastha Learning.<br>
            Invoice ini sah dan dihasilkan secara otomatis oleh sistem komputer.
        </div>
    </div>
</body>
</html>
