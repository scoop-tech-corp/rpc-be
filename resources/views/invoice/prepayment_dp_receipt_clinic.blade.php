<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Tanda Terima DP - {{ $registration_no }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.3;
            color: #000;
            padding: 15px;
            margin: 0;
        }

        .header {
            margin-bottom: 15px;
        }

        .header-title {
            text-align: center;
            margin-bottom: 10px;
        }

        .header-title h3 {
            margin: 0;
            font-size: 12px;
            text-decoration: underline;
        }

        .location-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .location-table td {
            padding: 3px;
            vertical-align: top;
        }

        .logo-cell {
            width: 15%;
            text-align: center;
        }

        .logo {
            width: 120px;
            height: auto;
        }

        .locations-cell {
            width: 85%;
            line-height: 1.3;
        }

        .customer-info {
            margin: 10px 0;
        }

        .customer-info table {
            width: 100%;
            border-collapse: collapse;
        }

        .customer-info td {
            padding: 2px 0;
            font-size: 11px;
        }

        .services-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        .services-table th,
        .services-table td {
            border: 1px solid #000;
            padding: 4px;
            font-size: 10px;
            text-align: center;
        }

        .services-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .services-table .col-desc {
            text-align: left;
            width: 50%;
        }

        .services-table .col-val {
            width: 50%;
        }

        .total-row {
            font-weight: bold;
            text-align: right;
        }

        .notes {
            margin: 15px 0;
            font-size: 9px;
        }

        .notes strong {
            font-size: 10px;
        }

        .payment-info {
            margin: 15px 0;
            font-size: 9px;
        }

        .signature-section {
            text-align: center;
            font-size: 11px;
        }

        hr {
            border: none;
            border-top: 1px solid #000;
            margin: 10px 0;
        }

        img {
            display: block;
        }
    </style>
</head>

