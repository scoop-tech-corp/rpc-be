<?php

namespace App\Http\Controllers\Report;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BookingController extends Controller
{
    public function indexLocation(Request $request)
    {
        $year = $request->input('year', date('Y'));

        // Ambil semua lokasi aktif
        $locations = DB::table('location')
            ->where('isDeleted', 0)
            ->where('status', 1)
            ->orderBy('id')
            ->get(['id', 'locationName']);

        // Build data per lokasi
        $result = [];
        foreach ($locations as $loc) {
            // Total booking per bulan dari tabel bookings
            $bookingsByMonth = DB::table('bookings')
                ->select(DB::raw('MONTH(bookingTime) as month'), DB::raw('COUNT(*) as total'))
                ->where('locationId', $loc->id)
                ->where('isDeleted', 0)
                ->whereYear('bookingTime', $year)
                ->groupBy(DB::raw('MONTH(bookingTime)'))
                ->pluck('total', 'month')
                ->toArray();

            // Total nilai transaksi (dari payment totals yang terhubung via bookingId)
            $valueByMonth = $this->getValueByLocation($loc->id, $year);

            $months = [];
            $totalBooking = 0;
            $totalValue = 0;
            for ($m = 1; $m <= 12; $m++) {
                $booking = $bookingsByMonth[$m] ?? 0;
                $value   = $valueByMonth[$m] ?? 0;
                $months[] = [
                    'month'   => $m,
                    'booking' => $booking,
                    'value'   => $value,
                ];
                $totalBooking += $booking;
                $totalValue   += $value;
            }

            $result[] = [
                'locationId'   => $loc->id,
                'locationName' => $loc->locationName,
                'months'       => $months,
                'totalBooking' => $totalBooking,
                'totalValue'   => $totalValue,
            ];
        }

        return response()->json([
            'year' => (int) $year,
            'data' => $result,
        ]);
    }

    public function indexStatus(Request $request)
    {
        $dateFrom   = $request->input('dateFrom');
        $dateTo     = $request->input('dateTo');
        $locationIds = $request->input('locationId', []);
        if (!is_array($locationIds)) $locationIds = [$locationIds];
        $locationIds = array_filter($locationIds, fn($v) => $v !== '' && $v !== null);

        // ── 1. Hitung jumlah booking per derived-status ───────────────────────
        $statusExpr = "
            CASE
                WHEN isCancelled = 1 THEN 'Dibatalkan'
                WHEN status = 2      THEN 'Ditolak'
                WHEN status = 1      THEN 'Diterima'
                ELSE                      'Menunggu'
            END
        ";

        $countQuery = DB::table('bookings')
            ->select(DB::raw("($statusExpr) as statusLabel"), DB::raw('COUNT(*) as total'))
            ->where('isDeleted', 0)
            ->groupBy(DB::raw("($statusExpr)"));

        if ($dateFrom) $countQuery->whereDate('bookingTime', '>=', $dateFrom);
        if ($dateTo)   $countQuery->whereDate('bookingTime', '<=', $dateTo);
        if (!empty($locationIds)) $countQuery->whereIn('locationId', $locationIds);

        $counts = $countQuery->pluck('total', 'statusLabel')->toArray();

        // ── 2. Hitung nilai transaksi per derived-status ──────────────────────
        // Union seluruh transaksi yang punya bookingId, lalu join ke bookings
        $txUnion = "
            SELECT id, bookingId FROM transactionPetClinics WHERE bookingId IS NOT NULL
            UNION ALL
            SELECT id, bookingId FROM transaction_pet_hotels WHERE bookingId IS NOT NULL
            UNION ALL
            SELECT id, bookingId FROM transaction_pet_salons WHERE bookingId IS NOT NULL
            UNION ALL
            SELECT id, bookingId FROM transaction_breedings WHERE bookingId IS NOT NULL
        ";

        $payUnion = "
            SELECT transactionId, amount FROM transaction_pet_clinic_payment_totals WHERE (isDeleted IS NULL OR isDeleted = 0)
            UNION ALL
            SELECT transactionId, amount FROM transaction_pet_hotel_payment_totals WHERE (isDeleted IS NULL OR isDeleted = 0)
            UNION ALL
            SELECT transactionId, amount FROM transaction_pet_salon_payment_totals WHERE (isDeleted IS NULL OR isDeleted = 0)
            UNION ALL
            SELECT transactionId, amount FROM transaction_breeding_payment_totals WHERE (isDeleted IS NULL OR isDeleted = 0)
        ";

        $bindings = [];
        $whereClauses = ['b.isDeleted = 0'];
        if ($dateFrom) { $whereClauses[] = 'DATE(b.bookingTime) >= ?'; $bindings[] = $dateFrom; }
        if ($dateTo)   { $whereClauses[] = 'DATE(b.bookingTime) <= ?'; $bindings[] = $dateTo; }
        if (!empty($locationIds)) {
            $placeholders = implode(',', array_fill(0, count($locationIds), '?'));
            $whereClauses[] = "b.locationId IN ($placeholders)";
            $bindings = array_merge($bindings, array_values($locationIds));
        }
        $whereSQL = implode(' AND ', $whereClauses);

        $valueSQL = "
            SELECT
                CASE
                    WHEN b.isCancelled = 1 THEN 'Dibatalkan'
                    WHEN b.status = 2      THEN 'Ditolak'
                    WHEN b.status = 1      THEN 'Diterima'
                    ELSE                        'Menunggu'
                END as statusLabel,
                COALESCE(SUM(pay.amount), 0) as totalValue
            FROM ($txUnion) tx
            JOIN ($payUnion) pay ON pay.transactionId = tx.id
            JOIN bookings b ON b.id = tx.bookingId
            WHERE $whereSQL
            GROUP BY statusLabel
        ";

        $valueRows = DB::select($valueSQL, $bindings);
        $values = collect($valueRows)->pluck('totalValue', 'statusLabel')->toArray();

        // ── 3. Susun hasil dengan urutan tetap ────────────────────────────────
        $statusOrder = ['Menunggu', 'Diterima', 'Ditolak', 'Dibatalkan'];
        $result = [];
        foreach ($statusOrder as $s) {
            $result[] = [
                'status' => $s,
                'total'  => (int) ($counts[$s] ?? 0),
                'value'  => (float) ($values[$s] ?? 0),
            ];
        }

        return response()->json(['data' => $result]);
    }

