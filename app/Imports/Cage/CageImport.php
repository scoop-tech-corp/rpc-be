<?php

namespace App\Imports\Cage;

use DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;

class CageImport implements ToCollection, WithHeadingRow
{
    use Importable;

    private int   $userId;
    private array $locationMap; // locationName (lowercase) → id
    private array $errors   = [];
    private int   $inserted = 0;
    private int   $skipped  = 0;

    private const VALID_TYPES      = ['hotel', 'breeding', 'salon', 'general'];
    private const VALID_SIZES      = ['S', 'M', 'L', 'XL', ''];
    private const VALID_CONDITIONS = ['baik', 'perlu_perhatian', 'tidak_layak'];

    public function __construct(int $userId)
    {
        $this->userId = $userId;

        // Build lokasi map sekali di awal (case-insensitive)
        $locations = DB::table('location')
            ->where('isDeleted', 0)
            ->select('id', 'locationName')
            ->get();

        foreach ($locations as $loc) {
            $this->locationMap[strtolower(trim($loc->locationName))] = $loc->id;
        }
    }

    public function collection(Collection $rows): void
    {
        $now = Carbon::now();

        foreach ($rows as $index => $row) {
            $rowNo = $index + 2; // +2 karena baris 1 = header

            // Skip baris kosong
            if (empty(trim((string)($row['lokasi'] ?? ''))) && empty(trim((string)($row['nama_kandang'] ?? '')))) {
                continue;
            }

            // ── Validasi ─────────────────────────────────────────
            $lokasiRaw    = trim((string)($row['lokasi']       ?? ''));
            $cageName     = trim((string)($row['nama_kandang'] ?? ''));
            $type         = strtolower(trim((string)($row['tipe']       ?? '')));
            $size         = strtoupper(trim((string)($row['ukuran']     ?? '')));
            $statusRaw    = trim((string)($row['status']      ?? '1'));
            $conditionRaw = strtolower(trim((string)($row['kondisi']    ?? 'baik')));
            $capacity     = (int)($row['kapasitas'] ?? 1);
            $amount       = (int)($row['jumlah']    ?? 1);
            $notes        = trim((string)($row['catatan']  ?? ''));

            $rowErrors = [];

            // Lokasi
            $locationId = $this->locationMap[strtolower($lokasiRaw)] ?? null;
            if (!$locationId) {
                $rowErrors[] = "Lokasi '{$lokasiRaw}' tidak ditemukan di sistem.";
            }

            // Nama kandang
            if (empty($cageName)) {
                $rowErrors[] = 'Nama kandang wajib diisi.';
            } elseif (strlen($cageName) > 100) {
                $rowErrors[] = 'Nama kandang maks 100 karakter.';
            }

            // Tipe
            if (!in_array($type, self::VALID_TYPES)) {
                $rowErrors[] = "Tipe '{$type}' tidak valid. Gunakan: hotel, breeding, salon, general.";
            }

            // Ukuran
            if (!in_array($size, self::VALID_SIZES)) {
                $rowErrors[] = "Ukuran '{$size}' tidak valid. Gunakan: S, M, L, XL, atau kosongkan.";
            }

            // Status
            $status = in_array($statusRaw, ['1', '0']) ? (int)$statusRaw : 1;

            // Kondisi
            if (!in_array($conditionRaw, self::VALID_CONDITIONS)) {
                $rowErrors[] = "Kondisi '{$conditionRaw}' tidak valid. Gunakan: baik, perlu_perhatian, tidak_layak.";
            }

            // Kapasitas & jumlah
            if ($capacity < 1) $rowErrors[] = 'Kapasitas minimal 1.';
            if ($amount   < 1) $rowErrors[] = 'Jumlah minimal 1.';

            // Catatan
            if (strlen($notes) > 300) $rowErrors[] = 'Catatan maks 300 karakter.';

            if (!empty($rowErrors)) {
                $this->errors[] = ['row' => $rowNo, 'errors' => $rowErrors];
                continue;
            }

            // ── Cek duplikat ──────────────────────────────────────
            $exists = DB::table('cages')
                ->where('locationId', $locationId)
                ->where('cageName', $cageName)
                ->where('isDeleted', 0)
                ->exists();

            if ($exists) {
                $this->skipped++;
                continue;
            }

            // ── Insert ────────────────────────────────────────────
            DB::table('cages')->insert([
                'locationId'      => $locationId,
                'cageName'        => $cageName,
                'type'            => $type,
                'size'            => $size ?: null,
                'status'          => $status,
                'conditionStatus' => $conditionRaw,
                'capacity'        => $capacity,
                'amount'          => $amount,
                'notes'           => $notes ?: null,
                'isDeleted'       => 0,
                'userId'          => $this->userId,
                'userUpdateId'    => null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);

            $this->inserted++;
        }
    }

    public function getInserted(): int  { return $this->inserted; }
    public function getSkipped():  int  { return $this->skipped;  }
    public function getErrors():   array { return $this->errors;   }
}
