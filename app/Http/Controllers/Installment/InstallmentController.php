<?php

namespace App\Http\Controllers\Installment;

use DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Installment\InstallmentPlan;
use App\Models\Installment\InstallmentSchedule;
use App\Models\Installment\InstallmentPayment;

class InstallmentController extends Controller
{
    // ── Mapping tipe transaksi → tabel utama ──────────────────────────────────
    private const TRANSACTION_TABLES = [
        'Pet Clinic' => 'transactionPetClinics',
        'Pet Hotel'  => 'transaction_pet_hotels',
        'Pet Salon'  => 'transaction_pet_salons',
        'Breeding'   => 'transaction_breedings',
        'Pet Shop'   => 'transactionpetshop',
    ];

    // ── Index — daftar semua plan ─────────────────────────────────────────────

    public function index(Request $request)
    {
        try {
            // Auto-mark overdue sebelum ambil data
            $this->markOverdueSchedules();

            $query = DB::table('transaction_installment_plans as p')
                ->leftJoin('customer as c',  'c.id', '=', 'p.customerId')
                ->leftJoin('location as l',  'l.id', '=', 'p.locationId')
                ->select(
                    'p.id',
                    'p.transactionType',
                    'p.transactionId',
                    DB::raw("CONCAT(COALESCE(c.firstName,''), ' ', COALESCE(c.lastName,'')) as customerName"),
                    'l.locationName',
                    'p.totalAmount',
                    'p.downPayment',
                    'p.outstandingAmount',
                    'p.tenor',
                    'p.intervalType',
                    'p.intervalValue',
                    'p.startDate',
                    'p.lateFeeType',
                    'p.lateFeeValue',
                    'p.lateFeeGracePeriod',
                    'p.status',
                    'p.created_at'
                )
                ->where('p.isDeleted', 0);

            if ($request->filled('keyword')) {
                $kw = $request->keyword;
                $query->where(function ($q) use ($kw) {
                    $q->where(DB::raw("CONCAT(COALESCE(c.firstName,''), ' ', COALESCE(c.lastName,''))"), 'like', "%{$kw}%")
                      ->orWhere('p.transactionType', 'like', "%{$kw}%");
                });
            }
            if ($request->filled('locationId'))      $query->where('p.locationId', $request->locationId);
            if ($request->filled('status'))           $query->where('p.status', $request->status);
            if ($request->filled('transactionType'))  $query->where('p.transactionType', $request->transactionType);

            $query->orderBy('p.created_at', 'desc');
            $result = paginateData($query, $request);

            return responseIndex((int) $result['totalPagination'], $result['data']);
        } catch (\Exception $e) {
            return responseInvalid([$e->getMessage()]);
        }
    }

    // ── Detail — plan + schedules + payments ──────────────────────────────────

    public function detail(Request $request)
    {
        $request->validate(['planId' => 'required|integer']);
        try {
            $plan = DB::table('transaction_installment_plans as p')
                ->leftJoin('customer as c', 'c.id', '=', 'p.customerId')
                ->leftJoin('location as l',  'l.id', '=', 'p.locationId')
                ->select(
                    'p.*',
                    DB::raw("CONCAT(COALESCE(c.firstName,''), ' ', COALESCE(c.lastName,'')) as customerName"),
                    'l.locationName'
                )
                ->where('p.id', $request->planId)
                ->where('p.isDeleted', 0)
                ->first();

            if (!$plan) return responseInvalid(['Plan tidak ditemukan.']);

            $schedules = DB::table('transaction_installment_schedules')
                ->where('planId', $request->planId)
                ->orderBy('installmentNo')
                ->get();

            // Ambil payments per schedule
            foreach ($schedules as $s) {
                $s->payments = DB::table('transaction_installment_payments as ip')
                    ->leftJoin('paymentmethod as pm', 'pm.id', '=', 'ip.paymentMethodId')
                    ->leftJoin('users as u', 'u.id', '=', 'ip.confirmedBy')
                    ->select(
                        'ip.*',
                        'pm.name as paymentMethodName',
                        DB::raw("CONCAT(COALESCE(u.firstName,''), ' ', COALESCE(u.lastName,'')) as confirmedByName")
                    )
                    ->where('ip.scheduleId', $s->id)
                    ->where('ip.isDeleted', 0)
                    ->orderBy('ip.paymentDate')
                    ->get();
            }

            return response()->json(['data' => $plan, 'schedules' => $schedules]);
        } catch (\Exception $e) {
            return responseInvalid([$e->getMessage()]);
        }
    }

