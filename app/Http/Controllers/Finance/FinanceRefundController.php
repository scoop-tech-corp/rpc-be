<?php

namespace App\Http\Controllers\Finance;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Exports\Finance\RefundReport;
use Maatwebsite\Excel\Facades\Excel;

class FinanceRefundController extends Controller
{
    // Mapping same as FinanceSalesController
    private const SERVICE_MAP = [
        'Pet Clinic' => ['mainTable' => 'transactionPetClinics',     'paymentTable' => 'transaction_pet_clinic_payment_totals',  'notaPrefix' => 'PC'],
        'Pet Hotel'  => ['mainTable' => 'transaction_pet_hotels',     'paymentTable' => 'transaction_pet_hotel_payment_totals',   'notaPrefix' => 'PH'],
        'Pet Salon'  => ['mainTable' => 'transaction_pet_salons',     'paymentTable' => 'transaction_pet_salon_payment_totals',   'notaPrefix' => 'PSL'],
        'Breeding'   => ['mainTable' => 'transaction_breedings',      'paymentTable' => 'transaction_breeding_payment_totals',    'notaPrefix' => 'BR'],
        'Pet Shop'   => ['mainTable' => 'transactionpetshop',         'paymentTable' => null,                                    'notaPrefix' => 'PS'],
    ];

