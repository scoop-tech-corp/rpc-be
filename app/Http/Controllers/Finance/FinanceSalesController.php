<?php

namespace App\Http\Controllers\Finance;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Exports\Finance\SalesReport;
use Maatwebsite\Excel\Facades\Excel;

class FinanceSalesController extends Controller
{
    // ══════════════════════════════════════════════════════════════════════════
    // CONFIG — mapping service type → tables & nota prefix
    // ══════════════════════════════════════════════════════════════════════════
    private const SERVICE_MAP = [
        'Pet Clinic' => [
            'mainTable'    => 'transactionPetClinics',
            'paymentTable' => 'transaction_pet_clinic_payment_totals',
            'notaPrefix'   => 'PC',
            'logFn'        => 'transactionPetClinicLog',
            'statusFn'     => 'statusTransactionPetClinic',
        ],
        'Pet Hotel'  => [
            'mainTable'    => 'transaction_pet_hotels',
            'paymentTable' => 'transaction_pet_hotel_payment_totals',
            'notaPrefix'   => 'PH',
            'logFn'        => 'transactionPetHotelLog',
            'statusFn'     => 'statusTransactionPetHotel',
        ],
        'Pet Salon'  => [
            'mainTable'    => 'transaction_pet_salons',
            'paymentTable' => 'transaction_pet_salon_payment_totals',
            'notaPrefix'   => 'PSL',
            'logFn'        => 'transactionPetSalonLog',
            'statusFn'     => 'statusTransactionPetSalon',
        ],
        'Breeding'   => [
            'mainTable'    => 'transaction_breedings',
            'paymentTable' => 'transaction_breeding_payment_totals',
            'notaPrefix'   => 'BR',
            'logFn'        => 'transactionBreedingLog',
            'statusFn'     => 'statusTransactionBreeding',
        ],
        'Pet Shop'   => [
            'mainTable'    => 'transactionpetshop',
            'paymentTable' => null,   // Pet Shop bayar langsung di tabel utama
            'notaPrefix'   => 'PS',
            'logFn'        => null,
            'statusFn'     => null,
        ],
    ];