    // ── Summary — ringkasan piutang ───────────────────────────────────────────

    public function summary(Request $request)
    {
        try {
            $base = DB::table('transaction_installment_plans')->where('isDeleted', 0);
            if ($request->filled('locationId')) $base->where('locationId', $request->locationId);

            $totalOutstanding = (clone $base)->where('status', 'active')->sum('outstandingAmount');
            $totalOverdue     = DB::table('transaction_installment_schedules as s')
                ->join('transaction_installment_plans as p', 'p.id', '=', 's.planId')
                ->where('p.isDeleted', 0)
                ->where('s.status', 'overdue')
                ->when($request->filled('locationId'), fn($q) => $q->where('p.locationId', $request->locationId))
                ->sum(DB::raw('s.scheduledAmount - s.paidAmount'));

            $countActive    = (clone $base)->where('status', 'active')->count();
            $countCompleted = (clone $base)->where('status', 'completed')->count();

            return response()->json([
                'totalOutstanding' => number_format((float) $totalOutstanding, 0, ',', '.'),
                'totalOverdue'     => number_format((float) $totalOverdue, 0, ',', '.'),
                'countActive'      => $countActive,
                'countCompleted'   => $countCompleted,
            ]);
        } catch (\Exception $e) {
            return responseInvalid([$e->getMessage()]);
        }
    }

    // ── Create — buat plan + auto-generate schedules ──────────────────────────

