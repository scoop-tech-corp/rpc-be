<?php

namespace App\Http\Controllers\Customer;

use DB;
use Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Customer\Customer;
use App\Models\Customer\CustomerPets;
use App\Models\Customer\CustomerTelephones;
use App\Models\Customer\CustomerEmails;
use App\Models\Customer\CustomerAddresses;
use App\Models\Customer\CustomerMessengers;
use App\Models\Customer\CustomerReminder;
use App\Exports\Customer\CustomerMergeHistoryExport;
use Maatwebsite\Excel\Facades\Excel;

class CustomerMergeController extends Controller
{
    // ── Kolom profil yang bisa dipilih dari source atau target ───────────────
    private const MERGEABLE_FIELDS = [
        'memberNo'           => 'Nomor Kartu',
        'customerGroupId'    => 'Grup Customer',
        'locationId'         => 'Lokasi',
        'notes'              => 'Catatan',
        'colorType'          => 'Color Type',
        'referenceCustomerId'=> 'Referensi',
        'joinDate'           => 'Tanggal Bergabung',
        'birthDate'          => 'Tanggal Lahir',
        'occupationId'       => 'Pekerjaan',
        'typeId'             => 'Tipe ID',
        'numberId'           => 'Nomor ID',
    ];

    // ── Tabel transaksi yang punya kolom customerId ───────────────────────────
    private const TRANSACTION_TABLES = [
        'transactionPetClinics'  => 'Pet Clinic',
        'transactionPetHotels'   => 'Pet Hotel',
        'transactionPetShop'     => 'Petshop',
        'transactionPetSalons'   => 'Pet Salon',
        'transactionBreedings'   => 'Breeding',
        'transactions'           => 'Transaksi Umum',
        'bookings'               => 'Booking',
        'deliveryOrders'         => 'Delivery Order',
        'queues'                 => 'Antrian',
    ];

