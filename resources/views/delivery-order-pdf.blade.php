<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Delivery Order - {{ $order->deliveryNumber }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #000;
            padding: 20px;
            margin: 0;
        }

        .header {
            text-align: center;
            margin-bottom: 16px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .header h2 {
            margin: 0 0 4px 0;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .header p {
            margin: 0;
            font-size: 10px;
            color: #444;
        }

        .info-section {
            margin-bottom: 14px;
        }

        .info-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .info-grid td {
            padding: 2px 6px 2px 0;
            vertical-align: top;
            font-size: 10px;
        }

        .info-grid td.label {
            width: 140px;
            font-weight: bold;
            color: #333;
        }

        .info-grid td.colon {
            width: 10px;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }

        .status-draft        { background: #e2e8f0; color: #333; }
        .status-assigned     { background: #fef3c7; color: #92400e; }
        .status-picked_up    { background: #dbeafe; color: #1e40af; }
        .status-on_delivery  { background: #ede9fe; color: #5b21b6; }
        .status-delivered    { background: #d1fae5; color: #065f46; }
        .status-failed       { background: #fee2e2; color: #991b1b; }
        .status-cancelled    { background: #f3f4f6; color: #6b7280; }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            margin: 14px 0 6px 0;
            padding: 4px 8px;
            background-color: #1e3a5f;
            color: #fff;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        table.data-table th {
            background-color: #1e3a5f;
            color: #fff;
            padding: 5px 6px;
            text-align: left;
            font-size: 9px;
        }

        table.data-table th.center,
        table.data-table td.center {
            text-align: center;
        }

        table.data-table th.right,
        table.data-table td.right {
            text-align: right;
        }

        table.data-table td {
            padding: 4px 6px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        table.data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .summary-box {
            margin-top: 10px;
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
            font-size: 10px;
            background: #f8fafc;
        }

        .summary-box table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-box td {
            padding: 3px 6px;
        }

        .summary-box td.val {
            font-weight: bold;
            text-align: right;
            width: 100px;
        }

        .logs-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        .logs-table th {
            background-color: #374151;
            color: #fff;
            padding: 4px 6px;
            text-align: left;
        }

        .logs-table td {
            padding: 3px 6px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .divider {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: 12px 0;
        }

        .footer {
            margin-top: 30px;
            font-size: 9px;
            color: #6b7280;
            text-align: right;
            border-top: 1px solid #e5e7eb;
            padding-top: 6px;
        }
    </style>
</head>
<body>

    {{-- HEADER --}}
    <div class="header">
        <h2>Delivery Order</h2>
        <p>{{ $order->deliveryNumber }}</p>
    </div>

    {{-- INFO UTAMA --}}
    <div class="info-section">
        <table class="info-grid">
            <tr>
                <td class="label">No. Delivery Order</td>
                <td class="colon">:</td>
                <td><strong>{{ $order->deliveryNumber }}</strong></td>
                <td class="label">Lokasi</td>
                <td class="colon">:</td>
                <td>{{ $order->location->locationName ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Status</td>
                <td class="colon">:</td>
                <td>
                    @php
                        $statusMap = [
                            'draft'       => ['label' => 'Draft',       'class' => 'status-draft'],
                            'assigned'    => ['label' => 'Assigned',    'class' => 'status-assigned'],
                            'picked_up'   => ['label' => 'Picked Up',   'class' => 'status-picked_up'],
                            'on_delivery' => ['label' => 'On Delivery', 'class' => 'status-on_delivery'],
                            'delivered'   => ['label' => 'Delivered',   'class' => 'status-delivered'],
                            'failed'      => ['label' => 'Failed',      'class' => 'status-failed'],
                            'cancelled'   => ['label' => 'Cancelled',   'class' => 'status-cancelled'],
                        ];
                        $s = $statusMap[$order->status] ?? ['label' => ucfirst($order->status), 'class' => 'status-draft'];
                    @endphp
                    <span class="status-badge {{ $s['class'] }}">{{ $s['label'] }}</span>
                </td>
                <td class="label">Dibuat Oleh</td>
                <td class="colon">:</td>
                <td>{{ $order->creator->firstName ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Tanggal Pengiriman</td>
                <td class="colon">:</td>
                <td>{{ $order->deliveryDate ? \Carbon\Carbon::parse($order->deliveryDate)->format('d/m/Y') : '-' }}</td>
                <td class="label">Waktu Pengiriman</td>
                <td class="colon">:</td>
                <td>{{ $order->deliveryTime ?? '-' }}</td>
            </tr>
            @if($order->scheduledAt)
            <tr>
                <td class="label">Dijadwalkan</td>
                <td class="colon">:</td>
                <td>{{ \Carbon\Carbon::parse($order->scheduledAt)->format('d/m/Y H:i') }}</td>
                <td></td><td></td><td></td>
            </tr>
            @endif
            @if($order->pickedUpAt)
            <tr>
                <td class="label">Diambil Pada</td>
                <td class="colon">:</td>
                <td>{{ \Carbon\Carbon::parse($order->pickedUpAt)->format('d/m/Y H:i') }}</td>
                <td></td><td></td><td></td>
            </tr>
            @endif
            @if($order->deliveredAt)
            <tr>
                <td class="label">Dikirim Pada</td>
                <td class="colon">:</td>
                <td>{{ \Carbon\Carbon::parse($order->deliveredAt)->format('d/m/Y H:i') }}</td>
                <td></td><td></td><td></td>
            </tr>
            @endif
            <tr><td colspan="6"><hr class="divider"></td></tr>
            <tr>
                <td class="label">Nama Customer</td>
                <td class="colon">:</td>
                <td>{{ $order->customerName ?? '-' }}</td>
                <td class="label">No. Telepon</td>
                <td class="colon">:</td>
                <td>{{ $order->customerPhone ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Alamat Pengiriman</td>
                <td class="colon">:</td>
                <td colspan="4">{{ $order->deliveryAddress ?? '-' }}</td>
            </tr>
            @if($order->note)
            <tr>
                <td class="label">Catatan</td>
                <td class="colon">:</td>
                <td colspan="4">{{ $order->note }}</td>
            </tr>
            @endif
        </table>
    </div>

    {{-- INFO AGEN --}}
    @if($order->agent)
    <div class="section-title">Informasi Agen Pengiriman</div>
    <div class="info-section">
        <table class="info-grid">
            <tr>
                <td class="label">Nama Agen</td>
                <td class="colon">:</td>
                <td>{{ $order->agent->name }}</td>
                <td class="label">No. Telepon</td>
                <td class="colon">:</td>
                <td>{{ $order->agent->phone ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Jenis Kendaraan</td>
                <td class="colon">:</td>
                <td>{{ $order->agent->vehicleType ?? '-' }}</td>
                <td class="label">Plat Nomor</td>
                <td class="colon">:</td>
                <td>{{ $order->agent->vehiclePlate ?? '-' }}</td>
            </tr>
        </table>
    </div>
    @endif

    {{-- DETAIL PRODUK --}}
    <div class="section-title">Detail Produk</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:30px" class="center">No</th>
                <th>SKU</th>
                <th>Nama Produk</th>
                <th class="center">Qty</th>
                <th class="right">Harga Satuan</th>
                <th class="right">Subtotal</th>
                <th class="center">Berat (kg)</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($order->details as $i => $detail)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td>{{ $detail->sku ?? '-' }}</td>
                <td>{{ $detail->productName }}</td>
                <td class="center">{{ $detail->qty }}</td>
                <td class="right">{{ number_format($detail->unitPrice, 0, ',', '.') }}</td>
                <td class="right">{{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                <td class="center">{{ $detail->weight ?? '-' }}</td>
                <td>{{ $detail->note ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="center" style="padding: 12px; color: #6b7280;">Tidak ada detail produk.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- SUMMARY --}}
    <div class="summary-box">
        <table>
            <tr>
                <td>Total Item</td>
                <td class="val">{{ $order->totalItems }}</td>
                <td style="width:30px"></td>
                <td>Total Berat</td>
                <td class="val">{{ $order->totalWeight }} kg</td>
                <td style="width:30px"></td>
                <td>Total Nilai</td>
                <td class="val">Rp {{ number_format($order->totalAmount, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    {{-- KETERANGAN GAGAL / BATAL --}}
    @if($order->failedReason)
    <div class="section-title" style="background:#991b1b">Alasan Gagal</div>
    <p style="font-size:10px; margin: 4px 0 12px 0;">{{ $order->failedReason }}</p>
    @endif

    @if($order->cancelledReason)
    <div class="section-title" style="background:#6b7280">Alasan Pembatalan</div>
    <p style="font-size:10px; margin: 4px 0 12px 0;">{{ $order->cancelledReason }}</p>
    @endif

    {{-- LOG AKTIVITAS --}}
    @if($order->logs->count() > 0)
    <div class="section-title" style="margin-top:16px">Riwayat Aktivitas</div>
    <table class="logs-table">
        <thead>
            <tr>
                <th style="width:30px" class="center">No</th>
                <th style="width:120px">Aksi</th>
                <th>Keterangan</th>
                <th style="width:100px">Oleh</th>
                <th style="width:120px">Waktu</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->logs as $i => $log)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $log->action)) }}</td>
                <td>{{ $log->description }}</td>
                <td>{{ $log->user->firstName ?? '-' }}</td>
                <td>{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer">
        Dicetak pada: {{ now()->format('d/m/Y H:i:s') }}
    </div>

</body>
</html>
