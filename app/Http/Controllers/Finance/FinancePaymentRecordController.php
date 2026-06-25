<?php

namespace App\Http\Controllers\Finance;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Exports\Finance\PaymentRecordReport;
use Maatwebsite\Excel\Facades\Excel;

class FinancePaymentRecordController extends Controller
{
    // ══════════════════════════════════════════════════════════════════════════
    // UNION subquery — satu baris per record pembayaran (cicilan)
    // ══════════════════════════════════════════════════════════════════════════
    private function buildBaseUnion()
    {
        // 1. Pet Clinic ─────────────────────────────────────────────────────
        $clinic = DB::table('transaction_pet_clinic_payment_totals as pt')
            ->join('transactionPetClinics as t', function ($j) {
                $j->on('t.id', '=', 'pt.transactionId')->where('t.isDeleted', 0);
            })
            ->join('customer as c', 'c.id', '=', 't.customerId')
            ->join('location as l', 'l.id', '=', 't.locationId')
            ->leftJoin('paymentMethodFinances as pm', 'pm.id', '=', 'pt.paymentMethodId')
            ->leftJoin('users as u', 'u.id', '=', 'pt.userId')
            ->select(
                'pt.nota_number as notaNumber',
                DB::raw("(SELECT MIN(pt2.nota_number)
                          FROM transaction_pet_clinic_payment_totals pt2
                          WHERE pt2.transactionId = pt.transactionId
                            AND pt2.isDeleted = 0) as invoiceNumber"),
                DB::raw("'Pet Clinic' as serviceType"),
                DB::raw("CONCAT(c.firstName, ' ', COALESCE(c.lastName, '')) as customerName"),
                'c.memberNo',
                'l.locationName',
                'l.id as locationId',
                DB::raw("COALESCE(pm.paymentMethod, '-') as paymentMethod"),
                'pt.paymentMethodId',
                'pt.amountPaid',
                'pt.isPayed',
                'pt.nextPayment',
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(pt.created_at, '%d/%m/%Y %H:%i') as createdAt"),
                'pt.created_at as sortableDate'
            )
            ->where('pt.isDeleted', 0)
            ->whereNotNull('pt.nota_number');

        // 2. Pet Hotel ──────────────────────────────────────────────────────
        $hotel = DB::table('transaction_pet_hotel_payment_totals as pt')
            ->join('transaction_pet_hotels as t', function ($j) {
                $j->on('t.id', '=', 'pt.transactionId')->where('t.isDeleted', 0);
            })
            ->join('customer as c', 'c.id', '=', 't.customerId')
            ->join('location as l', 'l.id', '=', 't.locationId')
            ->leftJoin('paymentMethodFinances as pm', 'pm.id', '=', 'pt.paymentMethodId')
            ->leftJoin('users as u', 'u.id', '=', 'pt.userId')
            ->select(
                'pt.nota_number as notaNumber',
                DB::raw("(SELECT MIN(pt2.nota_number)
                          FROM transaction_pet_hotel_payment_totals pt2
                          WHERE pt2.transactionId = pt.transactionId
                            AND pt2.isDeleted = 0) as invoiceNumber"),
                DB::raw("'Pet Hotel' as serviceType"),
                DB::raw("CONCAT(c.firstName, ' ', COALESCE(c.lastName, '')) as customerName"),
                'c.memberNo',
                'l.locationName',
                'l.id as locationId',
                DB::raw("COALESCE(pm.paymentMethod, '-') as paymentMethod"),
                'pt.paymentMethodId',
                'pt.amountPaid',
                'pt.isPayed',
                'pt.nextPayment',
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(pt.created_at, '%d/%m/%Y %H:%i') as createdAt"),
                'pt.created_at as sortableDate'
            )
            ->where('pt.isDeleted', 0)
            ->whereNotNull('pt.nota_number');

        // 3. Pet Salon ──────────────────────────────────────────────────────
        $salon = DB::table('transaction_pet_salon_payment_totals as pt')
            ->join('transaction_pet_salons as t', function ($j) {
                $j->on('t.id', '=', 'pt.transactionId')->where('t.isDeleted', 0);
            })
            ->join('customer as c', 'c.id', '=', 't.customerId')
            ->join('location as l', 'l.id', '=', 't.locationId')
            ->leftJoin('paymentMethodFinances as pm', 'pm.id', '=', 'pt.paymentMethodId')
            ->leftJoin('users as u', 'u.id', '=', 'pt.userId')
            ->select(
                'pt.nota_number as notaNumber',
                DB::raw("(SELECT MIN(pt2.nota_number)
                          FROM transaction_pet_salon_payment_totals pt2
                          WHERE pt2.transactionId = pt.transactionId
                            AND pt2.isDeleted = 0) as invoiceNumber"),
                DB::raw("'Pet Salon' as serviceType"),
                DB::raw("CONCAT(c.firstName, ' ', COALESCE(c.lastName, '')) as customerName"),
                'c.memberNo',
                'l.locationName',
                'l.id as locationId',
                DB::raw("COALESCE(pm.paymentMethod, '-') as paymentMethod"),
                'pt.paymentMethodId',
                'pt.amountPaid',
                'pt.isPayed',
                'pt.nextPayment',
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(pt.created_at, '%d/%m/%Y %H:%i') as createdAt"),
                'pt.created_at as sortableDate'
            )
            ->where('pt.isDeleted', 0)
            ->whereNotNull('pt.nota_number');

        // 4. Breeding ───────────────────────────────────────────────────────
        $breeding = DB::table('transaction_breeding_payment_totals as pt')
            ->join('transaction_breedings as t', function ($j) {
                $j->on('t.id', '=', 'pt.transactionId')->where('t.isDeleted', 0);
            })
            ->join('customer as c', 'c.id', '=', 't.customerId')
            ->join('location as l', 'l.id', '=', 't.locationId')
            ->leftJoin('paymentMethodFinances as pm', 'pm.id', '=', 'pt.paymentMethodId')
            ->leftJoin('users as u', 'u.id', '=', 'pt.userId')
            ->select(
                'pt.nota_number as notaNumber',
                DB::raw("(SELECT MIN(pt2.nota_number)
                          FROM transaction_breeding_payment_totals pt2
                          WHERE pt2.transactionId = pt.transactionId
                            AND pt2.isDeleted = 0) as invoiceNumber"),
                DB::raw("'Breeding' as serviceType"),
                DB::raw("CONCAT(c.firstName, ' ', COALESCE(c.lastName, '')) as customerName"),
                'c.memberNo',
                'l.locationName',
                'l.id as locationId',
                DB::raw("COALESCE(pm.paymentMethod, '-') as paymentMethod"),
                'pt.paymentMethodId',
                'pt.amountPaid',
                'pt.isPayed',
                'pt.nextPayment',
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(pt.created_at, '%d/%m/%Y %H:%i') as createdAt"),
                'pt.created_at as sortableDate'
            )
            ->where('pt.isDeleted', 0)
            ->whereNotNull('pt.nota_number');

        // 5. Pet Shop ───────────────────────────────────────────────────────
        $shop = DB::table('transactionpetshop as tp')
            ->join('customer as c', 'c.id', '=', 'tp.customerId')
            ->join('location as l', 'l.id', '=', 'tp.locationId')
            ->leftJoin('users as u', 'u.id', '=', 'tp.userId')
            ->select(
                'tp.no_nota as notaNumber',
                'tp.no_nota as invoiceNumber',
                DB::raw("'Pet Shop' as serviceType"),
                DB::raw("CONCAT(c.firstName, ' ', COALESCE(c.lastName, '')) as customerName"),
                'c.memberNo',
                'l.locationName',
                'l.id as locationId',
                DB::raw("'-' as paymentMethod"),
                DB::raw("NULL as paymentMethodId"),
                'tp.totalAmount as amountPaid',
                'tp.isPayed',
                DB::raw("NULL as nextPayment"),
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

        return DB::table(DB::raw("({$union->toSql()}) as pr"))
            ->mergeBindings($union);
    }

    private function applyFilters($query, Request $request)
    {
        if ($request->search) {
            $kw = $request->search;
            $query->where(function ($q) use ($kw) {
                $q->where('notaNumber',   'like', "%{$kw}%")
                  ->orWhere('invoiceNumber', 'like', "%{$kw}%")
                  ->orWhere('customerName',  'like', "%{$kw}%");
            });
        }

        if ($request->locationId && is_array($request->locationId) && count($request->locationId)) {
            $query->whereIn('locationId', $request->locationId);
        }

        if ($request->paymentMethodId) {
            $query->where('paymentMethodId', (int) $request->paymentMethodId);
        }

        if ($request->filled('isPayed') && $request->isPayed !== '') {
            $query->where('isPayed', (int) $request->isPayed);
        }

        if ($request->serviceType) {
            $query->where('serviceType', $request->serviceType);
        }

        if ($request->startDate && $request->endDate) {
            $query->whereBetween(
                DB::raw('DATE(sortableDate)'),
                [$request->startDate, $request->endDate]
            );
        }

        return $query;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // GET finance/payment-records — list dengan pagination
    // ══════════════════════════════════════════════════════════════════════════
    public function index(Request $request)
    {
        $itemPerPage = (int) ($request->rowPerPage ?? 10);
        $page        = (int) ($request->goToPage ?? 1);

        $allowedColumns = [
            'notaNumber', 'invoiceNumber', 'customerName', 'locationName',
            'serviceType', 'paymentMethod', 'amountPaid', 'isPayed',
            'nextPayment', 'createdBy', 'createdAt',
        ];

        $orderColumn = in_array($request->orderColumn, $allowedColumns)
            ? $request->orderColumn : 'sortableDate';
        $orderValue = in_array(strtolower($request->orderValue ?? ''), ['asc', 'desc'])
            ? $request->orderValue : 'desc';

        $query = $this->buildBaseUnion();
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
            ->transform(fn ($r) => tap($r, function ($r) {
                $r->amountPaid = (float) $r->amountPaid;
                $r->isPayed    = (bool)  $r->isPayed;
            }));

        return responseIndex((int) ceil($countData / max($itemPerPage, 1)), $data);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // GET finance/payment-records/export
    // ══════════════════════════════════════════════════════════════════════════
    public function exportExcel(Request $request)
    {
        return Excel::download(
            new PaymentRecordReport($request),
            'payment-record-' . date('Ymd-His') . '.xlsx'
        );
    }

    // ══════════════════════════════════════════════════════════════════════════
    // GET finance/payment-records/payment-methods
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
    // Internal: semua data tanpa pagination (for export)
    // ══════════════════════════════════════════════════════════════════════════
    public function allData(Request $request)
    {
        $query = $this->buildBaseUnion();
        $query = $this->applyFilters($query, $request);
        return $query->orderBy('sortableDate', 'desc')->get();
    }
}
