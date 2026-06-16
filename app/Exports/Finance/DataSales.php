<?php

namespace App\Exports\Finance;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\Exportable;

class DataSales implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping
{
    use Exportable;

    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        // Re-use logic dari FinanceSalesController via query builder langsung
        $req = $this->request;

        // ── Pet Clinic ──────────────────────────────────────────────────────
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

        // ── Pet Hotel ───────────────────────────────────────────────────────
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

        // ── Pet Salon ───────────────────────────────────────────────────────
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

        // ── Breeding ────────────────────────────────────────────────────────
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

        // ── Pet Shop ────────────────────────────────────────────────────────
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

        $query = DB::table(DB::raw("({$union->toSql()}) as sales"))
            ->mergeBindings($union->getQuery());

        // Filter
        if ($req->status) {
            $query->where('status', $req->status);
        }
        if ($req->locationId && is_array($req->locationId) && count($req->locationId)) {
            $query->whereIn('locationId', $req->locationId);
        }
        if ($req->startDate && $req->endDate) {
            $query->whereBetween('transactionDate', [$req->startDate, $req->endDate]);
        }
        if ($req->search) {
            $kw = $req->search;
            $query->where(function ($q) use ($kw) {
                $q->where('invoiceNumber', 'like', "%{$kw}%")
                  ->orWhere('customerName',   'like', "%{$kw}%")
                  ->orWhere('locationName',   'like', "%{$kw}%")
                  ->orWhere('serviceType',    'like', "%{$kw}%");
            });
        }

        $data = $query->orderBy('sortableDate', 'desc')->get();

        $no = 1;
        foreach ($data as $row) {
            $row->number     = $no++;
            $row->total      = (float) $row->total;
            $row->paidAmount = (float) $row->paidAmount;
            $row->remaining  = (float) $row->remaining;
        }

        return $data;
    }

    public function headings(): array
    {
        return [[
            'No.',
            'No. Invoice',
            'Customer',
            'No. Member',
            'Cabang',
            'Jenis Layanan',
            'Tgl Transaksi',
            'Jatuh Tempo',
            'Total (Rp)',
            'Terbayar (Rp)',
            'Sisa (Rp)',
            'Status',
            'Dibuat Oleh',
            'Tanggal Dibuat',
        ]];
    }

    public function title(): string
    {
        return 'Daftar Sales';
    }

    public function map($item): array
    {
        $statusLabel = match($item->status) {
            'paid'      => 'Lunas',
            'partial'   => 'Cicilan',
            'unpaid'    => 'Belum Bayar',
            'cancelled' => 'Dibatalkan',
            default     => ucfirst($item->status),
        };

        return [[
            $item->number,
            $item->invoiceNumber ?? '-',
            $item->customerName,
            $item->memberNo ?? '-',
            $item->locationName,
            $item->serviceType,
            $item->transactionDate ?? '-',
            $item->dueDate ?? '-',
            $item->total,
            $item->paidAmount,
            $item->remaining,
            $statusLabel,
            $item->createdBy,
            $item->createdAt,
        ]];
    }
}