<body>
    {{-- ── Header Alamat ── --}}
    <div class="header">
        <div class="header-title">
            <h3>ALAMAT RADHIYAN PET AND CARE</h3>
        </div>

        <div class="header-content">
            <table class="location-table" width="100%">
                <tr>
                    <td class="logo-cell" width="25%" valign="top" align="center">
                        <div style="padding: 1px; font-size: 8px; font-weight: bold; margin-bottom: 5px;">
                            RADHIYAN PET AND CARE
                        </div>
                        <img src="{{ public_path() . '/asset/logo-rpc-full-min.png' }}" alt="Logo" style="width: 100px;">
                    </td>

                    <td class="locations-cell" width="75%">
                        <table width="100%">
                            <tr>
                                @php
                                    $total = count($locations);
                                    $part = ceil($total / 3);
                                    $firstThird  = array_slice($locations, 0, $part);
                                    $secondThird = array_slice($locations, $part, $part);
                                    $thirdThird  = array_slice($locations, $part * 2);
                                @endphp
                                <td width="30%" valign="top">
                                    @foreach($firstThird as $location)
                                    <div style="font-size: 6px; line-height: 1.4; margin-bottom: 2px;">
                                        <strong>{{ $location['name'] }}</strong> - {{ $location['description'] }} - {{ $location['phone'] }}
                                    </div>
                                    @endforeach
                                </td>
                                <td width="30%" valign="top">
                                    @foreach($secondThird as $location)
                                    <div style="font-size: 6px; line-height: 1.4; margin-bottom: 2px;">
                                        <strong>{{ $location['name'] }}</strong> - {{ $location['description'] }} - {{ $location['phone'] }}
                                    </div>
                                    @endforeach
                                </td>
                                <td width="40%" valign="top">
                                    @foreach($thirdThird as $location)
                                    <div style="font-size: 6px; line-height: 1.4; margin-bottom: 2px;">
                                        <strong>{{ $location['name'] }}</strong> - {{ $location['description'] }} - {{ $location['phone'] }}
                                    </div>
                                    @endforeach
                                    <div style="font-size: 7px; margin-top: 5px;">
                                        <strong>Call Center:</strong> 081312245500
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <hr>

    {{-- ── Judul Nota ── --}}
    <table width="100%" style="margin-bottom: 10px;">
        <tr>
            <td style="font-size: 10px; text-align: left; width: 30%;">
                {{ $nota_date }}
            </td>
            <td style="font-weight: bold; font-size: 14px; text-align: center; width: 40%;">
                TANDA TERIMA PEMBAYARAN AWAL / DP
            </td>
            <td style="font-size: 10px; text-align: right; width: 30%; white-space: nowrap;">
                No. Nota: {{ $nota_number ?? '-' }}<br>
                No. Reg: {{ $registration_no }}
            </td>
        </tr>
    </table>

    {{-- ── Info Customer & Pet ── --}}
    <div class="customer-info">
        <table>
            <tr>
                <td style="width: 150px;">Nama Pemilik</td>
                <td>: <span>{{ $customer_name }}</span></td>
            </tr>
            <tr>
                <td>No.Telp (WA)</td>
                <td>: <span>{{ $phone_number }}</span></td>
            </tr>
            <tr>
                <td>Nama Pet</td>
                <td>: <span>{{ $pet_name }}</span></td>
            </tr>
            <tr>
                <td>Tanggal Masuk</td>
                <td>: <span>{{ $start_date }}</span></td>
            </tr>
            <tr>
                <td>Estimasi Keluar</td>
                <td>: <span>{{ $end_date }}</span></td>
            </tr>
        </table>
    </div>

    {{-- ── Detail DP ── --}}
    <table class="services-table">
        <thead>
            <tr>
                <th class="col-desc">KETERANGAN</th>
                <th class="col-val">DETAIL</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align: left;">Jumlah Pembayaran Awal (DP)</td>
                <td style="font-weight: bold; font-size: 12px;">Rp {{ number_format($amount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="text-align: left;">Metode Pembayaran</td>
                <td>{{ $payment_method }}</td>
            </tr>
            <tr>
                <td style="text-align: left;">Dicatat Pada</td>
                <td>{{ $recorded_at }}</td>
            </tr>
            <tr>
                <td style="text-align: left;">Dicatat Oleh</td>
                <td>{{ $recorded_by }}</td>
            </tr>
            @if($catatan)
            <tr>
                <td style="text-align: left;">Catatan</td>
                <td style="text-align: left; font-style: italic;">{{ $catatan }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    <div class="notes">
        <strong>Catatan:</strong><br>
        -Simpan tanda terima ini sebagai bukti pembayaran awal.<br>
        -Pembayaran awal (DP) akan diperhitungkan saat proses pembayaran akhir / check-out.<br>
        -Khusus VIP Member diskon 10%
    </div>

    {{-- ── Info Pembayaran + Tanda Tangan ── --}}
    <table style="width: 100%; font-size: 12px; line-height: 1.4; border-collapse: collapse;">
        <tr>
            {{-- Kolom kiri: Info Rekening --}}
            <td style="width: 65%; vertical-align: top;">
                <div class="payment-info">
                    <strong>Pembayaran via transfer ke:</strong><br><br>

                    <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                        <tr>
                            <td style="width: 130px; vertical-align: top; font-weight: bold;">No. Rek BCA</td>
                            <td style="vertical-align: top; font-weight: bold;">599-096-0005</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;">a.n</td>
                            <td style="padding-bottom: 4px; font-weight: bold;">Radhiyan Fadiar Sahistya</td>
                        </tr>
                        <tr>
                            <td style="vertical-align: top;">Cabang</td>
                            <td style="padding-bottom: 12px;">
                                Kp. Gading, Pulogebang, Tanjung Duren, Buaran, Sukmajaya, Sawangan, Hankam Pondokgede, Lippo Cikarang, Kalangtenah Karawaci Ketintang Surabaya, Waru Sidoarjo, Kenten Palembang
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 130px; vertical-align: top; font-weight: bold;">No. Rek BCA</td>
                            <td style="vertical-align: top; font-weight: bold;">599-093-0009</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;">a.n</td>
                            <td style="padding-bottom: 4px; font-weight: bold;">Dharmawijaya Widyatama</td>
                        </tr>
                        <tr>
                            <td style="vertical-align: top;">Cabang</td>
                            <td style="padding-bottom: 12px;">Condet & Rawamangun</td>
                        </tr>
                    </table>

                    <div style="margin-top: 10px; font-weight: bold;">
                        Pembayaran akan dianggap sah setelah masuk ke rekening yang ada di atas
                    </div>
                </div>
            </td>

            {{-- Kolom kanan: Tanda tangan --}}
            <td style="width: 35%; vertical-align: top; padding-top: 10px;">
                <table width="100%" style="font-size: 11px; text-align: center;">
                    <tr>
                        <td style="padding-bottom: 50px;">Kasir,</td>
                        <td style="padding-bottom: 50px;">Pemilik / Owner,</td>
                    </tr>
                    <tr>
                        <td style="border-top: 1px solid #000; padding-top: 4px;">
                            <strong>( {{ $recorded_by }} )</strong>
                        </td>
                        <td style="border-top: 1px solid #000; padding-top: 4px;">
                            <strong>( {{ $customer_name }} )</strong>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

</body>
</html>