    /**
     * Hitung total nilai transaksi per bulan untuk satu lokasi,
     * dengan menggabungkan 4 tabel transaksi via bookingId → bookings.locationId
     */
    private function getValueByLocation(int $locationId, int $year): array
    {
        $tables = [
            ['tx' => 'transactionPetClinics',  'pay' => 'transaction_pet_clinic_payment_totals'],
            ['tx' => 'transaction_pet_hotels',  'pay' => 'transaction_pet_hotel_payment_totals'],
            ['tx' => 'transaction_pet_salons',  'pay' => 'transaction_pet_salon_payment_totals'],
            ['tx' => 'transaction_breedings',   'pay' => 'transaction_breeding_payment_totals'],
        ];

        $combined = collect();
        foreach ($tables as $t) {
            $rows = DB::table($t['pay'] . ' as pay')
                ->join($t['tx'] . ' as tx', 'tx.id', '=', 'pay.transactionId')
                ->join('bookings as b', 'b.id', '=', 'tx.bookingId')
                ->select(DB::raw('MONTH(b.bookingTime) as month'), DB::raw('SUM(pay.amount) as total'))
                ->where('b.locationId', $locationId)
                ->where('b.isDeleted', 0)
                ->whereNotNull('tx.bookingId')
                ->whereYear('b.bookingTime', $year)
                ->where(function ($q) {
                    $q->whereNull('pay.isDeleted')
                      ->orWhere('pay.isDeleted', 0);
                })
                ->groupBy(DB::raw('MONTH(b.bookingTime)'))
                ->get();

            $combined = $combined->merge($rows);
        }

        // Sum per month across all service types
        $result = [];
        foreach ($combined as $row) {
            $m = (int) $row->month;
            $result[$m] = ($result[$m] ?? 0) + (float) $row->total;
        }

        return $result;
    }

