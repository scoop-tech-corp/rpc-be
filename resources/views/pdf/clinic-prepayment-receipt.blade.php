<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk DP - {{ $dp->registrationNo }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #000;
            padding: 20px;
            margin: 0;
            max-width: 400px;
        }
        .header {
            text-align: center;
            margin-bottom: 14px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h2 {
            margin: 0 0 2px 0;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header p {
            margin: 0;
            font-size: 10px;
            color: #444;
        }
        .section-title {
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #555;
            margin: 12px 0 6px 0;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 3px;
        }
        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
        }
        .label {
            color: #555;
        }
        .value {
            font-weight: 500;
        }
        .total-row {
            font-size: 13px;
            font-weight: bold;
            border-top: 2px solid #000;
            margin-top: 8px;
            padding-top: 6px;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            color: #777;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $dp->locationName }}</h2>
        <p>Struk Pembayaran DP / Uang Muka</p>
        <p>Rawat Inap Pet Clinic</p>
    </div>

    <div class="section-title">Informasi Transaksi</div>
    <div class="row">
        <span class="label">No. Registrasi</span>
        <span class="value">{{ $dp->registrationNo }}</span>
    </div>
    <div class="row">
        <span class="label">Tanggal</span>
        <span class="value">{{ $dp->createdAt }}</span>
    </div>

    <div class="section-title">Informasi Pasien</div>
    <div class="row">
        <span class="label">Nama Owner</span>
        <span class="value">{{ $dp->ownerName }}</span>
    </div>
    <div class="row">
        <span class="label">Nama Pet</span>
        <span class="value">{{ $dp->petName }}</span>
    </div>

    <div class="section-title">Detail Pembayaran</div>
    <div class="row">
        <span class="label">Metode Pembayaran</span>
        <span class="value">{{ $dp->paymentMethod }}</span>
    </div>
    @if($dp->catatan)
    <div class="row">
        <span class="label">Catatan</span>
        <span class="value">{{ $dp->catatan }}</span>
    </div>
    @endif
    <div class="row total-row">
        <span>TOTAL DP</span>
        <span>Rp {{ number_format($dp->amount, 0, ',', '.') }}</span>
    </div>

    <div class="footer">
        <p>Terima kasih telah mempercayakan perawatan hewan kesayangan Anda kepada kami.</p>
        <p>Struk ini sebagai bukti pembayaran yang sah.</p>
    </div>
</body>
</html>