    // ══════════════════════════════════════════════════════════════════════════
    // UNION subquery — satu baris per transaksi
    // ══════════════════════════════════════════════════════════════════════════
    protected function buildBaseUnion(Request $request)
    {
        // 1. Pet Clinic ─────────────────────────────────────────────────────
        $clinic = DB::table('transactionPetClinics as t')
            ->join('transaction_pet_clinic_payment_totals as pt', function ($j) {
                $j->on('pt.transactionId', '=', 't.id')
                  ->where('pt.isDeleted', 0)
                  ->whereNotNull('pt.nota_number');
            })
            ->join('customer as c', 'c.id', '=', 't.customerId')
            ->join('location as l', 'l.id', '=', 't.locationId')
            ->join('users as u', 'u.id', '=', 't.userId')
            ->select(
                DB::raw("MIN(pt.nota_number) as invoiceNumber"),
                DB::raw("'Pet Clinic' as serviceType"),
                DB::raw("CONCAT(c.firstName, ' ', COALESCE(c.lastName, '')) as customerName"),
                'c.memberNo',
                'l.locationName',
                'l.id as locationId',
                DB::raw("DATE(t.created_at) as transactionDate"),
                DB::raw("MIN(CASE WHEN pt.isPayed = 0 AND pt.nextPayment IS NOT NULL THEN pt.nextPayment END) as dueDate"),
                DB::raw("MAX(pt.amount) as total"),
                DB::raw("SUM(pt.amountPaid) as paidAmount"),
                DB::raw("GREATEST(MAX(pt.amount) - SUM(pt.amountPaid), 0) as remaining"),
                DB::raw("CASE
                    WHEN t.status = 'Batal' THEN 'cancelled'
                    WHEN (MAX(pt.amount) - SUM(pt.amountPaid)) <= 0 THEN 'paid'
                    WHEN SUM(pt.amountPaid) > 0 THEN 'partial'
                    ELSE 'unpaid'
                END as status"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(t.created_at, '%d/%m/%Y %H:%i') as createdAt"),
                't.created_at as sortableDate'
            )
            ->where('t.isDeleted', 0)
            ->groupBy(
                't.id', 't.status', 't.created_at',
                'c.firstName', 'c.lastName', 'c.memberNo',
                'l.locationName', 'l.id', 'u.firstName'
            );

        // 2. Pet Hotel ──────────────────────────────────────────────────────
        $hotel = DB::table('transaction_pet_hotels as t')
            ->join('transaction_pet_hotel_payment_totals as pt', function ($j) {
                $j->on('pt.transactionId', '=', 't.id')
                  ->where('pt.isDeleted', 0)
                  ->whereNotNull('pt.nota_number');
            })
            ->join('customer as c', 'c.id', '=', 't.customerId')
            ->join('location as l', 'l.id', '=', 't.locationId')
            ->join('users as u', 'u.id', '=', 't.userId')
            ->select(
                DB::raw("MIN(pt.nota_number) as invoiceNumber"),
                DB::raw("'Pet Hotel' as serviceType"),
                DB::raw("CONCAT(c.firstName, ' ', COALESCE(c.lastName, '')) as customerName"),
                'c.memberNo',
                'l.locationName',
                'l.id as locationId',
                DB::raw("DATE(t.created_at) as transactionDate"),
                DB::raw("MIN(CASE WHEN pt.isPayed = 0 AND pt.nextPayment IS NOT NULL THEN pt.nextPayment END) as dueDate"),
                DB::raw("MAX(pt.amount) as total"),
                DB::raw("SUM(pt.amountPaid) as paidAmount"),
                DB::raw("GREATEST(MAX(pt.amount) - SUM(pt.amountPaid), 0) as remaining"),
                DB::raw("CASE
                    WHEN t.status = 'Batal' THEN 'cancelled'
                    WHEN (MAX(pt.amount) - SUM(pt.amountPaid)) <= 0 THEN 'paid'
                    WHEN SUM(pt.amountPaid) > 0 THEN 'partial'
                    ELSE 'unpaid'
                END as status"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(t.created_at, '%d/%m/%Y %H:%i') as createdAt"),
                't.created_at as sortableDate'
            )
            ->where('t.isDeleted', 0)
            ->groupBy(
                't.id', 't.status', 't.created_at',
                'c.firstName', 'c.lastName', 'c.memberNo',
                'l.locationName', 'l.id', 'u.firstName'
            );

        // 3. Pet Salon ──────────────────────────────────────────────────────
        $salon = DB::table('transaction_pet_salons as t')
            ->join('transaction_pet_salon_payment_totals as pt', function ($j) {
                $j->on('pt.transactionId', '=', 't.id')
                  ->where('pt.isDeleted', 0)
                  ->whereNotNull('pt.nota_number');
            })
            ->join('customer as c', 'c.id', '=', 't.customerId')
            ->join('location as l', 'l.id', '=', 't.locationId')
            ->join('users as u', 'u.id', '=', 't.userId')
            ->select(
                DB::raw("MIN(pt.nota_number) as invoiceNumber"),
                DB::raw("'Pet Salon' as serviceType"),
                DB::raw("CONCAT(c.firstName, ' ', COALESCE(c.lastName, '')) as customerName"),
                'c.memberNo',
                'l.locationName',
                'l.id as locationId',
                DB::raw("DATE(t.created_at) as transactionDate"),
                DB::raw("MIN(CASE WHEN pt.isPayed = 0 AND pt.nextPayment IS NOT NULL THEN pt.nextPayment END) as dueDate"),
                DB::raw("MAX(pt.amount) as total"),
                DB::raw("SUM(pt.amountPaid) as paidAmount"),
                DB::raw("GREATEST(MAX(pt.amount) - SUM(pt.amountPaid), 0) as remaining"),
                DB::raw("CASE
                    WHEN t.status = 'Batal' THEN 'cancelled'
                    WHEN (MAX(pt.amount) - SUM(pt.amountPaid)) <= 0 THEN 'paid'
                    WHEN SUM(pt.amountPaid) > 0 THEN 'partial'
                    ELSE 'unpaid'
                END as status"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(t.created_at, '%d/%m/%Y %H:%i') as createdAt"),
                't.created_at as sortableDate'
            )
            ->where('t.isDeleted', 0)
            ->groupBy(
                't.id', 't.status', 't.created_at',
                'c.firstName', 'c.lastName', 'c.memberNo',
                'l.locationName', 'l.id', 'u.firstName'
            );

        // 4. Breeding ───────────────────────────────────────────────────────
        $breeding = DB::table('transaction_breedings as t')
            ->join('transaction_breeding_payment_totals as pt', function ($j) {
                $j->on('pt.transactionId', '=', 't.id')
                  ->where('pt.isDeleted', 0)
                  ->whereNotNull('pt.nota_number');
            })
            ->join('customer as c', 'c.id', '=', 't.customerId')
            ->join('location as l', 'l.id', '=', 't.locationId')
            ->join('users as u', 'u.id', '=', 't.userId')
            ->select(
                DB::raw("MIN(pt.nota_number) as invoiceNumber"),
                DB::raw("'Breeding' as serviceType"),
                DB::raw("CONCAT(c.firstName, ' ', COALESCE(c.lastName, '')) as customerName"),
                'c.memberNo',
                'l.locationName',
                'l.id as locationId',
                DB::raw("DATE(t.created_at) as transactionDate"),
                DB::raw("MIN(CASE WHEN pt.isPayed = 0 AND pt.nextPayment IS NOT NULL THEN pt.nextPayment END) as dueDate"),
                DB::raw("MAX(pt.amount) as total"),
                DB::raw("SUM(pt.amountPaid) as paidAmount"),
                DB::raw("GREATEST(MAX(pt.amount) - SUM(pt.amountPaid), 0) as remaining"),
                DB::raw("CASE
                    WHEN t.status = 'Batal' THEN 'cancelled'
                    WHEN (MAX(pt.amount) - SUM(pt.amountPaid)) <= 0 THEN 'paid'
                    WHEN SUM(pt.amountPaid) > 0 THEN 'partial'
                    ELSE 'unpaid'
                END as status"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(t.created_at, '%d/%m/%Y %H:%i') as createdAt"),
                't.created_at as sortableDate'
            )
            ->where('t.isDeleted', 0)
            ->groupBy(
                't.id', 't.status', 't.created_at',
                'c.firstName', 'c.lastName', 'c.memberNo',
                'l.locationName', 'l.id', 'u.firstName'
            );

        // 5. Pet Shop ───────────────────────────────────────────────────────
        $shop = DB::table('transactionpetshop as tp')
            ->join('customer as c', 'c.id', '=', 'tp.customerId')
            ->join('location as l', 'l.id', '=', 'tp.locationId')
            ->join('users as u', 'u.id', '=', 'tp.userId')
            ->select(
                'tp.no_nota as invoiceNumber',
                DB::raw("'Pet Shop' as serviceType"),
                DB::raw("CONCAT(c.firstName, ' ', COALESCE(c.lastName, '')) as customerName"),
                'c.memberNo',
                'l.locationName',
                'l.id as locationId',
                DB::raw("DATE(tp.created_at) as transactionDate"),
                DB::raw("NULL as dueDate"),
                'tp.totalAmount as total',
                DB::raw("CASE WHEN tp.isPayed = 1 THEN tp.totalAmount ELSE 0 END as paidAmount"),
                DB::raw("CASE WHEN tp.isPayed = 1 THEN 0 ELSE tp.totalAmount END as remaining"),
                DB::raw("CASE WHEN tp.isPayed = 1 THEN 'paid' ELSE 'unpaid' END as status"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(tp.created_at, '%d/%m/%Y %H:%i') as createdAt"),
                'tp.created_at as sortableDate'
            )
            ->where('tp.isDeleted', 0)
            ->whereNotNull('tp.no_nota');

        $union = $clinic
            ->unionAll($hotel)
            ->unionAll($salon)
            ->unionAll($breeding)
            ->unionAll($shop);

        return DB::table(DB::raw("({$union->toSql()}) as sales"))
            ->mergeBindings($union);
    }

    protected function applyFilters($query, Request $request)
    {
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->locationId && is_array($request->locationId) && count($request->locationId)) {
            $query->whereIn('locationId', $request->locationId);
        }
        if ($request->startDate && $request->endDate) {
            $query->whereBetween('transactionDate', [$request->startDate, $request->endDate]);
        }
        if ($request->search) {
            $kw = $request->search;
            $query->where(function ($q) use ($kw) {
                $q->where('invoiceNumber', 'like', "%{$kw}%")
                  ->orWhere('customerName',   'like', "%{$kw}%")
                  ->orWhere('locationName',   'like', "%{$kw}%")
                  ->orWhere('serviceType',    'like', "%{$kw}%");
            });
        }
        return $query;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // GET finance/sales/summary — widget statistik ringkasan
    // ══════════════════════════════════════════════════════════════════════════
    public function summary(Request $request)
    {
        $query = $this->buildBaseUnion($request);
        $query = $this->applyFilters($query, $request);

        // ── Agregasi global ─────────────────────────────────────────────────
        $stats = (clone $query)->selectRaw("
            COUNT(*)                                                   AS totalTransactions,
            COALESCE(SUM(total),       0)                              AS totalRevenue,
            COALESCE(SUM(paidAmount),  0)                              AS totalPaid,
            COALESCE(SUM(remaining),   0)                              AS totalOutstanding,
            SUM(CASE WHEN status = 'paid'      THEN 1 ELSE 0 END)     AS countPaid,
            SUM(CASE WHEN status = 'partial'   THEN 1 ELSE 0 END)     AS countPartial,
            SUM(CASE WHEN status = 'unpaid'    THEN 1 ELSE 0 END)     AS countUnpaid,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END)     AS countCancelled
        ")->first();

        // ── Breakdown per jenis layanan ─────────────────────────────────────
        $byService = (clone $query)
            ->selectRaw("
                serviceType,
                COUNT(*)                  AS count,
                COALESCE(SUM(total), 0)   AS revenue,
                COALESCE(SUM(paidAmount), 0) AS paid,
                COALESCE(SUM(remaining),  0) AS outstanding
            ")
            ->groupBy('serviceType')
            ->orderBy('serviceType')
            ->get()
            ->map(fn ($r) => [
                'serviceType'  => $r->serviceType,
                'count'        => (int)   $r->count,
                'revenue'      => (float) $r->revenue,
                'paid'         => (float) $r->paid,
                'outstanding'  => (float) $r->outstanding,
            ]);

        return response()->json([
            'totalTransactions' => (int)   ($stats->totalTransactions ?? 0),
            'totalRevenue'      => (float) ($stats->totalRevenue      ?? 0),
            'totalPaid'         => (float) ($stats->totalPaid         ?? 0),
            'totalOutstanding'  => (float) ($stats->totalOutstanding  ?? 0),
            'countPaid'         => (int)   ($stats->countPaid         ?? 0),
            'countPartial'      => (int)   ($stats->countPartial      ?? 0),
            'countUnpaid'       => (int)   ($stats->countUnpaid       ?? 0),
            'countCancelled'    => (int)   ($stats->countCancelled    ?? 0),
            'byService'         => $byService,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // GET finance/sales — list dengan pagination
    // ══════════════════════════════════════════════════════════════════════════
    public function index(Request $request)
    {
        $itemPerPage = (int) ($request->rowPerPage ?? 10);
        $page        = (int) ($request->goToPage ?? 1);

        $allowedColumns = [
            'invoiceNumber', 'customerName', 'locationName',
            'serviceType', 'transactionDate', 'dueDate',
            'total', 'paidAmount', 'remaining', 'status', 'createdAt',
        ];

        $orderColumn = in_array($request->orderColumn, $allowedColumns)
            ? $request->orderColumn : 'sortableDate';
        $orderValue = in_array(strtolower($request->orderValue ?? ''), ['asc', 'desc'])
            ? $request->orderValue : 'desc';

        $query = $this->buildBaseUnion($request);
        $query = $this->applyFilters($query, $request);

        $countQuery = clone $query;
        $countData  = $countQuery->count();

        if (!$itemPerPage) {
            return responseIndex(0, []);
        }
        $offset = ($page - 1) * $itemPerPage;
        if ($offset > $countData) $offset = 0;

        $data = $query
            ->orderBy($orderColumn, $orderValue)
            ->offset($offset)
            ->limit($itemPerPage)
            ->get()
            ->transform(fn($r) => tap($r, function ($r) {
                $r->total      = (float) $r->total;
                $r->paidAmount = (float) $r->paidAmount;
                $r->remaining  = (float) $r->remaining;
            }));

        return responseIndex((int) ceil($countData / max($itemPerPage, 1)), $data);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // GET finance/sales/export
    // ══════════════════════════════════════════════════════════════════════════
    public function exportExcel(Request $request)
    {
        return Excel::download(
            new SalesReport($request),
            'sales-list-' . date('Ymd-His') . '.xlsx'
        );
    }

    // ══════════════════════════════════════════════════════════════════════════
    // GET finance/sales/payment-methods
    // ══════════════════════════════════════════════════════════════════════════
    public function paymentMethods()
    {
        $methods = DB::table('paymentMethodFinances')
            ->where('isDeleted', 0)
            ->select('id', 'paymentMethod as name')
            ->orderBy('paymentMethod')
            ->get();

        return response()->json($methods);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // GET finance/sales/payment-detail?invoiceNumber=xxx&serviceType=Pet+Clinic
    // Riwayat pembayaran untuk satu invoice
    // ══════════════════════════════════════════════════════════════════════════
    public function paymentDetail(Request $request)
    {
        $invoiceNumber = $request->invoiceNumber;
        $serviceType   = $request->serviceType;

        if (!$invoiceNumber || !$serviceType) {
            return responseInvalid(['invoiceNumber and serviceType are required']);
        }

        // ── Pet Shop: tidak pakai payment_totals ────────────────────────────
        if ($serviceType === 'Pet Shop') {
            $trx = DB::table('transactionpetshop as tp')
                ->join('customer as c', 'c.id', '=', 'tp.customerId')
                ->join('location as l', 'l.id', '=', 'tp.locationId')
                ->select(
                    'tp.id as transactionId',
                    'tp.no_nota as invoiceNumber',
                    DB::raw("CONCAT(c.firstName, ' ', COALESCE(c.lastName, '')) as customerName"),
                    'l.locationName',
                    'tp.totalAmount as total',
                    DB::raw("CASE WHEN tp.isPayed = 1 THEN tp.totalAmount ELSE 0 END as paidAmount"),
                    DB::raw("CASE WHEN tp.isPayed = 1 THEN 0 ELSE tp.totalAmount END as remaining"),
                    DB::raw("CASE WHEN tp.isPayed = 1 THEN 'paid' ELSE 'unpaid' END as status")
                )
                ->where('tp.no_nota', $invoiceNumber)
                ->where('tp.isDeleted', 0)
                ->first();

            if (!$trx) return response()->json(['message' => 'Invoice not found'], 404);

            return response()->json([
                'invoice'  => $trx,
                'payments' => [], // Pet Shop tidak ada riwayat cicilan
            ]);
        }

        // ── Layanan dengan payment_totals ───────────────────────────────────
        $cfg = self::SERVICE_MAP[$serviceType] ?? null;
        if (!$cfg || !$cfg['paymentTable']) {
            return response()->json(['message' => 'Service type not supported'], 422);
        }

        $mainTable    = $cfg['mainTable'];
        $paymentTable = $cfg['paymentTable'];

        // Cari transactionId dari nota pertama
        $firstPayment = DB::table($paymentTable)
            ->where('nota_number', $invoiceNumber)
            ->where('isDeleted', 0)
            ->first();

        if (!$firstPayment) {
            // Coba cari berdasarkan transactionId dari nota lain milik transaksi yang sama
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        $transactionId = $firstPayment->transactionId;

        // Data transaksi utama
        $trx = DB::table("{$mainTable} as t")
            ->join('customer as c', 'c.id', '=', 't.customerId')
            ->join('location as l', 'l.id', '=', 't.locationId')
            ->select(
                't.id as transactionId',
                DB::raw("CONCAT(c.firstName, ' ', COALESCE(c.lastName, '')) as customerName"),
                'l.locationName',
                't.locationId'
            )
            ->where('t.id', $transactionId)
            ->first();

        if (!$trx) return response()->json(['message' => 'Transaction not found'], 404);

        // Semua payment records untuk transaksi ini
        $payments = DB::table("{$paymentTable} as pt")
            ->leftJoin('paymentMethodFinances as pm', 'pm.id', '=', 'pt.paymentMethodId')
            ->leftJoin('users as u', 'u.id', '=', 'pt.userId')
            ->select(
                'pt.id',
                'pt.nota_number as notaNumber',
                'pt.amount as total',
                'pt.amountPaid',
                'pt.isPayed',
                'pt.nextPayment',
                DB::raw("COALESCE(pm.paymentMethod, '-') as paymentMethod"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(pt.created_at, '%d/%m/%Y %H:%i') as createdAt")
            )
            ->where('pt.transactionId', $transactionId)
            ->where('pt.isDeleted', 0)
            ->orderBy('pt.id', 'asc')
            ->get();

        $totalBill  = (float) ($payments->first()->total ?? 0);
        $totalPaid  = (float) $payments->sum('amountPaid');
        $remaining  = max(0, $totalBill - $totalPaid);

        $status = 'unpaid';
        if ($remaining <= 0) $status = 'paid';
        elseif ($totalPaid > 0) $status = 'partial';

        return response()->json([
            'invoice' => [
                'transactionId' => $transactionId,
                'invoiceNumber' => $invoiceNumber,
                'serviceType'   => $serviceType,
                'customerName'  => $trx->customerName,
                'locationName'  => $trx->locationName,
                'locationId'    => $trx->locationId,
                'total'         => $totalBill,
                'paidAmount'    => $totalPaid,
                'remaining'     => $remaining,
                'status'        => $status,
            ],
            'payments' => $payments,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // POST finance/sales/add-payment — tambah bayar cicilan
    // ══════════════════════════════════════════════════════════════════════════
    public function addPayment(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'invoiceNumber'   => 'required|string',
            'serviceType'     => 'required|string',
            'amountPaid'      => 'required|numeric|min:1',
            'paymentMethodId' => 'required|integer',
            'nextPayment'     => 'nullable|date',
        ]);
        if ($validate->fails()) return responseInvalid($validate->errors()->all());

        $invoiceNumber   = $request->invoiceNumber;
        $serviceType     = $request->serviceType;
        $amountPaid      = (float) $request->amountPaid;
        $paymentMethodId = (int) $request->paymentMethodId;
        $nextPayment     = $request->nextPayment;
        $userId          = $request->user()->id;

        // ── Pet Shop: hanya mark isPayed = 1 ───────────────────────────────
        if ($serviceType === 'Pet Shop') {
            $shop = DB::table('transactionpetshop')
                ->where('no_nota', $invoiceNumber)
                ->where('isDeleted', 0)
                ->first();

            if (!$shop) return responseInvalid(['Invoice tidak ditemukan.']);
            if ($shop->isPayed) return responseInvalid(['Invoice sudah Lunas.']);

            DB::table('transactionpetshop')
                ->where('id', $shop->id)
                ->update(['isPayed' => 1, 'userUpdateId' => $userId, 'updated_at' => now()]);

            return response()->json(['message' => 'Pembayaran berhasil dicatat. Invoice Pet Shop telah lunas.'], 200);
        }

        // ── Layanan dengan payment_totals ───────────────────────────────────
        $cfg = self::SERVICE_MAP[$serviceType] ?? null;
        if (!$cfg || !$cfg['paymentTable']) {
            return responseInvalid(['Service type tidak didukung.']);
        }

        $mainTable    = $cfg['mainTable'];
        $paymentTable = $cfg['paymentTable'];
        $notaPrefix   = $cfg['notaPrefix'];

        // Cari transaksi dari nota pertama
        $firstPayment = DB::table($paymentTable)
            ->where('nota_number', $invoiceNumber)
            ->where('isDeleted', 0)
            ->first();

        if (!$firstPayment) return responseInvalid(['Invoice tidak ditemukan.']);

        $transactionId = $firstPayment->transactionId;
        $totalBill     = (float) $firstPayment->amount;

        // Total yang sudah terbayar
        $totalPaid = (float) DB::table($paymentTable)
            ->where('transactionId', $transactionId)
            ->where('isDeleted', 0)
            ->sum('amountPaid');

        $remaining = $totalBill - $totalPaid;

        if ($remaining <= 0) {
            return responseInvalid(['Invoice sudah Lunas. Tidak perlu tambah bayar.']);
        }
        if ($amountPaid > $remaining) {
            return responseInvalid(["Jumlah bayar melebihi sisa tagihan (Rp " . number_format($remaining, 0, ',', '.') . ")."]);
        }

        // Ambil locationId dari transaksi
        $trans = DB::table($mainTable)
            ->where('id', $transactionId)
            ->select('locationId')
            ->first();

        if (!$trans) return responseInvalid(['Data transaksi tidak ditemukan.']);

        $locationId = $trans->locationId;

        try {
            DB::beginTransaction();

            // Generate nota number untuk pembayaran ini
            $now   = Carbon::now();
            $tahun = $now->format('Y');
            $bulan = $now->format('m');

            $jumlah = DB::table($paymentTable . ' as pt')
                ->join($mainTable . ' as t', 'pt.transactionId', '=', 't.id')
                ->where('t.locationId', $locationId)
                ->whereYear('pt.created_at', $tahun)
                ->whereMonth('pt.created_at', $bulan)
                ->where('pt.isDeleted', 0)
                ->lockForUpdate()
                ->count();

            $nomorUrut  = str_pad($jumlah + 1, 4, '0', STR_PAD_LEFT);
            $notaNumber = "INV/{$notaPrefix}/{$locationId}/{$tahun}/{$bulan}/{$nomorUrut}";

            $newTotalPaid = $totalPaid + $amountPaid;
            $isFinalPayment = $newTotalPaid >= $totalBill;

            // Insert record pembayaran baru (langsung isPayed=1 karena Finance sudah konfirmasi)
            DB::table($paymentTable)->insert([
                'transactionId'   => $transactionId,
                'paymentMethodId' => $paymentMethodId,
                'amount'          => $totalBill,
                'amountPaid'      => $amountPaid,
                'isPayed'         => 1,
                'nota_number'     => $notaNumber,
                'nextPayment'     => $isFinalPayment ? null : $nextPayment,
                'isDeleted'       => 0,
                'userId'          => $userId,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            // Update status transaksi jika sudah lunas
            if ($isFinalPayment) {
                DB::table($mainTable)
                    ->where('id', $transactionId)
                    ->update(['status' => 'Selesai', 'userUpdateId' => $userId, 'updated_at' => now()]);
            }

            DB::commit();

            $message = $isFinalPayment
                ? "Pembayaran berhasil. Invoice {$invoiceNumber} telah Lunas."
                : "Pembayaran Rp " . number_format($amountPaid, 0, ',', '.') . " berhasil dicatat. Sisa: Rp " . number_format($totalBill - $newTotalPaid, 0, ',', '.') . ".";

            return response()->json(['message' => $message, 'notaNumber' => $notaNumber], 200);

        } catch (\Throwable $th) {
            DB::rollback();
            return responseInvalid([$th->getMessage()]);
        }
    }
}