    public function create(Request $request)
    {
        $request->validate([
            'transactionType'    => 'required|in:Pet Clinic,Pet Hotel,Pet Salon,Breeding,Pet Shop',
            'transactionId'      => 'required|integer',
            'totalAmount'        => 'required|numeric|min:1',
            'downPayment'        => 'nullable|numeric|min:0',
            'tenor'              => 'required|integer|min:1|max:120',
            'intervalType'       => 'required|in:daily,weekly,monthly',
            'intervalValue'      => 'required|integer|min:1',
            'startDate'          => 'required|date',
            'lateFeeType'        => 'nullable|in:fixed,percent',
            'lateFeeValue'       => 'nullable|numeric|min:0',
            'lateFeeGracePeriod' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $totalAmount  = (float) $request->totalAmount;
            $downPayment  = (float) ($request->downPayment ?? 0);
            $outstanding  = $totalAmount - $downPayment;

            if ($outstanding <= 0) {
                return responseInvalid(['Down payment melebihi total tagihan.']);
            }

            // Ambil customerId & locationId dari transaksi asal
            $table    = self::TRANSACTION_TABLES[$request->transactionType];
            $trx      = DB::table($table)->find($request->transactionId);
            if (!$trx) return responseInvalid(['Transaksi tidak ditemukan.']);

            $plan = InstallmentPlan::create([
                'transactionType'    => $request->transactionType,
                'transactionId'      => $request->transactionId,
                'customerId'         => $trx->customerId,
                'locationId'         => $trx->locationId ?? null,
                'totalAmount'        => $totalAmount,
                'downPayment'        => $downPayment,
                'outstandingAmount'  => $outstanding,
                'tenor'              => $request->tenor,
                'intervalType'       => $request->intervalType,
                'intervalValue'      => (int) $request->intervalValue,
                'startDate'          => $request->startDate,
                'lateFeeType'        => $request->lateFeeType ?? null,
                'lateFeeValue'       => (float) ($request->lateFeeValue ?? 0),
                'lateFeeGracePeriod' => (int) ($request->lateFeeGracePeriod ?? 0),
                'status'             => 'active',
                'notes'              => $request->notes ?? null,
                'isDeleted'          => 0,
                'userId'             => $request->user()->id,
            ]);

            // Auto-generate schedules
            $this->generateSchedules($plan, $outstanding);

            DB::commit();
            if ($plan->locationId) {
                sendNotificationToStaffAtLocation($plan->locationId, [13, 1], 'installment', "Cicilan baru dibuat untuk {$request->transactionType} — total Rp " . number_format($plan->totalAmount, 0, ',', '.') . " ({$plan->tenor}x angsuran).", 'info');
            }
            return responseCreate();
        } catch (\Exception $e) {
            DB::rollBack();
            return responseInvalid([$e->getMessage()]);
        }
    }

    // ── Record Payment — catat pembayaran angsuran ────────────────────────────

    public function recordPayment(Request $request)
    {
        $request->validate([
            'scheduleId'      => 'required|integer',
            'paymentDate'     => 'required|date',
            'amount'          => 'required|numeric|min:0.01',
            'lateFee'         => 'nullable|numeric|min:0',
            'paymentMethodId' => 'nullable|integer',
            'notes'           => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $schedule = InstallmentSchedule::find($request->scheduleId);
            if (!$schedule) return responseInvalid(['Schedule tidak ditemukan.']);

            $plan = InstallmentPlan::find($schedule->planId);
            if (!$plan || $plan->status === 'cancelled') {
                return responseInvalid(['Plan cicilan sudah dibatalkan.']);
            }

            $amount  = (float) $request->amount;
            $lateFee = (float) ($request->lateFee ?? 0);

            // Hitung sisa yang harus dibayar schedule ini
            $remaining = $schedule->scheduledAmount - $schedule->paidAmount;
            if ($amount > $remaining) {
                return responseInvalid(['Pembayaran melebihi sisa angsuran (Rp ' . number_format($remaining, 0, ',', '.') . ').']);
            }

            // Hitung denda otomatis jika tidak dikirim dari frontend
            if ($lateFee === 0.0 && $plan->lateFeeType && $plan->lateFeeValue > 0) {
                $lateFee = $this->calcLateFee($plan, $schedule, $request->paymentDate);
            }

            // Simpan pembayaran
            InstallmentPayment::create([
                'planId'          => $plan->id,
                'scheduleId'      => $schedule->id,
                'paymentDate'     => $request->paymentDate,
                'amount'          => $amount,
                'lateFee'         => $lateFee,
                'paymentMethodId' => $request->paymentMethodId ?? null,
                'notes'           => $request->notes ?? null,
                'confirmedBy'     => $request->user()->id,
                'confirmedAt'     => Carbon::now(),
                'isDeleted'       => 0,
                'userId'          => $request->user()->id,
            ]);

            // Update schedule
            $newPaid    = $schedule->paidAmount + $amount;
            $newLatePaid = $schedule->lateFeesPaid + $lateFee;

            if ($newPaid >= $schedule->scheduledAmount) {
                $scheduleStatus = 'paid';
            } else {
                $scheduleStatus = 'partial';
            }

            $schedule->update([
                'paidAmount'    => $newPaid,
                'lateFeesPaid'  => $newLatePaid,
                'lateFeeCharged'=> max($schedule->lateFeeCharged, $lateFee),
                'status'        => $scheduleStatus,
            ]);

            // Update outstanding di plan
            $newOutstanding = max(0, $plan->outstandingAmount - $amount);
            $planStatus     = ($newOutstanding <= 0) ? 'completed' : 'active';

            $plan->update([
                'outstandingAmount' => $newOutstanding,
                'status'            => $planStatus,
            ]);

            DB::commit();
            if ($planStatus === 'completed' && $plan->locationId) {
                sendNotificationToStaffAtLocation($plan->locationId, [13], 'installment', "Cicilan {$plan->transactionType} telah lunas — pembayaran selesai.", 'success');
            }
            return responseCreate();
        } catch (\Exception $e) {
            DB::rollBack();
            return responseInvalid([$e->getMessage()]);
        }
    }

    // ── Calculate Late Fee (preview) ──────────────────────────────────────────

    public function previewLateFee(Request $request)
    {
        $request->validate([
            'scheduleId'  => 'required|integer',
            'paymentDate' => 'required|date',
        ]);

        try {
            $schedule = InstallmentSchedule::find($request->scheduleId);
            if (!$schedule) return responseInvalid(['Schedule tidak ditemukan.']);
            $plan = InstallmentPlan::find($schedule->planId);

            $lateFee  = $this->calcLateFee($plan, $schedule, $request->paymentDate);
            $daysLate = max(0, Carbon::parse($request->paymentDate)->diffInDays(Carbon::parse($schedule->dueDate), false) * -1);
            $daysLate = max(0, $daysLate - ($plan->lateFeeGracePeriod ?? 0));

            return response()->json([
                'daysLate' => $daysLate,
                'lateFee'  => $lateFee,
                'lateFeeFormatted' => 'Rp ' . number_format($lateFee, 0, ',', '.'),
            ]);
        } catch (\Exception $e) {
            return responseInvalid([$e->getMessage()]);
        }
    }

    // ── Cancel Plan ───────────────────────────────────────────────────────────

    public function cancel(Request $request)
    {
        $request->validate(['planId' => 'required|integer']);
        DB::beginTransaction();
        try {
            $plan = InstallmentPlan::find($request->planId);
            if (!$plan) return responseInvalid(['Plan tidak ditemukan.']);

            $plan->update([
                'status'       => 'cancelled',
                'isDeleted'    => 1,
                'deletedBy'    => $request->user()->id,
                'deletedAt'    => Carbon::now(),
                'userUpdateId' => $request->user()->id,
            ]);

            DB::commit();
            return responseUpdate();
        } catch (\Exception $e) {
            DB::rollBack();
            return responseInvalid([$e->getMessage()]);
        }
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Generate schedule rows dari plan yang baru dibuat.
     * Sisa angsuran (karena pembagian tidak bulat) ditambahkan ke angsuran terakhir.
     */
    private function generateSchedules(InstallmentPlan $plan, float $outstanding): void
    {
        $perInstallment = floor(($outstanding / $plan->tenor) * 100) / 100; // floor 2 desimal
        $lastAmount     = round($outstanding - ($perInstallment * ($plan->tenor - 1)), 2);

        $current = Carbon::parse($plan->startDate);

        for ($i = 1; $i <= $plan->tenor; $i++) {
            $amount = ($i === $plan->tenor) ? $lastAmount : $perInstallment;

            InstallmentSchedule::create([
                'planId'          => $plan->id,
                'installmentNo'   => $i,
                'dueDate'         => $current->format('Y-m-d'),
                'scheduledAmount' => $amount,
                'paidAmount'      => 0,
                'lateFeeCharged'  => 0,
                'lateFeesPaid'    => 0,
                'status'          => 'unpaid',
            ]);

            // Geser ke tanggal berikutnya
            match ($plan->intervalType) {
                'daily'   => $current->addDays($plan->intervalValue),
                'weekly'  => $current->addWeeks($plan->intervalValue),
                'monthly' => $current->addMonths($plan->intervalValue),
            };
        }
    }

    /**
     * Hitung denda keterlambatan.
     * - fixed  : lateFeeValue Rp per hari terlambat
     * - percent: lateFeeValue % dari sisa angsuran per hari terlambat
     */
    private function calcLateFee(InstallmentPlan $plan, InstallmentSchedule $schedule, string $paymentDate): float
    {
        if (!$plan->lateFeeType || $plan->lateFeeValue <= 0) return 0.0;

        $dueDate  = Carbon::parse($schedule->dueDate);
        $paidOn   = Carbon::parse($paymentDate);
        $daysLate = max(0, $paidOn->diffInDays($dueDate, false) * -1);
        $daysLate = max(0, $daysLate - ($plan->lateFeeGracePeriod ?? 0));

        if ($daysLate <= 0) return 0.0;

        $remaining = $schedule->scheduledAmount - $schedule->paidAmount;

        return match ($plan->lateFeeType) {
            'fixed'   => round($plan->lateFeeValue * $daysLate, 2),
            'percent' => round(($remaining * $plan->lateFeeValue / 100) * $daysLate, 2),
            default   => 0.0,
        };
    }

    /**
     * Update status schedule yang sudah melewati jatuh tempo menjadi 'overdue'.
     */
    private function markOverdueSchedules(): void
    {
        DB::table('transaction_installment_schedules as s')
            ->join('transaction_installment_plans as p', 'p.id', '=', 's.planId')
            ->where('p.isDeleted', 0)
            ->where('p.status', 'active')
            ->whereIn('s.status', ['unpaid', 'partial'])
            ->where('s.dueDate', '<', Carbon::today())
            ->update(['s.status' => 'overdue']);
    }
}
