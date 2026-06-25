<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Quotation {{ $quotation->quotationNo }}</title>
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

        .services-table .item-name {
            text-align: left;
        }

        .total-row {
            font-weight: bold;
            text-align: right;
        }

        .notes {
            margin: 10px 0;
            font-size: 9px;
        }

        .notes strong {
            font-size: 10px;
        }

        .validity-box {
            margin: 10px 0;
            padding: 6px 10px;
            border: 1px dashed #999;
            border-radius: 4px;
            font-size: 9px;
            color: #555;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-draft     { background: #f3f4f6; color: #374151; }
        .status-sent      { background: #dbeafe; color: #1e40af; }
        .status-accepted  { background: #dcfce7; color: #166534; }
        .status-rejected  { background: #fee2e2; color: #991b1b; }
        .status-expired   { background: #fef9c3; color: #713f12; }
        .status-converted { background: #ede9fe; color: #5b21b6; }

        .badge-service { background: #dbeafe; color: #1e40af; border-radius: 3px; padding: 1px 5px; font-size: 9px; }
        .badge-product { background: #dcfce7; color: #166534; border-radius: 3px; padding: 1px 5px; font-size: 9px; }

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

    {{-- ══ HEADER — sama persis dengan pet clinic ══ --}}
    <div class="header">
        <div class="header-title">
            <h3>ALAMAT RADHIYAN PET AND CARE</h3>
        </div>

        <table class="location-table" width="100%">
            <tr>
                <td class="logo-cell" width="25%" valign="top" align="center">
                    <div style="padding: 1px; font-size: 8px; font-weight: bold; margin-bottom: 5px;">
                        RADHIYAN PET AND CARE
                    </div>
                    <img src="{{ public_path() . '/asset/logo-rpc-full-min.webp' }}" alt="Logo" style="width: 120px;">
                </td>

                <td class="locations-cell" width="75%">
                    <table width="100%">
                        <tr>
                            @php
                                $total      = count($locations);
                                $part       = ceil($total / 3);
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

    <hr>

    {{-- ══ JUDUL ══ --}}
    <table width="100%" style="margin-bottom: 10px;">
        <tr>
            <td style="font-size: 10px; text-align: left; width: 30%;">
                {{ $nota_date }}
            </td>
            <td style="font-weight: bold; font-size: 14px; text-align: center; width: 40%;">
                PENAWARAN HARGA
            </td>
            <td style="font-size: 10px; text-align: right; width: 30%; white-space: nowrap;">
                No.: {{ $quotation->quotationNo }}
            </td>
        </tr>
    </table>

    {{-- ══ INFO CUSTOMER ══ --}}
    <div class="customer-info">
        <table>
            <tr>
                <td style="width: 150px;">Nomor Kartu</td>
                <td>: <span>{{ $quotation->memberNo ?? '-' }}</span></td>
            </tr>
            <tr>
                <td>Nama Pemilik</td>
                <td>: <span>{{ $quotation->customerName }}</span></td>
            </tr>
            <tr>
                <td>No.Telp (WA)</td>
                <td>: <span>{{ $quotation->customerPhone }}</span></td>
            </tr>
            @if($quotation->petName)
            <tr>
                <td>Hewan Peliharaan</td>
                <td>: <span>{{ $quotation->petName }}</span></td>
            </tr>
            @endif
            <tr>
                <td>Jenis Layanan</td>
                <td>: <span>{{ $serviceLabel }}</span></td>
            </tr>
            <tr>
                <td>Cabang</td>
                <td>: <span>{{ $quotation->locationName }}</span></td>
            </tr>
            <tr>
                <td>Status</td>
                <td>: <span class="status-badge status-{{ $quotation->status }}">{{ ucfirst($quotation->status) }}</span></td>
            </tr>
            <tr>
                <td>Berlaku Hingga</td>
                <td>: <span style="font-weight: bold; color: #dc2626;">{{ $valid_until }}</span></td>
            </tr>
        </table>
    </div>

    {{-- ══ TABEL ITEM ══ --}}
    <table class="services-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 8%;">Tipe</th>
                <th style="text-align: left;">Nama Item</th>
                <th style="width: 8%;">Qty</th>
                <th style="width: 20%;">Harga Satuan</th>
                <th style="width: 20%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>
                    @if($item->itemType === 'service')
                        <span class="badge-service">Layanan</span>
                    @else
                        <span class="badge-product">Produk</span>
                    @endif
                </td>
                <td class="item-name">
                    {{ $item->itemName }}
                    @if($item->notes)
                        <div style="font-size: 9px; color: #888; margin-top: 1px;">{{ $item->notes }}</div>
                    @endif
                </td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->unitPrice, 0, ',', '.') }}</td>
                <td>{{ number_format($item->totalPrice, 0, ',', '.') }}</td>
            </tr>
            @endforeach

            <tr>
                <td colspan="5" class="total-row">SUBTOTAL</td>
                <td>{{ number_format($quotation->subtotalAmount, 0, ',', '.') }}</td>
            </tr>
            @if($quotation->discountAmount > 0)
            <tr>
                <td colspan="5" class="total-row">DISKON</td>
                <td style="color: #dc2626;">- {{ number_format($quotation->discountAmount, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr>
                <td colspan="5" class="total-row">TOTAL</td>
                <td style="font-weight: bold;">{{ number_format($quotation->finalAmount, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    {{-- ══ CATATAN ══ --}}
    @if($quotation->notes)
    <div class="notes">
        <strong>Catatan:</strong><br>
        {{ $quotation->notes }}
    </div>
    @endif

    {{-- ══ VALIDITAS ══ --}}
    <div class="validity-box">
        ⚠ Penawaran ini berlaku hingga <strong>{{ $valid_until }}</strong>.
        Setelah tanggal tersebut, harga dapat berubah sewaktu-waktu.
    </div>

    {{-- ══ TANDA TANGAN — sama seperti pet clinic ══ --}}
    <table style="width: 100%; font-size: 12px; line-height: 1.4; border-collapse: collapse;">
        <tr>
            <td style="width: 65%; vertical-align: top;">
                <div style="font-size: 9px; margin-top: 10px;">
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
                            <td style="width: 130px; vertical-align: top; font-weight: bold;">No. Rek BCA</td>
                            <td style="vertical-align: top; font-weight: bold;">599-093-0009</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;">a.n</td>
                            <td style="font-weight: bold;">Dharmawijaya Widyatama</td>
                        </tr>
                    </table>
                    <div style="margin-top: 8px; font-weight: bold;">
                        Pembayaran akan dianggap sah setelah masuk ke rekening yang ada di atas
                    </div>
                </div>
            </td>

            <td style="width: 35%; vertical-align: top; padding-top: 20px;">
                <div style="width: 100%; text-align: center; font-size: 11px;">
                    Hormat kami,<br><br><br><br>
                    ( _________________ )<br>
                    <strong>Radhiyan Pet &amp; Care</strong>
                </div>
            </td>
        </tr>
    </table>

</body>

</html>