    public function indexCancel(Request $request)
    {
        $dateFrom    = $request->input('dateFrom');
        $dateTo      = $request->input('dateTo');
        $locationIds = $request->input('locationId', []);
        if (!is_array($locationIds)) $locationIds = [$locationIds];
        $locationIds = array_filter(array_values($locationIds), fn($v) => $v !== '' && $v !== null);

        // Label alasan yang dikenal — disimpan sebagai teks di kolom cancellationReason
        $knownReasons = [
            'Berubah Pikiran / Tidak Jadi',
            'Hewan Peliharaan Sudah Sembuh Sendiri',
            'Jadwal Bentrok / Tidak Bisa Hadir',
            'Pindah ke Klinik Lain',
            'Kondisi Keuangan / Biaya Terlalu Mahal',
            'Dokter Tidak Tersedia / Berhalangan',
            'Fasilitas / Peralatan Tidak Tersedia',
            'Klinik Tutup / Hari Libur Mendadak',
            'Slot Penuh / Overbooking',
            'Hewan Peliharaan Meninggal Dunia',
        ];

        // Bangun CASE WHEN sebagai SQL literal (nilai sudah diketahui & fixed, aman tanpa binding)
        $whenSQL = '';
        foreach ($knownReasons as $reason) {
            $escaped  = addslashes($reason);
            $whenSQL .= " WHEN cancellationReason = '{$escaped}' THEN '{$escaped}'";
        }
        $caseExpr = "CASE {$whenSQL} ELSE 'Alasan Lainnya' END";

        // Bangun WHERE clause
        $whereParts = ["isDeleted = 0", "isCancelled = 1"];
        $bindings   = [];

        if ($dateFrom) { $whereParts[] = "DATE(cancellationDate) >= ?"; $bindings[] = $dateFrom; }
        if ($dateTo)   { $whereParts[] = "DATE(cancellationDate) <= ?"; $bindings[] = $dateTo;   }
        if (!empty($locationIds)) {
            $placeholders = implode(',', array_fill(0, count($locationIds), '?'));
            $whereParts[] = "locationId IN ($placeholders)";
            $bindings     = array_merge($bindings, array_values($locationIds));
        }

        $whereSQL = implode(' AND ', $whereParts);

        $sql = "
            SELECT ({$caseExpr}) AS reason,
                   COUNT(*)      AS total
            FROM   bookings
            WHERE  {$whereSQL}
            GROUP  BY ({$caseExpr})
            ORDER  BY total DESC
        ";

        $rows = DB::select($sql, $bindings);

        $result = collect($rows)->map(fn($r) => [
            'reason' => $r->reason,
            'total'  => (int) $r->total,
        ])->values();

        return response()->json([
            'data'       => $result,
            'grandTotal' => $result->sum('total'),
        ]);
    }