    // ══════════════════════════════════════════════════════════════════════════
    // GET finance/refund — paginated refund list
    // ══════════════════════════════════════════════════════════════════════════
    public function index(Request $request)
    {
        $itemPerPage = (int) ($request->rowPerPage ?? 10);
        $page        = (int) ($request->goToPage   ?? 1);

        $allowedColumns = [
            'refundNumber', 'invoiceNumber', 'customerName', 'locationName',
            'serviceType', 'paymentMethod', 'amount', 'status', 'createdAt',
        ];
        $orderColumn = in_array($request->orderColumn, $allowedColumns)
            ? $request->orderColumn : 'fr.created_at';
        $orderValue = in_array(strtolower($request->orderValue ?? ''), ['asc', 'desc'])
            ? $request->orderValue : 'desc';

        $query = DB::table('finance_refunds as fr')
            ->join('customer as c',                    'c.id',  '=', 'fr.customerId')
            ->join('location as l',                    'l.id',  '=', 'fr.locationId')
            ->join('users as u',                       'u.id',  '=', 'fr.userId')
            ->leftJoin('paymentMethodFinances as pm',  'pm.id', '=', 'fr.paymentMethodId')
            ->leftJoin('users as ua',                  'ua.id', '=', 'fr.approvedBy')
            ->select(
                'fr.id',
                'fr.refundNumber',
                'fr.serviceType',
                'fr.invoiceNumber',
                'fr.amount',
                'fr.reason',
                'fr.notes',
                'fr.status',
                DB::raw("CONCAT(c.firstName, ' ', COALESCE(c.lastName, '')) AS customerName"),
                'c.memberNo',
                'l.locationName',
                'l.id AS locationId',
                DB::raw("COALESCE(pm.paymentMethod, '-') AS paymentMethod"),
                'u.firstName AS createdBy',
                'ua.firstName AS approvedByName',
                DB::raw("DATE_FORMAT(fr.created_at, '%d/%m/%Y %H:%i') AS createdAt"),
                'fr.created_at AS sortableDate'
            )
            ->where('fr.isDeleted', 0);

        // ── Filters ─────────────────────────────────────────────────────────
        if ($request->search) {
            $kw = $request->search;
            $query->where(function ($q) use ($kw) {
                $q->where('fr.refundNumber',  'like', "%{$kw}%")
                  ->orWhere('fr.invoiceNumber', 'like', "%{$kw}%")
                  ->orWhereRaw("CONCAT(c.firstName, ' ', COALESCE(c.lastName, '')) LIKE ?", ["%{$kw}%"]);
            });
        }
        if ($request->locationId && is_array($request->locationId) && count($request->locationId)) {
            $query->whereIn('fr.locationId', $request->locationId);
        }
        if ($request->serviceType) {
            $query->where('fr.serviceType', $request->serviceType);
        }
        if ($request->filled('status') && $request->status !== '') {
            $query->where('fr.status', (int) $request->status);
        }
        if ($request->startDate && $request->endDate) {
            $query->whereBetween(DB::raw('DATE(fr.created_at)'), [$request->startDate, $request->endDate]);
        }

        $countData = (clone $query)->count();
        $offset    = ($page - 1) * $itemPerPage;
        if ($offset > $countData) $offset = 0;

        $data = $query
            ->orderBy($orderColumn, $orderValue)
            ->offset($offset)
            ->limit($itemPerPage)
            ->get()
            ->transform(fn ($r) => tap($r, fn ($r) => $r->amount = (float) $r->amount));

        return responseIndex((int) ceil($countData / max($itemPerPage, 1)), $data);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // GET finance/refund/invoice-lookup
    // Cari invoice & hitung sisa yang bisa direfund
    // ══════════════════════════════════════════════════════════════════════════
    public function invoiceLookup(Request $request)
    {
        $invoiceNumber = trim($request->invoiceNumber ?? '');
        $serviceType   = trim($request->serviceType   ?? '');

        if (!$invoiceNumber || !$serviceType) {
            return responseInvalid(['invoiceNumber dan serviceType wajib diisi.']);
        }

        $cfg = self::SERVICE_MAP[$serviceType] ?? null;
        if (!$cfg) {
            return responseInvalid(['Service type tidak valid.']);
        }

        // ── Pet Shop ────────────────────────────────────────────────────────
        if ($serviceType === 'Pet Shop') {
            $trx = DB::table('transactionpetshop as tp')
                ->join('customer as c', 'c.id', '=', 'tp.customerId')
                ->join('location as l', 'l.id', '=', 'tp.locationId')
                ->select(
                    'tp.id AS transactionId',
                    'tp.no_nota AS invoiceNumber',
                    DB::raw("CONCAT(c.firstName, ' ', COALESCE(c.lastName, '')) AS customerName"),
                    'c.id AS customerId',
                    'l.locationName',
                    'l.id AS locationId',
                    'tp.totalAmount AS paidAmount',
                )
                ->where('tp.no_nota', $invoiceNumber)
                ->where('tp.isDeleted', 0)
                ->first();

            if (!$trx) return response()->json(['message' => 'Invoice tidak ditemukan.'], 404);

            $alreadyRefunded = (float) DB::table('finance_refunds')
                ->where('invoiceNumber', $invoiceNumber)
                ->where('serviceType', 'Pet Shop')
                ->where('isDeleted', 0)
                ->sum('amount');

            return response()->json([
                'transactionId' => $trx->transactionId,
                'invoiceNumber' => $trx->invoiceNumber,
                'customerName'  => $trx->customerName,
                'customerId'    => $trx->customerId,
                'locationName'  => $trx->locationName,
                'locationId'    => $trx->locationId,
                'paidAmount'    => (float) $trx->paidAmount,
                'alreadyRefunded' => $alreadyRefunded,
                'maxRefund'     => max(0, (float) $trx->paidAmount - $alreadyRefunded),
            ]);
        }

        // ── Services with payment_totals ────────────────────────────────────
        $mainTable    = $cfg['mainTable'];
        $paymentTable = $cfg['paymentTable'];

        $firstPayment = DB::table($paymentTable)
            ->where('nota_number', $invoiceNumber)
            ->where('isDeleted', 0)
            ->first();

        if (!$firstPayment) {
            return response()->json(['message' => 'Invoice tidak ditemukan.'], 404);
        }

        $transactionId = $firstPayment->transactionId;

        $trx = DB::table("{$mainTable} as t")
            ->join('customer as c', 'c.id', '=', 't.customerId')
            ->join('location as l', 'l.id', '=', 't.locationId')
            ->select(
                't.id AS transactionId',
                DB::raw("CONCAT(c.firstName, ' ', COALESCE(c.lastName, '')) AS customerName"),
                'c.id AS customerId',
                'l.locationName',
                'l.id AS locationId',
            )
            ->where('t.id', $transactionId)
            ->first();

        if (!$trx) {
            return response()->json(['message' => 'Data transaksi tidak ditemukan.'], 404);
        }

        $paidAmount = (float) DB::table($paymentTable)
            ->where('transactionId', $transactionId)
            ->where('isDeleted', 0)
            ->sum('amountPaid');

        $alreadyRefunded = (float) DB::table('finance_refunds')
            ->where('invoiceNumber', $invoiceNumber)
            ->where('serviceType', $serviceType)
            ->where('isDeleted', 0)
            ->sum('amount');

        return response()->json([
            'transactionId'   => $trx->transactionId,
            'invoiceNumber'   => $invoiceNumber,
            'customerName'    => $trx->customerName,
            'customerId'      => $trx->customerId,
            'locationName'    => $trx->locationName,
            'locationId'      => $trx->locationId,
            'paidAmount'      => $paidAmount,
            'alreadyRefunded' => $alreadyRefunded,
            'maxRefund'       => max(0, $paidAmount - $alreadyRefunded),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // GET finance/refund/summary — total refund stats
    // ══════════════════════════════════════════════════════════════════════════
    public function summary(Request $request)
    {
        $query = DB::table('finance_refunds as fr')
            ->where('fr.isDeleted', 0);

        if ($request->startDate && $request->endDate) {
            $query->whereBetween(DB::raw('DATE(fr.created_at)'), [$request->startDate, $request->endDate]);
        }
        if ($request->locationId && is_array($request->locationId) && count($request->locationId)) {
            $query->whereIn('fr.locationId', $request->locationId);
        }

        $s = (clone $query)->selectRaw("
            COUNT(*) AS totalRefunds,
            COALESCE(SUM(amount), 0) AS totalAmount,
            COALESCE(SUM(CASE WHEN status = 1 THEN amount ELSE 0 END), 0) AS approvedAmount,
            COALESCE(SUM(CASE WHEN status = 0 THEN amount ELSE 0 END), 0) AS pendingAmount,
            SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS countApproved,
            SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS countPending
        ")->first();

        return response()->json([
            'totalRefunds'   => (int)   ($s->totalRefunds   ?? 0),
            'totalAmount'    => (float) ($s->totalAmount    ?? 0),
            'approvedAmount' => (float) ($s->approvedAmount ?? 0),
            'pendingAmount'  => (float) ($s->pendingAmount  ?? 0),
            'countApproved'  => (int)   ($s->countApproved  ?? 0),
            'countPending'   => (int)   ($s->countPending   ?? 0),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // POST finance/refund — catat refund baru
    // ══════════════════════════════════════════════════════════════════════════
    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'invoiceNumber'   => 'required|string',
            'serviceType'     => 'required|string',
            'transactionId'   => 'nullable|integer',
            'customerId'      => 'required|integer',
            'locationId'      => 'required|integer',
            'paymentMethodId' => 'required|integer',
            'amount'          => 'required|numeric|min:1',
            'reason'          => 'required|string|max:500',
            'notes'           => 'nullable|string|max:1000',
        ]);
        if ($validate->fails()) return responseInvalid($validate->errors()->all());

        $cfg = self::SERVICE_MAP[$request->serviceType] ?? null;
        if (!$cfg) return responseInvalid(['Service type tidak valid.']);

        $amount    = (float) $request->amount;
        $locationId = (int)   $request->locationId;
        $userId    = $request->user()->id;

        try {
            DB::beginTransaction();

            // Generate refund number
            $now       = Carbon::now();
            $tahun     = $now->format('Y');
            $bulan     = $now->format('m');
            $prefix    = $cfg['notaPrefix'];

            $jumlah = DB::table('finance_refunds')
                ->where('locationId', $locationId)
                ->whereYear('created_at', $tahun)
                ->whereMonth('created_at', $bulan)
                ->where('isDeleted', 0)
                ->lockForUpdate()
                ->count();

            $nomorUrut    = str_pad($jumlah + 1, 4, '0', STR_PAD_LEFT);
            $refundNumber = "REF/{$prefix}/{$locationId}/{$tahun}/{$bulan}/{$nomorUrut}";

            DB::table('finance_refunds')->insert([
                'refundNumber'    => $refundNumber,
                'serviceType'     => $request->serviceType,
                'invoiceNumber'   => $request->invoiceNumber,
                'transactionId'   => $request->transactionId ?: null,
                'customerId'      => $request->customerId,
                'locationId'      => $locationId,
                'paymentMethodId' => $request->paymentMethodId,
                'amount'          => $amount,
                'reason'          => $request->reason,
                'notes'           => $request->notes ?: null,
                'status'          => 1,   // langsung approved oleh Finance
                'userId'          => $userId,
                'approvedBy'      => $userId,
                'isDeleted'       => 0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            DB::commit();

            sendNotificationToStaffAtLocation($locationId, [1, 13], 'refund', "Refund {$refundNumber} sebesar Rp " . number_format($amount, 0, ',', '.') . " berhasil dicatat.", 'warning');

            return response()->json([
                'message'      => "Refund {$refundNumber} berhasil dicatat.",
                'refundNumber' => $refundNumber,
            ], 201);

        } catch (\Throwable $th) {
            DB::rollback();
            return responseInvalid([$th->getMessage()]);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // DELETE finance/refund/{id} — soft delete
    // ══════════════════════════════════════════════════════════════════════════
    public function destroy(Request $request, $id)
    {
        $refund = DB::table('finance_refunds')->where('id', $id)->where('isDeleted', 0)->first();
        if (!$refund) return responseInvalid(['Data refund tidak ditemukan.']);

        DB::table('finance_refunds')->where('id', $id)->update([
            'isDeleted'  => 1,
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Refund berhasil dihapus.']);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // GET finance/refund/payment-methods
    // ══════════════════════════════════════════════════════════════════════════
    public function paymentMethods()
    {
        return response()->json(
            DB::table('paymentMethodFinances')
                ->where('isDeleted', 0)
                ->select('id', 'paymentMethod as name')
                ->orderBy('paymentMethod')
                ->get()
        );
    }

    // ══════════════════════════════════════════════════════════════════════════
    // GET finance/refund/export
    // ══════════════════════════════════════════════════════════════════════════
    public function exportExcel(Request $request)
    {
        return Excel::download(
            new RefundReport($request),
            'refund-' . date('Ymd-His') . '.xlsx'
        );
    }

    // ── For export (no pagination) ──────────────────────────────────────────
    public function allData(Request $request)
    {
        $query = DB::table('finance_refunds as fr')
            ->join('customer as c',                   'c.id',  '=', 'fr.customerId')
            ->join('location as l',                   'l.id',  '=', 'fr.locationId')
            ->join('users as u',                      'u.id',  '=', 'fr.userId')
            ->leftJoin('paymentMethodFinances as pm', 'pm.id', '=', 'fr.paymentMethodId')
            ->select(
                'fr.refundNumber', 'fr.invoiceNumber', 'fr.serviceType',
                DB::raw("CONCAT(c.firstName,' ',COALESCE(c.lastName,'')) AS customerName"),
                'c.memberNo', 'l.locationName',
                DB::raw("COALESCE(pm.paymentMethod, '-') AS paymentMethod"),
                'fr.amount', 'fr.reason', 'fr.notes', 'fr.status',
                'u.firstName AS createdBy',
                DB::raw("DATE_FORMAT(fr.created_at,'%d/%m/%Y %H:%i') AS createdAt")
            )
            ->where('fr.isDeleted', 0);

        if ($request->search) {
            $kw = $request->search;
            $query->where(function ($q) use ($kw) {
                $q->where('fr.refundNumber', 'like', "%{$kw}%")
                  ->orWhere('fr.invoiceNumber', 'like', "%{$kw}%")
                  ->orWhereRaw("CONCAT(c.firstName,' ',COALESCE(c.lastName,'')) LIKE ?", ["%{$kw}%"]);
            });
        }
        if ($request->locationId && is_array($request->locationId) && count($request->locationId)) {
            $query->whereIn('fr.locationId', $request->locationId);
        }
        if ($request->serviceType) {
            $query->where('fr.serviceType', $request->serviceType);
        }
        if ($request->startDate && $request->endDate) {
            $query->whereBetween(DB::raw('DATE(fr.created_at)'), [$request->startDate, $request->endDate]);
        }

        return $query->orderBy('fr.created_at', 'desc')->get();
    }
}
