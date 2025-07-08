<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip Gaji - {{ $user->fullname }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        td,
        th {
            border: 1px solid #333;
            padding: 8px;
            vertical-align: top;
        }

        .no-border {
            border: none !important;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .header-table {
            margin-bottom: 20px;
        }

        .header-table td {
            border: none;
            padding: 5px;
        }

        .logo {
            width: 80px;
            height: auto;
        }

        .company-info {
            text-align: center;
        }

        .company-info h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
        }

        .company-info p {
            margin: 0;
            font-size: 11px;
        }

        .title {
            text-align: center;
            margin: 20px 0;
            font-size: 14px;
            font-weight: bold;
        }

        .employee-info {
            margin-bottom: 20px;
        }

        .employee-info td {
            border: none;
            padding: 3px 5px;
        }

        .employee-info td:first-child {
            width: 25%;
            font-weight: bold;
        }

        .income-expense-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
            padding: 10px;
        }

        .income-expense-table td {
            padding: 10px;
            vertical-align: top;
        }

        .income-list,
        .expense-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .income-list li,
        .expense-list li {
            margin-bottom: 5px;
            padding-left: 0;
        }

        .totals {
            background-color: #f9f9f9;
            font-weight: bold;
        }

        .net-pay {
            background-color: #e8f5e8;
            font-weight: bold;
            text-align: center;
        }

        .signature {
            margin-top: 40px;
            text-align: right;
        }

        .signature-line {
            margin-top: 60px;
            font-weight: bold;
        }

        @media print {
            .container {
                padding: 10px;
            }

            body {
                font-size: 11px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <table class="header-table">
            <tr>
                <td style="width: 25%; vertical-align: top; padding: 0 10px 0 0;">
                    <img src="{{ public_path('storage/Logo/Logo-Radhiyan.png') }}" style="width: 100px;" alt="Logo">
                </td>
                <td style="text-align: left; padding: 0; line-height: 1.3;">
                    <div style="font-weight: bold; font-size: 14px;">RADHIYAN PET & CARE GRUP</div>
                    <div style="font-size: 11px;">Perumahan Mandar Asri Blok C/2-4, Ciracas – Jakarta Timur</div>
                    <div style="font-size: 11px;">D.K.I. Jakarta</div>
                    <div style="font-size: 11px;">Telp. 0813 1224 5500</div>
                </td>
            </tr>
        </table>

        <!-- Title di tengah halaman -->
        <div class="title" style="text-align: center; margin: 10px 0 20px 0;">
            <strong style="font-size: 14px;">REKAP PENGHASILAN MITRA KERJA</strong><br>
            <span style="font-size: 12px;">Periode {{ $period }}</span>
        </div>


        <!-- Employee Info -->
        <table class="employee-info">
            <tr>
                <td>Nama</td>
                <td>: {{ $user->firstName }}</td>
            </tr>
            <tr>
                <td>No ID Mitra Kerja</td>
                <td>: {{ $user->registrationNo }}</td>
            </tr>
            <tr>
                <td>Jabatan</td>
                <td>: {{ $user->jobTitle->jobName ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Status</td>
                <td>: ...........................................</td>
            </tr>
            <tr>
                <td>Awal Masuk Kerja</td>
                <td>: {{ \Carbon\Carbon::parse($user->startDate)->translatedFormat('d F Y') }}</td>
            </tr>
        </table>

        <!-- Income & Expense Table -->
        <table class="income-expense-table" style="width: 100%; border-collapse: collapse; font-size: 12px;">
            <thead>
                <tr style="background-color: #eae4dc;">
                    <th style="border: 1px solid #000; text-align: center;" colspan="2">PEMASUKAN</th>
                    <th style="border: 1px solid #000; text-align: center;" colspan="2">PENGELUARAN</th>
                </tr>
            </thead>
            <tbody>
                @php
                $maxRows = max(count($incomeFields), count($expenseFields));
                $incomeKeys = array_keys($incomeFields);
                $expenseKeys = array_keys($expenseFields);
                @endphp

                @for($i = 0; $i < $maxRows; $i++)
                    <tr>
                    {{-- Kolom Pemasukan --}}
                    <td style="border: 1px solid #000; padding: 5px;">
                        {{ $incomeKeys[$i] ?? '' }}
                    </td>
                    <td style="border: 1px solid #000; padding: 5px; text-align: right;">
                        @isset($incomeKeys[$i])
                        Rp {{ number_format($incomeFields[$incomeKeys[$i]], 0, ',', '.') }}
                        @endisset
                    </td>

                    {{-- Kolom Pengeluaran --}}
                    <td style="border: 1px solid #000; padding: 5px;">
                        {{ $expenseKeys[$i] ?? '' }}
                    </td>
                    <td style="border: 1px solid #000; padding: 5px; text-align: right;">
                        @isset($expenseKeys[$i])
                        Rp {{ number_format($expenseFields[$expenseKeys[$i]], 0, ',', '.') }}
                        @endisset
                    </td>
                    </tr>
                    @endfor

                    {{-- Total --}}
                    <tr style="background-color: #f2f2f2;">
                        <td style="border: 1px solid #000; font-weight: bold;">Total Penghasilan</td>
                        <td style="border: 1px solid #000; text-align: right; font-weight: bold;">
                            Rp {{ number_format($payroll->totalIncome, 0, ',', '.') }}
                        </td>
                        <td style="border: 1px solid #000; font-weight: bold;">Total Deduction</td>
                        <td style="border: 1px solid #000; text-align: right; font-weight: bold;">
                            Rp {{ number_format($payroll->totalDeduction, 0, ',', '.') }}
                        </td>
                    </tr>

                    {{-- Penerimaan Bersih --}}
                    <tr style="background-color: #f2f2f2;">
                        <td colspan="3" style="border: 1px solid #000; font-weight: bold;">
                            Penerimaan Bersih
                        </td>
                        <td style="border: 1px solid #000; text-align: right; font-weight: bold;">
                            Rp {{ number_format($payroll->netPay, 0, ',', '.') }}
                        </td>
                    </tr>
            </tbody>
        </table>

        <!-- Signature -->
        <div class="signature">
            Jakarta, {{ $slipDate }}<br>
            <strong>Corporate Finance & Administration</strong><br>
            <div class="signature-line">
                <strong>({{ $userId->firstName }})</strong>
            </div>
        </div>
    </div>
</body>

</html>