<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Stock Opname - {{ $stockOpname->stockOpnameNumber }}</title>
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
            width: 130px;
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

        .status-1 { background: #e2e8f0; color: #333; }
        .status-2 { background: #fef3c7; color: #92400e; }
        .status-3 { background: #dbeafe; color: #1e40af; }
        .status-4 { background: #d1fae5; color: #065f46; }
        .status-5 { background: #d1fae5; color: #065f46; }
        .status-6 { background: #fee2e2; color: #991b1b; }

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

        table.data-table td {
            padding: 4px 6px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        table.data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .status-match   { color: #065f46; font-weight: bold; }
        .status-more    { color: #1e40af; font-weight: bold; }
        .status-less    { color: #991b1b; font-weight: bold; }

        .diff-positive  { color: #065f46; }
        .diff-negative  { color: #991b1b; }

        .summary-box {
            margin-top: 14px;
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
            font-size: 10px;
        }

        .summary-box table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-box td {
            padding: 2px 6px;
        }

        .summary-box td.val {
            font-weight: bold;
            text-align: right;
            width: 60px;
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
        }

        .footer {
            margin-top: 30px;
            font-size: 9px;
            color: #6b7280;
            text-align: right;
            border-top: 1px solid #e5e7eb;
            padding-top: 6px;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>

    {{-- HEADER --}}
    <div class="header">
        <h2>Laporan Stock Opname</h2>
        <p>{{ $stockOpname->stockOpnameNumber }}</p>
    </div>

    {{-- INFO MASTER --}}
    <div class="info-section">
        <table class="info-grid">
            <tr>
                <td class="label">Nomor SO</td>
                <td class="colon">:</td>
                <td>{{ $stockOpname->stockOpnameNumber }}</td>
                <td class="label">Lokasi</td>
                <td class="colon">:</td>
                <td>{{ $stockOpname->locationName }}</td>
            </tr>
            <tr>
                <td class="label">Judul</td>
                <td class="colon">:</td>
                <td>{{ $stockOpname->title }}</td>
                <td class="label">Dibuat Oleh</td>
                <td class="colon">:</td>
                <td>{{ $stockOpname->createdBy }}</td>
            </tr>
            <tr>
                <td class="label">Waktu Mulai</td>
                <td class="colon">:</td>
                <td>{{ $stockOpname->startTime }}</td>
                <td class="label">Tanggal</td>
                <td class="colon">:</td>
                <td>{{ $stockOpname->createdAt }}</td>
            </tr>
            <tr>
                <td class="label">Status</td>
                <td class="colon">:</td>
                <td>
                    @php
                        $statusMap = [
                            1 => ['label' => 'Draft',              'class' => 'status-1'],
                            2 => ['label' => 'Process Input Data', 'class' => 'status-2'],
                            3 => ['label' => 'Approval Pending',   'class' => 'status-3'],
                            4 => ['label' => 'Checker Approved',   'class' => 'status-4'],
                            5 => ['label' => 'Director Approved',  'class' => 'status-5'],
                            6 => ['label' => 'Rejected',           'class' => 'status-6'],
                        ];
                        $s = $statusMap[$stockOpname->statusId] ?? ['label' => 'Unknown', 'class' => 'status-1'];
                    @endphp
                    <span class="status-badge {{ $s['class'] }}">{{ $s['label'] }}</span>
                </td>
                <td></td><td></td><td></td>
            </tr>
        </table>
    </div>

    {{-- PETUGAS --}}
    @if(count($users) > 0)
    <div class="section-title">Petugas Stock Opname</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:40px" class="center">No</th>
                <th>Nama Petugas</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $i => $user)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td>{{ $user->name }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- PRODUK --}}
    <div class="section-title">Detail Produk</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:30px" class="center">No</th>
                <th>SKU</th>
                <th>Nama Produk</th>
                <th class="center">Stok Sistem</th>
                <th class="center">Stok Fisik</th>
                <th class="center">Selisih</th>
                <th class="center">Status</th>
                <th>Catatan</th>
                <th>Diinput Oleh</th>
                <th>Waktu Input</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $i => $product)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td>{{ $product->sku }}</td>
                <td>{{ $product->fullName }}</td>
                <td class="center">{{ $product->stockSystem }}</td>
                <td class="center">{{ $product->stockPhysical }}</td>
                <td class="center {{ $product->difference > 0 ? 'diff-positive' : ($product->difference < 0 ? 'diff-negative' : '') }}">
                    {{ $product->difference > 0 ? '+' : '' }}{{ $product->difference }}
                </td>
                <td class="center">
                    @if($product->status == 1)
                        <span class="status-match">Match</span>
                    @elseif($product->status == 2)
                        <span class="status-more">Lebih</span>
                    @else
                        <span class="status-less">Kurang</span>
                    @endif
                </td>
                <td>{{ $product->note ?? '-' }}</td>
                <td>{{ $product->inputedBy }}</td>
                <td>{{ $product->inputedAt }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="center" style="padding: 12px; color: #6b7280;">Belum ada data produk.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- SUMMARY --}}
    @if(count($products) > 0)
    @php
        $totalMatch  = collect($products)->where('status', 1)->count();
        $totalMore   = collect($products)->where('status', 2)->count();
        $totalLess   = collect($products)->where('status', 3)->count();
    @endphp
    <div class="summary-box">
        <table>
            <tr>
                <td>Total Produk</td>
                <td class="val">{{ count($products) }}</td>
                <td style="width:20px"></td>
                <td>Match</td>
                <td class="val status-match">{{ $totalMatch }}</td>
                <td style="width:20px"></td>
                <td>Lebih</td>
                <td class="val status-more">{{ $totalMore }}</td>
                <td style="width:20px"></td>
                <td>Kurang</td>
                <td class="val status-less">{{ $totalLess }}</td>
            </tr>
        </table>
    </div>
    @endif

    {{-- LOG AKTIVITAS --}}
    @if(count($logs) > 0)
    <div class="section-title" style="margin-top:16px">Riwayat Aktivitas</div>
    <table class="logs-table">
        <thead>
            <tr>
                <th style="width:30px" class="center">No</th>
                <th style="width:160px">Event</th>
                <th>Keterangan</th>
                <th style="width:100px">Oleh</th>
                <th style="width:120px">Waktu</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $i => $log)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td>{{ $log->event }}</td>
                <td>{{ $log->details }}</td>
                <td>{{ $log->performedBy }}</td>
                <td>{{ $log->createdAt }}</td>
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