    public function indexList(Request $request)
    {
        $itemPerPage = (int) $request->input('rowPerPage', 10);
        $page        = (int) $request->input('goToPage', 1);
        $orderColumn = $request->input('orderColumn', 'e.bookingTime');
        $orderValue  = $request->input('orderValue', 'desc');
        $dateFrom    = $request->input('dateFrom');
        $dateTo      = $request->input('dateTo');
        $locationIds = $request->input('locationId', []);
        if (!is_array($locationIds)) $locationIds = [$locationIds];
        $locationIds = array_filter(array_values($locationIds), fn($v) => $v !== '' && $v !== null);

        $allowedColumns = ['e.bookingTime', 'l.locationName', 'c.firstName', 'e.serviceType', 'e.status'];
        if (!in_array($orderColumn, $allowedColumns)) $orderColumn = 'e.bookingTime';
        if (!in_array(strtolower($orderValue), ['asc', 'desc'])) $orderValue = 'desc';

        $statusExpr = "
            CASE
                WHEN e.isCancelled = 1 THEN 'Dibatalkan'
                WHEN e.status = 2      THEN 'Ditolak'
                WHEN e.status = 1      THEN 'Diterima'
                ELSE                        'Menunggu'
            END
        ";

        $query = DB::table('bookings as e')
            ->join('customer as c',     'c.id', '=', 'e.customerId')
            ->join('customerPets as p', 'p.id', '=', 'e.petId')
            ->join('location as l',     'l.id', '=', 'e.locationId')
            ->leftJoin('users as d',    'd.id', '=', 'e.doctorId')
            ->select([
                'e.id',
                DB::raw("DATE_FORMAT(e.bookingTime, '%d %b %Y') as bookingDate"),
                DB::raw("DATE_FORMAT(e.bookingTime, '%H:%i')    as bookingTime"),
                'l.locationName',
                DB::raw("CONCAT(c.firstName, ' ', COALESCE(c.lastName, '')) as customerName"),
                'p.petName',
                'e.serviceType',
                DB::raw("($statusExpr) as status"),
                DB::raw("CONCAT(d.firstName, ' ', COALESCE(d.lastName, '')) as doctorName"),
            ])
            ->where('e.isDeleted', 0);

        if ($dateFrom) $query->whereDate('e.bookingTime', '>=', $dateFrom);
        if ($dateTo)   $query->whereDate('e.bookingTime', '<=', $dateTo);
        if (!empty($locationIds)) $query->whereIn('e.locationId', $locationIds);

        $count_data = $query->count();
        $offset     = ($page - 1) * $itemPerPage;
        $offset     = ($count_data - $offset < 0) ? 0 : $offset;

        $data = $query
            ->orderBy($orderColumn, $orderValue)
            ->offset($offset)
            ->limit($itemPerPage)
            ->get();

        $totalPaging = $itemPerPage > 0 ? ceil($count_data / $itemPerPage) : 1;

        return response()->json([
            'totalPagination' => $totalPaging,
            'data'            => $data,
        ]);
    }

    public function indexDiagnose(Request $request)
    {
        $itemPerPage = (int) $request->input('rowPerPage', 10);
        $page        = (int) $request->input('goToPage', 1);
        $orderColumn = $request->input('orderColumn', 't.startDate');
        $orderValue  = $request->input('orderValue', 'desc');
        $dateFrom    = $request->input('dateFrom');
        $dateTo      = $request->input('dateTo');
        $search      = $request->input('search');
        $status      = $request->input('status');
        $locationIds = $request->input('locationId', []);
        if (!is_array($locationIds)) $locationIds = [$locationIds];
        $locationIds = array_filter(array_values($locationIds), fn($v) => $v !== '' && $v !== null);

        $allowedColumns = ['t.startDate', 'l.locationName', 'c.firstName', 't.status', 't.registrationNo'];
        if (!in_array($orderColumn, $allowedColumns)) $orderColumn = 't.startDate';
        if (!in_array(strtolower($orderValue), ['asc', 'desc'])) $orderValue = 'desc';

        $query = DB::table('transactionPetClinics as t')
            ->join('location as l',     'l.id',  '=', 't.locationId')
            ->join('customer as c',     'c.id',  '=', 't.customerId')
            ->join('customerPets as cp', 'cp.id', '=', 't.PetId')
            ->join('petCategory as pc', 'pc.id', '=', 'cp.petCategoryId')
            ->leftJoin('transactionPetClinicAnamnesis as a',
                fn($j) => $j->on('a.transactionPetClinicId', '=', 't.id')->where('a.isDeleted', 0))
            ->leftJoin('transactionPetClinicCheckUpResults as cr',
                fn($j) => $j->on('cr.transactionPetClinicId', '=', 't.id')->where('cr.isDeleted', 0))
            ->select([
                't.id',
                DB::raw("DATE_FORMAT(t.startDate, '%d %b %Y') as bookingDate"),
                'l.locationName',
                DB::raw("CONCAT(c.firstName, ' ', COALESCE(c.lastName, '')) as customerName"),
                'a.petCheckRegistrationNo as recordNo',
                'cp.petName',
                't.registrationNo as patientId',
                'cr.weight',
                'pc.petCategoryName as species',
                DB::raw("CASE cp.petGender WHEN 'J' THEN 'Jantan' WHEN 'B' THEN 'Betina' ELSE '-' END as gender"),
                't.status',
                // Services & diagnosis via subquery GROUP_CONCAT
                DB::raw("(
                    SELECT GROUP_CONCAT(DISTINCT s.fullName ORDER BY s.fullName SEPARATOR ', ')
                    FROM   transaction_pet_clinic_services tpcs
                    JOIN   services s ON s.id = tpcs.serviceId
                    WHERE  tpcs.transactionPetClinicId = t.id AND (tpcs.isDeleted IS NULL OR tpcs.isDeleted = 0)
                ) as services"),
                DB::raw("(
                    SELECT GROUP_CONCAT(DISTINCT d.diagnoseDisease ORDER BY d.diagnoseDisease SEPARATOR ', ')
                    FROM   transactionPetClinicDiagnoses d
                    WHERE  d.transactionPetClinicId = t.id AND d.diagnoseDisease IS NOT NULL
                      AND (d.isDeleted IS NULL OR d.isDeleted = 0)
                ) as diagnosis"),
                DB::raw("COALESCE(a.othersCompalints, '') as reasonForVisit"),
            ])
            ->where('t.isDeleted', 0);

        if ($dateFrom) $query->whereDate('t.startDate', '>=', $dateFrom);
        if ($dateTo)   $query->whereDate('t.startDate', '<=', $dateTo);
        if ($status)   $query->where('t.status', $status);
        if ($search)   $query->where(DB::raw("CONCAT(c.firstName, ' ', COALESCE(c.lastName, ''))"), 'like', "%{$search}%");
        if (!empty($locationIds)) $query->whereIn('t.locationId', $locationIds);

        $count_data = $query->count();
        $offset     = max(0, ($page - 1) * $itemPerPage);
        if ($count_data - $offset < 0) $offset = 0;

        $data = $query
            ->orderBy($orderColumn, $orderValue)
            ->offset($offset)
            ->limit($itemPerPage)
            ->get();

        $totalPaging = $itemPerPage > 0 ? ceil($count_data / $itemPerPage) : 1;

        return response()->json([
            'totalPagination' => $totalPaging,
            'data'            => $data,
        ]);
    }

