<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Nota Petshop</title>
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

        .header-content {
            display: flex;
            align-items: center;
        }

        .alamat-section {
            flex: 1;
        }

        .alamat-info {
            font-size: 8px;
            line-height: 1.4;
            text-align: left;
        }

        .alamat-info p {
            margin: 2px 0;
            padding: 0;
        }

        /* Updated styles for location table */
        .location-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            font-size: 7px;
            /* Ukuran font lebih kecil */
        }

        .location-table td {
            border: 1px solid #000;
            padding: 3px;
            vertical-align: top;
        }

        .logo-cell {
            width: 15%;
            text-align: center;
        }

        .logo-text {
            border: 1px solid #000;
            padding: 2px;
            font-size: 8px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .logo {
            width: 70px;
            /* Logo lebih kecil */
            height: auto;
        }

        .locations-cell {
            width: 85%;
            line-height: 1.3;
        }

        .location-row {
            margin-bottom: 2px;
            font-size: 7px;
        }

        .locations-container {
            display: flex;
            gap: 8px;
        }

        .location-column {
            flex: 1;
            padding: 0 2px;
        }

        .nota-header {
            text-align: center;
            margin: 15px 0 10px 0;
            position: relative;
        }

        .nota-date {
            position: absolute;
            left: 0;
            font-size: 11px;
        }

        .nota-title {
            font-size: 14px;
            font-weight: bold;
            display: inline-block;
        }

        .nota-number {
            position: absolute;
            right: 0;
            font-size: 11px;
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

        .services-table .service-name {
            text-align: left;
            width: 30%;
        }

        .services-table .animal-type {
            width: 20%;
        }

        .services-table .quantity {
            width: 10%;
        }

        .services-table .price {
            width: 20%;
        }

        .services-table .total {
            width: 20%;
        }

        .groomer-info {
            margin: 15px 0;
            font-size: 11px;
        }

        .groomer-line {
            margin: 3px 0;
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
            margin-top: 20px;
            text-align: right;
            font-size: 11px;
        }

        .signature-box {
            margin-top: 30px;
            text-align: center;
        }

        .dotted-line {
            border-bottom: 1px dotted #000;
            display: inline-block;
            min-width: 200px;
        }

        .total-row {
            font-weight: bold;
            text-align: right;
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
    <div class="header">
        <div class="header-title">
            <h3>ALAMAT RADHIYAN PET AND CARE</h3>
        </div>

        <div class="header-content">
            <table class="location-table" width="100%">
                <tr>
                    <td class="logo-cell" width="25%" valign="top" align="center">
                        <div style="border: 1px solid #000; padding: 3px; font-size: 9px; font-weight: bold; margin-bottom: 5px;">
                            RADHIYAN PET AND CARE
                        </div>
                        <img src="{{ public_path('storage/Logo/Logo-Radhiyan.png') }}" alt="Logo" style="width: 80px;">
                    </td>

                    <td class="locations-cell" width="75%">
                        <table width="100%">
                            <tr>
                                <td width="50%" valign="top">
                                    @php
                                    $halfCount = ceil(count($locations) / 2);
                                    $firstHalf = array_slice($locations, 0, $halfCount);
                                    @endphp
                                    @foreach($firstHalf as $location)
                                    <div style="font-size: 9px; line-height: 1.4; margin-bottom: 2px;">
                                        <strong>{{ $location['name'] }}</strong> - {{ $location['description'] }} - {{ $location['phone'] }}
                                    </div>
                                    @endforeach
                                </td>
                                <td width="50%" valign="top">
                                    @php
                                    $secondHalf = array_slice($locations, $halfCount);
                                    @endphp
                                    @foreach($secondHalf as $location)
                                    <div style="font-size: 9px; line-height: 1.4; margin-bottom: 2px;">
                                        <strong>{{ $location['name'] }}</strong> - {{ $location['description'] }} - {{ $location['phone'] }}
                                    </div>
                                    @endforeach

                                    <div style="font-size: 9px; margin-top: 5px;">
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

    <div class="nota-header">
        <span class="nota-date">___/___/202___</span>
        <span class="nota-title">NOTA PETSHOP</span>
        <span class="nota-number">No.Nota: ___________</span>
    </div>

    <div class="customer-info">
        <table>
            <tr>
                <td style="width: 150px;">Nomor Kartu</td>
                <td>: <span class="dotted-line"></span></td>
            </tr>
            <tr>
                <td>Nama Pemilik</td>
                <td>: <span class="dotted-line"></span></td>
            </tr>
            <tr>
                <td>No.Telp (WA)</td>
                <td>: <span class="dotted-line"></span></td>
            </tr>
            <tr>
                <td>Jam Kedatangan</td>
                <td>: <span class="dotted-line"></span></td>
            </tr>
        </table>
    </div>

    <table class="services-table">
        <thead>
            <tr>
                <th class="service-name">JENIS PELAYANAN</th>
                <th class="animal-type">JENIS HEWAN</th>
                <th class="quantity">JML</th>
                <th class="price">HARGA</th>
                <th class="total">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="service-name">Jasa antar jemput/delivery</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td class="service-name">Grooming kering</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td class="service-name">Grooming sehat</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td class="service-name">Anti Jamur</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td class="service-name">Anti Kutu</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td class="service-name">Grooming Anti Kutu + Cabut Kutu</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td class="service-name">Grooming Lengkap (Kutu/Jamur)</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td class="service-name">Grooming Sekasoie/Antippe</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td class="service-name">Cukur Rambut/Bulu</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td class="service-name">Sikat Gigi</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td class="service-name">Penitipan tgl _____ s.d _____</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td class="service-name">Penitipan tgl _____ s.d _____</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td class="service-name">Penitipan tgl _____ s.d _____</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td colspan="4" class="total-row">TOTAL</td>
                <td></td>
            </tr>
            <tr>
                <td colspan="4" class="total-row">DEPOSIT</td>
                <td></td>
            </tr>
            <tr>
                <td colspan="4" class="total-row">TOTAL TAGIHAN</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="groomer-info">
        <div class="groomer-line">
            Nama Groomer : <span class="dotted-line" style="min-width: 250px;"></span> (_____ ekor)
        </div>
        <div class="groomer-line">
            Nama Groomer : <span class="dotted-line" style="min-width: 250px;"></span> (_____ ekor)
        </div>
    </div>

    <div class="notes">
        <strong>Catatan:</strong><br>
        -Kumpulkan nota bukti grooming & penitipan (*tunjukkan kepada pegawai kasir)<br>
        -Khusus VIP Member diskon 10%<br>
        -Penitipan minimal 10 hari, gratis grooming sehat 1x
    </div>

    <div class="payment-info">
        <strong>Pembayaran via transfer ke:</strong><br>
        No. Rek BCA <strong>599-096-0005</strong> a.n <strong>Radhiyan Fadiar Sahistya</strong><br>
        (Cabang Kp. Gading, Pulogebang, Tanjung Duren, Buaran, Sukmajaya, Sawangan, Hankam Pondokgede, Lippo Cikarang, Kalangtenah Karawaci Ketintang Surabaya, Waru Sidoarjo, Kenten Palembang)<br>
        No. Rek BCA <strong>599-093-0009</strong> a.n <strong>Dharmawijaya Widyatama</strong><br>
        (Cabang Condet & Rawamangun)<br>
        <strong>Pembayaran akan dianggap sah setelah masuk ke rekening yang ada diatas</strong>
    </div>

    <div class="signature-section">
        <div style="float: right; text-align: center;">
            Mengetahui,<br><br><br><br>
            ( _________________ )<br>
            <strong>Kasir</strong>
        </div>
        <div style="clear: both;"></div>
    </div>
</body>

</html>