    /**
     * GET /customer/merge/preview?sourceId=X&targetId=Y
     * Kembalikan perbandingan data A vs B untuk ditampilkan di UI
     */
    public function preview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sourceId' => 'required|integer',
            'targetId' => 'required|integer',
        ]);

        if ($validator->fails()) return responseInvalid($validator->errors()->all());

        $sourceId = $request->sourceId;
        $targetId = $request->targetId;

        if ($sourceId === $targetId) {
            return responseInvalid(['Customer sumber dan target tidak boleh sama.']);
        }

        $source = $this->getCustomerFull($sourceId);
        $target = $this->getCustomerFull($targetId);

        if (!$source) return responseInvalid(['Customer sumber tidak ditemukan.']);
        if (!$target) return responseInvalid(['Customer target tidak ditemukan.']);

        // Hitung jumlah relasi di masing-masing customer
        $sourceCounts = $this->getRelationCounts($sourceId);
        $targetCounts = $this->getRelationCounts($targetId);

        // Map field ID → nama display yang sudah di-fetch di getCustomerFull
        $displayMap = [
            'customerGroupId' => 'customerGroupName',
            'locationId'      => 'locationName',
        ];

        // Field perbandingan — saran default: pakai target, kecuali target kosong
        $fieldComparison = [];
        foreach (self::MERGEABLE_FIELDS as $field => $label) {
            $sourceVal = $source[$field] ?? null;
            $targetVal = $target[$field] ?? null;

            // Gunakan nama display jika ada (misal: nama grup, nama lokasi)
            $displayField    = $displayMap[$field] ?? null;
            $sourceDisplay   = $displayField ? ($source[$displayField] ?? $sourceVal) : $sourceVal;
            $targetDisplay   = $displayField ? ($target[$displayField] ?? $targetVal) : $targetVal;

            $fieldComparison[] = [
                'field'         => $field,
                'label'         => $label,
                'sourceValue'   => $sourceVal,
                'targetValue'   => $targetVal,
                'sourceDisplay' => $sourceDisplay,
                'targetDisplay' => $targetDisplay,
                'recommended'   => ($targetVal !== null && $targetVal !== '') ? 'target' : 'source',
            ];
        }

        return response()->json([
            'source'          => $source,
            'target'          => $target,
            'fieldComparison' => $fieldComparison,
            'sourceCounts'    => $sourceCounts,
            'targetCounts'    => $targetCounts,
        ], 200);
    }

    /**
     * POST /customer/merge/execute
     * Jalankan merge: pindahkan semua relasi dari source → target, soft-delete source
     */
    public function execute(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sourceId'      => 'required|integer',
            'targetId'      => 'required|integer',
            'fieldOverrides'=> 'nullable|array', // ['memberNo' => 'source', 'locationId' => 'target', ...]
        ]);

        if ($validator->fails()) return responseInvalid($validator->errors()->all());

        $sourceId      = (int)$request->sourceId;
        $targetId      = (int)$request->targetId;
        $fieldOverrides = $request->fieldOverrides ?? [];
        $userId        = $request->user()->id;

        if ($sourceId === $targetId) {
            return responseInvalid(['Customer sumber dan target tidak boleh sama.']);
        }

        $source = Customer::find($sourceId);
        $target = Customer::find($targetId);

        if (!$source || $source->isDeleted) return responseInvalid(['Customer sumber tidak ditemukan.']);
        if (!$target || $target->isDeleted) return responseInvalid(['Customer target tidak ditemukan.']);

        $transferredRelations = [];

        try {
            DB::beginTransaction();

            // ── 1. Override field profil target jika diminta ──────────────────
            $profileUpdates = [];
            foreach (self::MERGEABLE_FIELDS as $field => $label) {
                $choice = $fieldOverrides[$field] ?? 'target';
                if ($choice === 'source' && $source->$field !== null && $source->$field !== '') {
                    $profileUpdates[$field] = $source->$field;
                }
            }
            if (!empty($profileUpdates)) {
                $target->update($profileUpdates);
            }

            // ── 2. Pindahkan nomor telepon (skip duplikat) ────────────────────
            $existingPhones = CustomerTelephones::where('customerId', $targetId)
                ->pluck('phoneNumber')->toArray();

            $phones = CustomerTelephones::where('customerId', $sourceId)->get();
            $phoneMoved = 0;
            foreach ($phones as $phone) {
                if (!in_array($phone->phoneNumber, $existingPhones)) {
                    $phone->customerId = $targetId;
                    $phone->save();
                    $phoneMoved++;
                }
            }
            if ($phoneMoved) $transferredRelations['telephones'] = $phoneMoved;

            // ── 3. Pindahkan email (skip duplikat) ────────────────────────────
            $existingEmails = CustomerEmails::where('customerId', $targetId)
                ->pluck('email')->toArray();

            $emails = CustomerEmails::where('customerId', $sourceId)->get();
            $emailMoved = 0;
            foreach ($emails as $email) {
                if (!in_array($email->email, $existingEmails)) {
                    $email->customerId = $targetId;
                    $email->save();
                    $emailMoved++;
                }
            }
            if ($emailMoved) $transferredRelations['emails'] = $emailMoved;

            // ── 4. Pindahkan alamat ───────────────────────────────────────────
            $addressMoved = CustomerAddresses::where('customerId', $sourceId)
                ->update(['customerId' => $targetId]);
            if ($addressMoved) $transferredRelations['addresses'] = $addressMoved;

            // ── 5. Pindahkan messenger ────────────────────────────────────────
            $messengerMoved = CustomerMessengers::where('customerId', $sourceId)
                ->update(['customerId' => $targetId]);
            if ($messengerMoved) $transferredRelations['messengers'] = $messengerMoved;

            // ── 6. Pindahkan semua hewan peliharaan ───────────────────────────
            $petMoved = CustomerPets::where('customerId', $sourceId)
                ->update(['customerId' => $targetId]);
            if ($petMoved) $transferredRelations['pets'] = $petMoved;

            // ── 7. Pindahkan reminder ─────────────────────────────────────────
            $reminderMoved = CustomerReminder::where('customerId', $sourceId)
                ->update(['customerId' => $targetId]);
            if ($reminderMoved) $transferredRelations['reminders'] = $reminderMoved;

            // ── 8. Update customerId di semua tabel transaksi ─────────────────
            foreach (self::TRANSACTION_TABLES as $table => $label) {
                try {
                    $count = DB::table($table)
                        ->where('customerId', $sourceId)
                        ->update(['customerId' => $targetId]);
                    if ($count) $transferredRelations[$table] = $count;
                } catch (\Exception $e) {
                    // Tabel mungkin belum ada, skip
                }
            }

            // ── 9. Soft-delete customer sumber ────────────────────────────────
            $source->update([
                'isDeleted' => true,
                'deletedBy' => 'merge:' . $targetId,
                'deletedAt' => now(),
            ]);

            // ── 10. Simpan log merge ──────────────────────────────────────────
            DB::table('customer_merge_logs')->insert([
                'sourceCustomerId'    => $sourceId,
                'targetCustomerId'    => $targetId,
                'sourceCustomerName'  => trim("{$source->firstName} {$source->middleName} {$source->lastName}"),
                'targetCustomerName'  => trim("{$target->firstName} {$target->middleName} {$target->lastName}"),
                'fieldOverrides'      => json_encode($fieldOverrides),
                'transferredRelations'=> json_encode($transferredRelations),
                'userId'              => $userId,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            DB::commit();

            return response()->json([
                'message'             => 'Merge berhasil dilakukan.',
                'targetCustomerId'    => $targetId,
                'transferredRelations'=> $transferredRelations,
            ], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            return responseInvalid([$th->getMessage() . ' at line ' . $th->getLine()]);
        }
    }

    // ── Helper: ambil data lengkap customer ───────────────────────────────────
    private function getCustomerFull(int $id): ?array
    {
        $cust = DB::table('customer as c')
            ->leftJoin('customerGroups as cg', 'cg.id', '=', 'c.customerGroupId')
            ->leftJoin('location as l', 'l.id', '=', 'c.locationId')
            ->where('c.id', $id)
            ->where(function ($q) { $q->where('c.isDeleted', 0)->orWhereNull('c.isDeleted'); })
            ->select(
                'c.id', 'c.memberNo', 'c.firstName', 'c.middleName', 'c.lastName',
                'c.nickName', 'c.gender', 'c.customerGroupId', 'c.locationId',
                'c.notes', 'c.colorType', 'c.joinDate', 'c.birthDate',
                'c.referenceCustomerId', 'c.occupationId', 'c.typeId', 'c.numberId',
                'cg.customerGroup as customerGroupName',
                'l.locationName'
            )
            ->first();

        if (!$cust) return null;

        $arr = (array)$cust;

        // Nomor telepon utama
        $phone = DB::table('customerTelephones')
            ->where('customerId', $id)->where('usage', 'Utama')
            ->where(function ($q) { $q->where('isDeleted', 0)->orWhereNull('isDeleted'); })
            ->value('phoneNumber');
        $arr['phoneNumber'] = $phone;

        // Email utama
        $email = DB::table('customerEmails')
            ->where('customerId', $id)
            ->where(function ($q) { $q->where('isDeleted', 0)->orWhereNull('isDeleted'); })
            ->value('email');
        $arr['email'] = $email;

        return $arr;
    }

    // ── Helper: hitung jumlah relasi per customer ─────────────────────────────
    private function getRelationCounts(int $id): array
    {
        $counts = [
            'pets'         => CustomerPets::where('customerId', $id)
                                ->where(function ($q) { $q->where('isDeleted', 0)->orWhereNull('isDeleted'); })
                                ->count(),
            'telephones'   => CustomerTelephones::where('customerId', $id)->count(),
            'emails'       => CustomerEmails::where('customerId', $id)->count(),
            'transactions' => 0,
        ];

        foreach (self::TRANSACTION_TABLES as $table => $label) {
            try {
                $counts['transactions'] += DB::table($table)
                    ->where('customerId', $id)->count();
            } catch (\Exception $e) {}
        }

        return $counts;
    }

    // ── GET /customer/merge/history ───────────────────────────────────────────
    public function history(Request $request)
    {
        $rowPerPage = $request->rowPerPage ?? 10;
        $goToPage   = $request->goToPage   ?? 1;
        $dateFrom   = $request->dateFrom;
        $dateTo     = $request->dateTo;
        $locationId = $request->locationId;

        $query = DB::table('customer_merge_logs as ml')
            ->leftJoin('users as u', 'u.id', '=', 'ml.userId')
            ->leftJoin('customer as c', 'c.id', '=', 'ml.targetCustomerId')
            ->leftJoin('location as l', 'l.id', '=', 'c.locationId')
            ->select(
                'ml.id',
                'ml.sourceCustomerId',
                'ml.targetCustomerId',
                'ml.sourceCustomerName',
                'ml.targetCustomerName',
                'ml.transferredRelations',
                'ml.fieldOverrides',
                'ml.created_at',
                DB::raw("CONCAT_WS(' ', u.firstName, u.middleName, u.lastName) as performedBy"),
                'l.locationName',
                'c.locationId'
            );

        if ($dateFrom) {
            $query->whereDate('ml.created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('ml.created_at', '<=', $dateTo);
        }
        if ($locationId) {
            $query->where('c.locationId', $locationId);
        }

        $total = $query->count();
        $data  = $query->orderBy('ml.created_at', 'desc')
                       ->offset(($goToPage - 1) * $rowPerPage)
                       ->limit($rowPerPage)
                       ->get();

        return response()->json([
            'data'            => $data,
            'totalPagination' => $total,
        ], 200);
    }

    // ── GET /customer/merge/export ────────────────────────────────────────────
    public function exportHistory(Request $request)
    {
        $dateFrom   = $request->dateFrom;
        $dateTo     = $request->dateTo;
        $locationId = $request->locationId;

        $filename = 'riwayat-merge-customer-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(
            new CustomerMergeHistoryExport($dateFrom, $dateTo, $locationId),
            $filename
        );
    }
}
