<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Loan Produk - {{ $loan->loanNumber }}</title>
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

        .status-draft      { background: #e2e8f0; color: #333; }
        .status-pending    { background: #fef3c7; color: #92400e; }
        .status-approved   { background: #dbeafe; color: #1e40af; }
        .status-active     { background: #ede9fe; color: #5b21b6; }
        .status-returned   { background: #d1fae5; color: #065f46; }
        .status-cancelled  { background: #fee2e2; color: #991b1b; }

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

        .return-pending  { color: #92400e; font-weight: bold; }
        .return-returned { color: #065f46; font-weight: bold; }

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
            margin: 8px 0;
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
        <h2>Loan Produk</h2>
        <p>{{ $loan->loanNumber }}</p>
    </div>

    {{-- INFO UTAMA --}}
    <div class="info-section">
        <table class="info-grid">
            <tr>
                <td class="label">No. Loan Produk</td>
                <td class="colon">:</td>
                <td><strong>{{ $loan->loanNumber }}</strong></td>
                <td class="label">Lokasi</td>
                <td class="colon">:</td>
                <td>{{ $loan->location->locationName ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Status</td>
                <td class="colon">:</td>
                <td>
                    @php
                        $statusMap = [
                            'draft'     => ['label' => 'Draft',      'class' => 'status-draft'],
                            'pending'   => ['label' => 'Pending',    'class' => 'status-pending'],
                            'approved'  => ['label' => 'Disetujui',  'class' => 'status-approved'],
                            'active'    => ['label' => 'Aktif',      'class' => 'status-active'],
                            'returned'  => ['label' => 'Dikembalikan','class' => 'status-returned'],
                            'cancelled' => ['label' => 'Dibatalkan', 'class' => 'status-cancelled'],
                        ];
                        $s = $statusMap[$loan->status] ?? ['label' => ucfirst($loan->status), 'class' => 'status-draft'];
                    @endphp
                    <span class="status-badge {{ $s['class'] }}">{{ $s['label'] }}</span>
                </td>
                <td class="label">Staff</td>
                <td class="colon">:</td>
                <td>{{ $loan->staff->firstName ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Nama Event</td>
                <td class="colon">:</td>
                <td colspan="4">{{ $loan->eventName }}</td>
            </tr>
            <tr>
                <td class="label">Tanggal Event</td>
                <td class="colon">:</td>
                <td>{{ $loan->eventDate ? \Carbon\Carbon::parse($loan->eventDate)->format('d/m/Y') : '-' }}</td>
                <td class="label">Alamat Event</td>
                <td class="colon">:</td>
                <td>{{ $loan->eventAddress ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Batas Pengembalian</td>
                <td class="colon">:</td>
                <td>{{ $loan->returnDeadline ? \Carbon\Carbon::parse($loan->returnDeadline)->format('d/m/Y') : '-' }}</td>
                <td class="label">Tanggal Pinjam</td>
                <td class="colon">:</td>
                <td>{{ $loan->loanDate ? \Carbon\Carbon::parse($loan->loanDate)->format('d/m/Y') : '-' }}</td>
            </tr>
            @if($loan->returnDate)
            <tr>
                <td class="label">Tanggal Kembali</td>
                <td class="colon">:</td>
                <td>{{ \Carbon\Carbon::parse($loan->returnDate)->format('d/m/Y') }}</td>
                <td></td><td></td><td></td>
            </tr>
            @endif
            @if($loan->approver)
            <tr><td colspan="6"><hr class="divider"></td></tr>
            <tr>
                <td class="label">Disetujui Oleh</td>
                <td class="colon">:</td>
                <td>{{ $loan->approver->firstName }}</td>
                <td class="label">Tanggal Persetujuan</td>
                <td class="colon">:</td>
                <td>{{ $loan->approvedAt ? \Carbon\Carbon::parse($loan->approvedAt)->format('d/m/Y H:i') : '-' }}</td>
            </tr>
            @endif
            @if($loan->rejectedReason)
            <tr>
                <td class="label">Alasan Penolakan</td>
                <td class="colon">:</td>
                <td colspan="4" style="color:#991b1b">{{ $loan->rejectedReason }}</td>
            </tr>
            @endif
            @if($loan->note)
            <tr><td colspan="6"><hr class="divider"></td></tr>
            <tr>
                <td class="label">Catatan</td>
                <td class="colon">:</td>
                <td colspan="4">{{ $loan->note }}</td>
            </tr>
            @endif
        </table>
    </div>

    {{-- DETAIL PRODUK --}}
    <div class="section-title">Detail Produk</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:30px" class="center">No</th>
                <th>SKU</th>
                <th>Nama Produk</th>
                <th class="center">Qty Pinjam</th>
                <th class="right">Harga Modal</th>
                <th class="right">Harga Jual</th>
                <th class="center">Qty Terjual</th>
                <th class="center">Qty Kembali</th>
                <th class="right">Pendapatan</th>
                <th class="center">Status</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($loan->details as $i => $detail)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td>{{ $detail->sku ?? '-' }}</td>
                <td>{{ $detail->productName }}</td>
                <td class="center">{{ $detail->loanedQty }}</td>
                <td class="right">{{ number_format($detail->costPrice, 0, ',', '.') }}</td>
                <td class="right">{{ number_format($detail->suggestedPrice, 0, ',', '.') }}</td>
                <td class="center">{{ $detail->soldQty ?? '-' }}</td>
                <td class="center">{{ $detail->returnedQty ?? '-' }}</td>
                <td class="right">{{ $detail->revenue ? number_format($detail->revenue, 0, ',', '.') : '-' }}</td>
                <td class="center">
                    @if($detail->returnStatus === 'returned')
                        <span class="return-returned">Kembali</span>
                    @else
                        <span class="return-pending">Pending</span>
                    @endif
                </td>
                <td>{{ $detail->itemNote ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="11" class="center" style="padding:12px; color:#6b7280">Tidak ada detail produk.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- SUMMARY --}}
    <div class="summary-box">
        <table>
            <tr>
                <td>Total Item</td>
                <td class="val">{{ $loan->totalItems }}</td>
                <td style="width:20px"></td>
                <td>Total Qty Pinjam</td>
                <td class="val">{{ $loan->totalLoanedQty }}</td>
                @if($loan->totalSoldQty !== null)
                <td style="width:20px"></td>
                <td>Total Terjual</td>
                <td class="val">{{ $loan->totalSoldQty }}</td>
                <td style="width:20px"></td>
                <td>Total Kembali</td>
                <td class="val">{{ $loan->totalReturnedQty }}</td>
                <td style="width:20px"></td>
                <td>Total Pendapatan</td>
                <td class="val">Rp {{ number_format($loan->totalRevenue, 0, ',', '.') }}</td>
                @endif
            </tr>
        </table>
    </div>

    @if($loan->returnNote)
    <div class="section-title" style="margin-top:14px">Catatan Pengembalian</div>
    <p style="font-size:10px; margin: 4px 0 12px 0;">{{ $loan->returnNote }}</p>
    @endif

    {{-- LOG AKTIVITAS --}}
    @if($loan->logs->count() > 0)
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
            @foreach($loan->logs as $i => $log)
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
