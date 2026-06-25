<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Customer List</title>
    <style>
        * { box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #111;
            margin: 0;
            padding: 16px 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 12px;
            border-bottom: 2px solid #1e3a5f;
            padding-bottom: 8px;
        }

        .header h2 {
            margin: 0 0 2px 0;
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #1e3a5f;
        }

        .header p {
            margin: 0;
            font-size: 9px;
            color: #555;
        }

        .meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 9px;
            color: #444;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        thead tr {
            background-color: #1e3a5f;
            color: #fff;
        }

        thead th {
            padding: 5px 6px;
            text-align: left;
            font-weight: bold;
            white-space: nowrap;
        }

        tbody tr:nth-child(even) td {
            background-color: #f3f6fb;
        }

        tbody tr:nth-child(odd) td {
            background-color: #ffffff;
        }

        tbody td {
            padding: 4px 6px;
            border-bottom: 1px solid #dde3ec;
            vertical-align: top;
        }

        .center { text-align: center; }

        .footer {
            margin-top: 14px;
            font-size: 8px;
            color: #888;
            border-top: 1px solid #dde3ec;
            padding-top: 5px;
            display: flex;
            justify-content: space-between;
        }

        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            background-color: #d1fae5;
            color: #065f46;
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>Radhiyan Pet &amp; Care</h2>
        <p>Customer List &mdash; {{ $locationLabel }}</p>
    </div>

    <div class="meta">
        <span>Total Customer: <strong>{{ count($customers) }}</strong></span>
        <span>Dicetak: {{ $generatedAt }}</span>
    </div>

    <table>
        <thead>
            <tr>
                <th class="center" style="width:30px">No</th>
                <th style="width:90px">No. Member</th>
                <th style="width:160px">Nama Customer</th>
                <th style="width:100px">Grup</th>
                <th class="center" style="width:55px">Total Pet</th>
                <th style="width:110px">Lokasi</th>
                <th style="width:110px">No. Telepon</th>
                <th style="width:140px">Email</th>
                <th style="width:90px">Dibuat Oleh</th>
                <th style="width:100px">Dibuat Tanggal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customers as $i => $c)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td>{{ $c->memberNo ?? '-' }}</td>
                <td>{{ $c->customerName }}</td>
                <td>{{ $c->customerGroup ?: '-' }}</td>
                <td class="center">{{ $c->totalPet }}</td>
                <td>{{ $c->location ?? '-' }}</td>
                <td>{{ trim($c->phoneNumber) ?: '-' }}</td>
                <td>{{ $c->emailAddress ?: '-' }}</td>
                <td>{{ $c->createdBy ?? '-' }}</td>
                <td>{{ $c->createdAt ? \Carbon\Carbon::parse($c->createdAt)->format('d/m/Y') : '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="center" style="padding:14px; color:#888;">
                    Tidak ada data customer.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <span>RPC &mdash; Customer List Report</span>
        <span>Generated: {{ $generatedAt }}</span>
    </div>

</body>
</html>
