<?php

namespace App\Http\Controllers\Finance;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\Finance\PiutangReport;
use Maatwebsite\Excel\Facades\Excel;

class FinancePiutangController extends FinanceSalesController
{
    // Aging bucket labels (same order used on frontend)
    private const BUCKETS = ['current', '1-30', '31-60', '61-90', '>90'];

    // ══════════════════════════════════════════════════════════════════════════
    // Base query: only unpaid/partial, plus optional piutang-specific filters
    // ══════════════════════════════════════════════════════════════════════════
    private function buildPiutangBase(Request $request)
    {
        return $this->buildBaseUnion($request)
            ->whereIn('status', ['unpaid', 'partial']);
    }

    private function applyPiutangFilters($query, Request $request)
    {
        // keyword
        if ($request->search) {
            $kw = $request->search;
            $query->where(function ($q) use ($kw) {
                $q->where('invoiceNumber', 'like', "%{$kw}%")
                  ->orWhere('customerName', 'like', "%{$kw}%")
                  ->orWhere('locationName', 'like', "%{$kw}%");
            });
        }

        // location filter
        if ($request->locationId && is_array($request->locationId) && count($request->locationId)) {
            $query->whereIn('locationId', $request->locationId);
        }

        // service type filter
        if ($request->serviceType) {
            $query->where('serviceType', $request->serviceType);
        }

        // aging bucket filter — repeat CASE logic in WHERE to avoid SELECT alias issue
        if ($request->agingBucket && in_array($request->agingBucket, self::BUCKETS)) {
            switch ($request->agingBucket) {
                case 'current':
                    $query->where(function ($q) {
                        $q->whereNull('dueDate')
                          ->orWhereRaw('dueDate >= CURDATE()');
                    });
                    break;
                case '1-30':
                    $query->whereNotNull('dueDate')
                          ->whereRaw('DATEDIFF(CURDATE(), dueDate) BETWEEN 1 AND 30');
                    break;
                case '31-60':
                    $query->whereNotNull('dueDate')
                          ->whereRaw('DATEDIFF(CURDATE(), dueDate) BETWEEN 31 AND 60');
                    break;
                case '61-90':
                    $query->whereNotNull('dueDate')
                          ->whereRaw('DATEDIFF(CURDATE(), dueDate) BETWEEN 61 AND 90');
                    break;
                case '>90':
                    $query->whereNotNull('dueDate')
                          ->whereRaw('DATEDIFF(CURDATE(), dueDate) > 90');
                    break;
            }
        }

        return $query;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // GET finance/piutang/aging-summary — widget totals per aging bucket
    // ══════════════════════════════════════════════════════════════════════════
    public function agingSummary(Request $request)
    {
        $query = $this->buildPiutangBase($request);
        $query = $this->applyPiutangFilters($query, $request);

        $s = (clone $query)->selectRaw("
            COUNT(*)                                                                              AS totalCount,
            COALESCE(SUM(remaining), 0)                                                          AS totalOutstanding,

            COALESCE(SUM(CASE WHEN (dueDate IS NULL OR dueDate >= CURDATE())
                              THEN remaining ELSE 0 END), 0)                                     AS current_amt,

            COALESCE(SUM(CASE WHEN dueDate IS NOT NULL
                               AND DATEDIFF(CURDATE(), dueDate) BETWEEN 1 AND 30
                              THEN remaining ELSE 0 END), 0)                                     AS days1_30,

            COALESCE(SUM(CASE WHEN dueDate IS NOT NULL
                               AND DATEDIFF(CURDATE(), dueDate) BETWEEN 31 AND 60
                              THEN remaining ELSE 0 END), 0)                                     AS days31_60,

            COALESCE(SUM(CASE WHEN dueDate IS NOT NULL
                               AND DATEDIFF(CURDATE(), dueDate) BETWEEN 61 AND 90
                              THEN remaining ELSE 0 END), 0)                                     AS days61_90,

            COALESCE(SUM(CASE WHEN dueDate IS NOT NULL
                               AND DATEDIFF(CURDATE(), dueDate) > 90
                              THEN remaining ELSE 0 END), 0)                                     AS days90plus,

            SUM(CASE WHEN (dueDate IS NULL OR dueDate >= CURDATE())                    THEN 1 ELSE 0 END) AS cnt_current,
            SUM(CASE WHEN dueDate IS NOT NULL AND DATEDIFF(CURDATE(), dueDate) BETWEEN 1 AND 30   THEN 1 ELSE 0 END) AS cnt_1_30,
            SUM(CASE WHEN dueDate IS NOT NULL AND DATEDIFF(CURDATE(), dueDate) BETWEEN 31 AND 60  THEN 1 ELSE 0 END) AS cnt_31_60,
            SUM(CASE WHEN dueDate IS NOT NULL AND DATEDIFF(CURDATE(), dueDate) BETWEEN 61 AND 90  THEN 1 ELSE 0 END) AS cnt_61_90,
            SUM(CASE WHEN dueDate IS NOT NULL AND DATEDIFF(CURDATE(), dueDate) > 90               THEN 1 ELSE 0 END) AS cnt_90plus
        ")->first();

        return response()->json([
            'totalCount'       => (int)   ($s->totalCount       ?? 0),
            'totalOutstanding' => (float) ($s->totalOutstanding  ?? 0),
            'current'          => ['amount' => (float) ($s->current_amt ?? 0), 'count' => (int) ($s->cnt_current ?? 0)],
            'days1_30'         => ['amount' => (float) ($s->days1_30    ?? 0), 'count' => (int) ($s->cnt_1_30   ?? 0)],
            'days31_60'        => ['amount' => (float) ($s->days31_60   ?? 0), 'count' => (int) ($s->cnt_31_60  ?? 0)],
            'days61_90'        => ['amount' => (float) ($s->days61_90   ?? 0), 'count' => (int) ($s->cnt_61_90  ?? 0)],
            'days90plus'       => ['amount' => (float) ($s->days90plus  ?? 0), 'count' => (int) ($s->cnt_90plus ?? 0)],
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // GET finance/piutang — paginated list with agingBucket + daysOverdue
    // ══════════════════════════════════════════════════════════════════════════
    public function index(Request $request)
    {
        $itemPerPage = (int) ($request->rowPerPage ?? 10);
        $page        = (int) ($request->goToPage ?? 1);

        $allowedColumns = [
            'invoiceNumber', 'customerName', 'locationName', 'serviceType',
            'transactionDate', 'dueDate', 'total', 'remaining', 'daysOverdue',
        ];
        $orderColumn = in_array($request->orderColumn, $allowedColumns)
            ? $request->orderColumn : 'daysOverdue';
        $orderValue = in_array(strtolower($request->orderValue ?? ''), ['asc', 'desc'])
            ? $request->orderValue : 'desc';

        $query = $this->buildPiutangBase($request);
        $query = $this->applyPiutangFilters($query, $request);

        // Add computed aging columns to SELECT
        $query->select([
            '*',
            DB::raw("CASE
                WHEN dueDate IS NULL OR dueDate >= CURDATE()                   THEN 'current'
                WHEN DATEDIFF(CURDATE(), dueDate) BETWEEN 1  AND 30            THEN '1-30'
                WHEN DATEDIFF(CURDATE(), dueDate) BETWEEN 31 AND 60            THEN '31-60'
                WHEN DATEDIFF(CURDATE(), dueDate) BETWEEN 61 AND 90            THEN '61-90'
                ELSE '>90'
            END AS agingBucket"),
            DB::raw("GREATEST(DATEDIFF(CURDATE(), COALESCE(dueDate, transactionDate)), 0) AS daysOverdue"),
        ]);

        $countQuery = clone $query;
        $countData  = $countQuery->count();

        $offset = ($page - 1) * $itemPerPage;
        if ($offset > $countData) $offset = 0;

        $data = $query
            ->orderBy($orderColumn, $orderValue)
            ->offset($offset)
            ->limit($itemPerPage)
            ->get()
            ->transform(fn ($r) => tap($r, function ($r) {
                $r->total      = (float) $r->total;
                $r->paidAmount = (float) $r->paidAmount;
                $r->remaining  = (float) $r->remaining;
                $r->daysOverdue = (int)  $r->daysOverdue;
            }));

        return responseIndex((int) ceil($countData / max($itemPerPage, 1)), $data);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // GET finance/piutang/export
    // ══════════════════════════════════════════════════════════════════════════
    public function exportExcel(Request $request)
    {
        return Excel::download(
            new PiutangReport($request),
            'piutang-aging-' . date('Ymd-His') . '.xlsx'
        );
    }

    // ── For export (no pagination) ──────────────────────────────────────────
    public function allData(Request $request)
    {
        $query = $this->buildPiutangBase($request);
        $query = $this->applyPiutangFilters($query, $request);

        $query->select([
            '*',
            DB::raw("CASE
                WHEN dueDate IS NULL OR dueDate >= CURDATE()                   THEN 'current'
                WHEN DATEDIFF(CURDATE(), dueDate) BETWEEN 1  AND 30            THEN '1-30'
                WHEN DATEDIFF(CURDATE(), dueDate) BETWEEN 31 AND 60            THEN '31-60'
                WHEN DATEDIFF(CURDATE(), dueDate) BETWEEN 61 AND 90            THEN '61-90'
                ELSE '>90'
            END AS agingBucket"),
            DB::raw("GREATEST(DATEDIFF(CURDATE(), COALESCE(dueDate, transactionDate)), 0) AS daysOverdue"),
        ]);

        return $query->orderBy('daysOverdue', 'desc')->get();
    }
}
