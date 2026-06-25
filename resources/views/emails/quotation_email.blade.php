<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Penawaran Harga {{ $quotation->quotationNo }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            color: #333;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .wrapper {
            max-width: 600px;
            margin: 30px auto;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .header {
            background: #1a56db;
            color: #fff;
            padding: 24px 32px;
        }
        .header h1 {
            margin: 0 0 4px 0;
            font-size: 20px;
        }
        .header p {
            margin: 0;
            font-size: 12px;
            opacity: 0.85;
        }
        .body {
            padding: 28px 32px;
        }
        .body p {
            margin: 0 0 14px 0;
            line-height: 1.6;
        }
        .info-box {
            background: #f0f5ff;
            border-left: 4px solid #1a56db;
            border-radius: 4px;
            padding: 14px 18px;
            margin: 18px 0;
        }
        .info-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-box td {
            padding: 4px 0;
            font-size: 13px;
        }
        .info-box td:first-child {
            color: #666;
            width: 160px;
        }
        .info-box td:last-child {
            font-weight: 600;
        }
        .validity-notice {
            background: #fef9c3;
            border: 1px solid #fde047;
            border-radius: 4px;
            padding: 10px 14px;
            font-size: 12px;
            color: #713f12;
            margin: 18px 0;
        }
        .footer {
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            padding: 16px 32px;
            font-size: 11px;
            color: #9ca3af;
            text-align: center;
        }
        .amount {
            font-size: 20px;
            font-weight: bold;
            color: #1a56db;
        }
    </style>
</head>
<body>
<div class="wrapper">

    <div class="header">
        <h1>Radhiyan Pet &amp; Care</h1>
        <p>Pet Clinic &middot; Pet Hotel &middot; Grooming &middot; Pet Shop</p>
    </div>

    <div class="body">
        <p>Yth. <strong>{{ $quotation->customerName }}</strong>,</p>

        <p>
            Terima kasih telah mempercayakan kebutuhan hewan peliharaan Anda kepada kami.
            Berikut kami sampaikan <strong>Penawaran Harga</strong> dari <strong>Radhiyan Pet &amp; Care</strong>
            cabang <strong>{{ $quotation->locationName }}</strong>.
        </p>

        <div class="info-box">
            <table>
                <tr>
                    <td>No. Penawaran</td>
                    <td>{{ $quotation->quotationNo }}</td>
                </tr>
                <tr>
                    <td>Jenis Layanan</td>
                    <td>
                        @php
                            $serviceLabel = match($quotation->typeOfService) {
                                'clinic'   => 'Pet Clinic',
                                'hotel'    => 'Pet Hotel',
                                'salon'    => 'Salon',
                                'grooming' => 'Grooming',
                                'shop'     => 'Pet Shop',
                                default    => ucfirst($quotation->typeOfService),
                            };
                        @endphp
                        {{ $serviceLabel }}
                    </td>
                </tr>
                @if($quotation->petName)
                <tr>
                    <td>Hewan Peliharaan</td>
                    <td>{{ $quotation->petName }}</td>
                </tr>
                @endif
                <tr>
                    <td>Berlaku Hingga</td>
                    <td style="color: #dc2626;">
                        {{ \Carbon\Carbon::parse($quotation->validUntil)->format('d/m/Y') }}
                    </td>
                </tr>
                <tr>
                    <td>Total Penawaran</td>
                    <td class="amount">
                        Rp {{ number_format($quotation->finalAmount, 0, ',', '.') }}
                    </td>
                </tr>
            </table>
        </div>

        @if($quotation->notes)
        <p><strong>Catatan:</strong><br>{{ $quotation->notes }}</p>
        @endif

        <div class="validity-notice">
            ⚠ Penawaran ini berlaku hingga
            <strong>{{ \Carbon\Carbon::parse($quotation->validUntil)->format('d/m/Y') }}</strong>.
            Setelah tanggal tersebut, harga dapat berubah sewaktu-waktu.
        </div>

        <p>
            Detail lengkap penawaran tersedia pada lampiran PDF di email ini.
            Jika ada pertanyaan, jangan ragu untuk menghubungi kami.
        </p>

        <p>Hormat kami,<br><strong>Tim Radhiyan Pet &amp; Care</strong><br>
        {{ $quotation->locationName }}<br>
        Call Center: 081312245500</p>
    </div>

    <div class="footer">
        Email ini dikirim secara otomatis oleh sistem Radhiyan Pet &amp; Care.
        Harap tidak membalas email ini.
    </div>

</div>
</body>
</html>