    public function getDiagnoseOptions(Request $request)
    {
        $diagnoses = DB::table('transactionPetClinicDiagnoses as d')
            ->join('transactionPetClinics as t', 't.id', '=', 'd.transactionPetClinicId')
            ->where('t.isDeleted', 0)
            ->whereNotNull('d.diagnoseDisease')
            ->where(function ($q) { $q->whereNull('d.isDeleted')->orWhere('d.isDeleted', 0); })
            ->distinct()
            ->orderBy('d.diagnoseDisease')
            ->pluck('d.diagnoseDisease')
            ->map(fn ($d) => ['value' => $d, 'label' => $d])
            ->values();

        return response()->json($diagnoses);
    }

    public function indexDiagnosesSpeciesGender(Request $request)
    {
        $itemPerPage  = (int) $request->input('rowPerPage', 10);
        $page         = (int) $request->input('goToPage', 1);
        $orderValue   = strtolower($request->input('orderValue', 'asc'));
        $dateFrom     = $request->input('dateFrom');
        $dateTo       = $request->input('dateTo');

        $locationIds  = $request->input('locationId', []);
        $genderIds    = $request->input('genderId', []);   // 'J' or 'B'
        $diagnoseIds  = $request->input('diagnoseId', []); // diagnoseDisease strings
        $speciesIds   = $request->input('speciesId', []);  // petCategory IDs

        foreach (['locationIds','genderIds','diagnoseIds','speciesIds'] as $var) {
            if (!is_array($$var)) $$var = [$$var];
            $$var = array_values(array_filter($$var, fn($v) => $v !== '' && $v !== null));
        }

        if (!in_array($orderValue, ['asc','desc'])) $orderValue = 'asc';

        // 1. Species list from petCategory
        $speciesQuery = DB::table('petCategory')->where('isActive', 1)->orderBy('petCategoryName');
        if (!empty($speciesIds)) $speciesQuery->whereIn('id', $speciesIds);
        $speciesList = $speciesQuery->pluck('petCategoryName')->toArray();

        // 2. Base query (shared for pagination + counts)
        $base = DB::table('transactionPetClinicDiagnoses as d')
            ->join('transactionPetClinics as t', 't.id', '=', 'd.transactionPetClinicId')
            ->join('customerPets as cp',         'cp.id', '=', 't.petId')
            ->join('petCategory as pc',          'pc.id', '=', 'cp.petCategoryId')
            ->where('t.isDeleted', 0)
            ->whereNotNull('d.diagnoseDisease')
            ->where(function ($q) { $q->whereNull('d.isDeleted')->orWhere('d.isDeleted', 0); });

        if ($dateFrom)            $base->whereDate('t.startDate', '>=', $dateFrom);
        if ($dateTo)              $base->whereDate('t.startDate', '<=', $dateTo);
        if (!empty($locationIds)) $base->whereIn('t.locationId', $locationIds);
        if (!empty($genderIds))   $base->whereIn('cp.petGender', $genderIds);
        if (!empty($diagnoseIds)) $base->whereIn('d.diagnoseDisease', $diagnoseIds);
        if (!empty($speciesIds))  $base->whereIn('cp.petCategoryId', $speciesIds);

        // 3. Paginate by unique diagnosis name
        $allDiagnoses    = (clone $base)
            ->selectRaw('d.diagnoseDisease')
            ->groupBy('d.diagnoseDisease')
            ->orderBy('d.diagnoseDisease', $orderValue)
            ->pluck('diagnoseDisease')
            ->toArray();

        $totalDiagnoses  = count($allDiagnoses);
        $totalPagination = $itemPerPage > 0 ? (int) ceil($totalDiagnoses / $itemPerPage) : 1;
        $offset          = max(0, ($page - 1) * $itemPerPage);
        $diagnosesPage   = array_slice($allDiagnoses, $offset, $itemPerPage);

        // 4. Raw counts for this page
        $pivot = [];
        if (!empty($diagnosesPage)) {
            $rows = (clone $base)
                ->whereIn('d.diagnoseDisease', $diagnosesPage)
                ->selectRaw("
                    d.diagnoseDisease,
                    pc.petCategoryName,
                    CASE cp.petGender WHEN 'J' THEN 'jantan' WHEN 'B' THEN 'betina' ELSE 'lainnya' END as gender,
                    COUNT(*) as cnt
                ")
                ->groupBy('d.diagnoseDisease', 'pc.petCategoryName', 'cp.petGender')
                ->get();

            foreach ($rows as $r) {
                $pivot[$r->diagnoseDisease][$r->petCategoryName][$r->gender] = (int) $r->cnt;
            }
        }

        // 5. Build pivot rows
        $species = [];
        foreach ($diagnosesPage as $i => $diagnosisName) {
            $row = [
                'no'        => $offset + $i + 1,
                'diagnosis' => $diagnosisName,
                'total'     => 0,
            ];
            foreach ($speciesList as $speciesName) {
                $jantan = $pivot[$diagnosisName][$speciesName]['jantan'] ?? 0;
                $betina = $pivot[$diagnosisName][$speciesName]['betina'] ?? 0;
                $row[$speciesName] = ['jantan' => $jantan, 'betina' => $betina];
                $row['total'] += $jantan + $betina;
            }
            $species[] = $row;
        }

        return response()->json([
            'speciesList'     => $speciesList,
            'totalPagination' => $totalPagination,
            'data'            => ['species' => $species],
        ]);
    }

    /* ─────────── DUMMY PLACEHOLDER (kept for backward compat) ─────────── */
    private function _dummyDiagnosesSpeciesGender_UNUSED()
    {
        $data = [
            'speciesList' => [
                'anjing', 'ayam', 'burung', 'gecko', 'hamster', 'iguana', 'kelinci',
                'marmut', 'monyet', 'musang', 'naga', 'other', 'otter', 'sugarGlider'
            ],
            'totalPagination' => 1,
            'data' => [
                'species' => [
                    [
                        'no' => '1',
                        'diagnosis' => '(Suspect) Limpoma',
                        'total' => 0,
                        'anjing' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'ayam' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'burung' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'gecko' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'hamster' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'iguana' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'kelinci' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'marmut' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'monyet' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'musang' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'naga' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'other' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'otter' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'sugarGlider' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                    ],
                    [
                        'no' => '2',
                        'diagnosis' => '(Suspect) Salmonellosis',
                        'total' => 0,
                        'anjing' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'ayam' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'burung' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'gecko' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'hamster' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'iguana' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'kelinci' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'marmut' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'monyet' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'musang' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'naga' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'other' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'otter' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'sugarGlider' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                    ],
                    [
                        'no' => '3',
                        'total' => 0,
                        'diagnosis' => 'Abnormalitas Gigi',
                        'anjing' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'ayam' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'burung' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'gecko' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'hamster' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'iguana' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'kelinci' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'marmut' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'monyet' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'musang' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'naga' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'other' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'otter' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'sugarGlider' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                    ],
                ]
            ]
        ];

        // (unused dummy)
    }

    public function exportDiagnosesSpeciesGender(Request $request)
    {
        $data = [
            'totalPagination' => 1,
            'table' => [
                'data' => [
                    [
                        'no' => '1',
                        'diagnosis' => '(Suspect) Limpoma',
                        'anjing' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'ayam' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'burung' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'gecko' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'hamster' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'iguana' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'kelinci' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'marmut' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'monyet' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'musang' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'naga' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'other' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'otter' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'sugarglider' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'total' => '0'
                    ],
                    [
                        'no' => '2',
                        'diagnosis' => '(Suspect) Salmonellosis',
                        'anjing' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'ayam' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'burung' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'gecko' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'hamster' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'iguana' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'kelinci' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'marmut' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'monyet' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'musang' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'naga' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'other' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'otter' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'sugarglider' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'total' => '0'
                    ],
                    [
                        'no' => '3',
                        'diagnosis' => 'Abnormalitas Gigi',
                        'anjing' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'ayam' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'burung' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'gecko' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'hamster' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'iguana' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'kelinci' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'marmut' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'monyet' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'musang' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'naga' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'other' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'otter' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'sugarglider' => [
                            'betina' => 0,
                            'jantan' => 0
                        ],
                        'total' => '0'
                    ],
                ],
            ]
        ];

        $spreadsheet = IOFactory::load(public_path() . '/template/report/' . 'Template_Report_Booking_Diagnoses_Species_Gender.xlsx');
        $sheet = $spreadsheet->getSheet(0);

        $sheet->setCellValue('A2', 'No');
        $sheet->setCellValue('B2', 'Diagnosis');
        $sheet->setCellValue('D1', 'Anjing');
        $sheet->setCellValue('F1', 'Ayam');
        $sheet->setCellValue('H1', 'Burung');
        $sheet->setCellValue('J1', 'Gecko');
        $sheet->setCellValue('L1', 'Hamster');
        $sheet->setCellValue('N1', 'Iguana');
        $sheet->setCellValue('O1', 'Kelinci');
        $sheet->setCellValue('Q1', 'Marmut');
        $sheet->setCellValue('S1', 'Monyet');
        $sheet->setCellValue('U1', 'Musang');
        $sheet->setCellValue('W1', 'Naga');
        $sheet->setCellValue('Y1', 'Other');
        $sheet->setCellValue('AA1', 'Otter/Berang-Berang');
        $sheet->setCellValue('AC1', 'Sugarglider');

        $sheet->mergeCells('A1:B1');
        $sheet->mergeCells('C1:D1');
        $sheet->mergeCells('E1:F1');
        $sheet->mergeCells('G1:H1');
        $sheet->mergeCells('I1:J1');
        $sheet->mergeCells('K1:L1');
        $sheet->mergeCells('M1:N1');
        $sheet->mergeCells('P1:Q1');
        $sheet->mergeCells('R1:S1');
        $sheet->mergeCells('T1:U1');
        $sheet->mergeCells('V1:W1');
        $sheet->mergeCells('X1:Y1');
        $sheet->mergeCells('Z1:AA1');
        $sheet->mergeCells('AB1:AC1');

        $sheet->setCellValue('C2', 'Betina');
        $sheet->setCellValue('D2', 'Jantan');
        $sheet->setCellValue('E2', 'Betina');
        $sheet->setCellValue('F2', 'Jantan');
        $sheet->setCellValue('G2', 'Betina');
        $sheet->setCellValue('H2', 'Jantan');
        $sheet->setCellValue('I2', 'Betina');
        $sheet->setCellValue('J2', 'Jantan');
        $sheet->setCellValue('K2', 'Betina');
        $sheet->setCellValue('L2', 'Jantan');
        $sheet->setCellValue('M2', 'Betina');
        $sheet->setCellValue('N2', 'Jantan');
        $sheet->setCellValue('O2', 'Betina');
        $sheet->setCellValue('P2', 'Betina');
        $sheet->setCellValue('Q2', 'Jantan');
        $sheet->setCellValue('R2', 'Betina');
        $sheet->setCellValue('S2', 'Jantan');
        $sheet->setCellValue('T2', 'Betina');
        $sheet->setCellValue('U2', 'Jantan');
        $sheet->setCellValue('V2', 'Betina');
        $sheet->setCellValue('W2', 'Jantan');
        $sheet->setCellValue('X2', 'Betina');
        $sheet->setCellValue('Y2', 'Jantan');
        $sheet->setCellValue('Z2', 'Betina');
        $sheet->setCellValue('AA2', 'Jantan');
        $sheet->setCellValue('AB2', 'Betina');
        $sheet->setCellValue('AC2', 'Jantan');


        
        $sheet->getStyle('A1:AD2')->getFont()->setBold(true);
        $sheet->getStyle('A1:AD2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:AD2')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        
        $row = 3;  
        foreach ($data['table']['data'] as $item) {
            $sheet->setCellValue("A{$row}", $item['no']);
            $sheet->setCellValue("B{$row}", $item['diagnosis']);
            $sheet->setCellValue("C{$row}", $item['anjing']['betina']);
            $sheet->setCellValue("D{$row}", $item['anjing']['jantan']);
            $sheet->setCellValue("E{$row}", $item['ayam']['betina']);
            $sheet->setCellValue("F{$row}", $item['ayam']['jantan']);
            $sheet->setCellValue("G{$row}", $item['burung']['betina']);
            $sheet->setCellValue("H{$row}", $item['burung']['jantan']);
            $sheet->setCellValue("I{$row}", $item['gecko']['betina']);
            $sheet->setCellValue("J{$row}", $item['gecko']['jantan']);
            $sheet->setCellValue("K{$row}", $item['hamster']['betina']);
            $sheet->setCellValue("L{$row}", $item['hamster']['jantan']);
            $sheet->setCellValue("M{$row}", $item['iguana']['betina']);
            $sheet->setCellValue("N{$row}", $item['iguana']['jantan']);
            $sheet->setCellValue("O{$row}", $item['kelinci']['betina']);
            $sheet->setCellValue("P{$row}", $item['marmut']['betina']);
            $sheet->setCellValue("Q{$row}", $item['marmut']['jantan']);
            $sheet->setCellValue("R{$row}", $item['monyet']['betina']);
            $sheet->setCellValue("S{$row}", $item['monyet']['jantan']);
            $sheet->setCellValue("T{$row}", $item['musang']['betina']);
            $sheet->setCellValue("U{$row}", $item['musang']['jantan']);
            $sheet->setCellValue("V{$row}", $item['naga']['betina']);
            $sheet->setCellValue("W{$row}", $item['naga']['jantan']);
            $sheet->setCellValue("X{$row}", $item['other']['betina']);
            $sheet->setCellValue("Y{$row}", $item['other']['jantan']);
            $sheet->setCellValue("Z{$row}", $item['otter']['betina']);
            $sheet->setCellValue("AA{$row}", $item['otter']['jantan']);
            $sheet->setCellValue("AB{$row}", $item['sugarglider']['betina']);
            $sheet->setCellValue("AC{$row}", $item['sugarglider']['jantan']);
            $sheet->setCellValue("AD{$row}", $item['total']);

            $sheet->getStyle("A{$row}:AD{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

            
            foreach (range('A', 'AD') as $columnID) {
                $sheet->getColumnDimension($columnID)->setAutoSize(true);
            }

            $row++;  
        }

        
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . 'Export Booking By Diagnoses, Species, Gender.xlsx';
        $writer->save($newFilePath);

        
        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Booking By Diagnoses, Species, Gender.xlsx"',
        ]);
    }
}
