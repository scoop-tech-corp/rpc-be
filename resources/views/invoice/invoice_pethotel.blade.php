<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Nota PetHotel</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.3;
            color: #000;
            padding: 15px;
            margin: 0;
        }

        .header { margin-bottom: 15px; }

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

        .nota-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
        }

        .customer-info { margin: 10px 0; }

        .customer-info table { width: 100%; border-collapse: collapse; }

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

        .services-table .item-name { text-align: left; }
        .services-table .quantity  { width: 10%; }
        .services-table .price     { width: 20%; }
        .services-table .total     { width: 20%; }

        .notes {
            margin: 15px 0;
            font-size: 9px;
        }

        .notes strong { font-size: 10px; }

        .payment-info {
            margin: 15px 0;
            font-size: 9px;
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

        img { display: block; }
    </style>
</head>

<body>
    {{-- ── Header ── --}}
    <div class="header">
        <div class="header-title">
            <h3>ALAMAT RADHIYAN PET AND CARE</h3>
        </div>

        <table class="location-table" width="100%">
            <tr>
                <td width="25%" valign="top" align="center">
                    <div style="font-size: 8px; font-weight: bold; margin-bottom: 5px;">RADHIYAN PET AND CARE</div>
                    <img src="{{ public_path() . '/asset/logo-rpc-full-min.webp' }}" alt="Logo" style="width: 120px;">
                </td>
                <td width="75%" valign="top">
                    @php
                        $total   = count($locations);
                        $part    = ceil($total / 3);
                        $col1    = array_slice($locations, 0, $part);
                        $col2    = array_slice($locations, $part, $part);
                        $col3    = array_slice($locations, $part * 2);
                    @endphp
                    <table width="100%">
                        <tr>
                            <td width="30%" valign="top">
                                @foreach($col1 as $loc)
                                <div style="font-size: 6px; line-height: 1.4; margin-bottom: 2px;">
                                    <strong>{{ $loc['name'] }}</strong> - {{ $loc['description'] }} - {{ $loc['phone'] }}
                                </div>
                                @endforeach
                            </td>
                            <td width="30%" valign="top">
                                @foreach($col2 as $loc)
                                <div style="font-size: 6px; line-height: 1.4; margin-bottom: 2px;">
                                    <strong>{{ $loc['name'] }}</strong> - {{ $loc['description'] }} - {{ $loc['phone'] }}
                                </div>
                                @endforeach
                            </td>
                            <td width="40%" valign="top">
                                @foreach($col3 as $loc)
                                <div style="font-size: 6px; line-height: 1.4; margin-bottom: 2px;">
                                    <strong>{{ $loc['name'] }}</strong> - {{ $loc['description'] }} - {{ $loc['phone'] }}
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

    <hr>

    {{-- ── Nota header ── --}}
    <table width="100%" style="margin-bottom: 10px;">
        <tr>
            <td style="font-size: 10px; text-align: left; width: 30%;">{{ $nota_date }}</td>
            <td style="font-weight: bold; font-size: 14px; text-align: center; width: 40%;">NOTA PET HOTEL</td>
            <td style="font-size: 10px; text-align: right; width: 30%; white-space: nowrap;">No.Nota: {{ $no_nota }}</td>
        </tr>
    </table>

    {{-- ── Info customer & hewan ── --}}
    <div class="customer-info">
        <table>
            <tr>
                <td style="width: 150px;">Nomor Kartu</td>
                <td>: {{ $member_no }}</td>
            </tr>
            <tr>
                <td>Nama Pemilik</td>
                <td>: {{ $customer_name }}</td>
            </tr>
            <tr>
                <td>No.Telp (WA)</td>
                <td>: {{ $phone_number }}</td>
            </tr>
            <tr>
                <td>Nama Hewan</td>
                <td>: {{ $pet_name }}</td>
            </tr>
            <tr>
                <td>Kandang</td>
                <td>: {{ $cage_name }}</td>
            </tr>
            <tr>
                <td>Check-In</td>
                <td>: {{ $checkin_date }}</td>
            </tr>
            <tr>
                <td>Check-Out</td>
                <td>: {{ $checkout_date }}</td>
            </tr>
            <tr>
                <td>Lama Menginap</td>
                <td>: {{ $days_stayed }} hari</td>
            </tr>
        </table>
    </div>

    {{-- ── Tabel item ── --}}
    <table class="services-table">
        <thead>
            <tr>
                <th class="item-name" style="width: 40%; text-align: left;">ITEM</th>
                <th class="quantity">QTY</th>
                <th class="price">HARGA SATUAN</th>
                <th class="total">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            {{-- Masa menginap --}}
            <tr>
                <td class="item-name" colspan="4" style="background:#f9f9f9; font-weight:bold; font-size:9px;">
                    MASA MENGINAP
                </td>
            </tr>
            <tr>
                <td class="item-name">{{ $stay_service_name }} ({{ $days_stayed }} hari)</td>
                <td>{{ $days_stayed }}</td>
                <td>{{ number_format($price_per_day, 0, ',', '.') }}</td>
                <td>{{ number_format($subtotal_stay, 0, ',', '.') }}</td>
            </tr>

            {{-- Treatment awal --}}
            @if(count($services) > 0 || count($products) > 0)
            <tr>
                <td class="item-name" colspan="4" style="background:#f9f9f9; font-weight:bold; font-size:9px;">
                    TREATMENT AWAL
                </td>
            </tr>
            @foreach($services as $item)
            <tr>
                <td class="item-name">{{ $item['name'] }}</td>
                <td>{{ $item['quantity'] }}</td>
                <td>{{ number_format($item['price'], 0, ',', '.') }}</td>
                <td>{{ number_format($item['total'], 0, ',', '.') }}</td>
            </tr>
            @endforeach
            @foreach($products as $item)
            <tr>
                <td class="item-name">{{ $item['name'] }}</td>
                <td>{{ $item['quantity'] }}</td>
                <td>{{ number_format($item['price'], 0, ',', '.') }}</td>
                <td>{{ number_format($item['total'], 0, ',', '.') }}</td>
            </tr>
            @endforeach
            @endif

            {{-- Treatment tambahan --}}
            @if(count($additional) > 0)
            <tr>
                <td class="item-name" colspan="4" style="background:#f9f9f9; font-weight:bold; font-size:9px;">
                    ITEM TAMBAHAN
                </td>
            </tr>
            @foreach($additional as $item)
            <tr>
                <td class="item-name">{{ $item['name'] }}{{ $item['catatan'] ? ' - ' . $item['catatan'] : '' }}</td>
                <td>{{ $item['quantity'] }}</td>
                <td>{{ number_format($item['price'], 0, ',', '.') }}</td>
                <td>{{ number_format($item['total'], 0, ',', '.') }}</td>
            </tr>
            @endforeach
            @endif

            {{-- Summary bawah --}}
            <tr>
                <td colspan="3" class="total-row">SUBTOTAL</td>
                <td>{{ number_format($subtotal_before_discount, 0, ',', '.') }}</td>
            </tr>
            @if($total_prepaid > 0)
            <tr>
                <td colspan="3" class="total-row">DP / PEMBAYARAN AWAL</td>
                <td>- {{ number_format($total_prepaid, 0, ',', '.') }}</td>
            </tr>
            @endif
            @if($total_discount > 0)
            <tr>
                <td colspan="3" class="total-row">DISKON PROMO</td>
                <td>- {{ number_format($total_discount, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr>
                <td colspan="3" class="total-row" style="font-size:12px;">TOTAL TAGIHAN</td>
                <td style="font-weight:bold; font-size:12px;">{{ number_format($grand_total, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="3" class="total-row">DIBAYAR</td>
                <td>{{ number_format($amount_paid, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="3" class="total-row">METODE PEMBAYARAN</td>
                <td>{{ $payment_method }}</td>
            </tr>
        </tbody>
    </table>

    <div class="notes">
        <strong>Catatan:</strong><br>
        -Khusus VIP Member diskon 10%<br>
        @if($note)
        <em>{{ $note }}</em>
        @endif
    </div>

    {{-- ── Info bank transfer + tanda tangan ── --}}
    <table style="width: 100%; font-size: 11px; line-height: 1.4; border-collapse: collapse; margin-top: 10px;">
        <tr>
            <td style="width: 65%; vertical-align: top;">
                <strong>Pembayaran via transfer ke:</strong><br><br>
                <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                    <tr>
                        <td style="width: 130px; font-weight: bold;">No. Rek BCA</td>
                        <td style="font-weight: bold;">599-096-0005</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">a.n</td>
                        <td style="padding-bottom: 4px; font-weight: bold;">Radhiyan Fadiar Sahistya</td>
                    </tr>
                    <tr>
                        <td>Cabang</td>
                        <td style="padding-bottom: 12px; font-size: 9px;">
                            Kp. Gading, Pulogebang, Tanjung Duren, Buaran, Sukmajaya, Sawangan,
                            Hankam Pondokgede, Lippo Cikarang, Kalangtenah Karawaci, Ketintang Surabaya, Waru Sidoarjo, Kenten Palembang
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">No. Rek BCA</td>
                        <td style="font-weight: bold;">599-093-0009</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">a.n</td>
                        <td style="padding-bottom: 4px; font-weight: bold;">Dharmawijaya Widyatama</td>
                    </tr>
                    <tr>
                        <td>Cabang</td>
                        <td style="padding-bottom: 12px;">Condet & Rawamangun</td>
                    </tr>
                </table>
                <div style="margin-top: 8px; font-weight: bold; font-size: 10px;">
                    Pembayaran akan dianggap sah setelah masuk ke rekening yang ada di atas
                </div>
            </td>

            <td style="width: 35%; vertical-align: top; padding-top: 20px; text-align: center;">
                Mengetahui,<br><br><br><br>
                ( _________________ )<br>
                <strong>Kasir</strong>
            </td>
        </tr>
    </table>

</body>
</html>
