<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\Cage;
use App\Models\CageInspection;
use App\Models\CageMaintenance;
use App\Models\CageCleaningLog;
use App\Exports\Cage\ExportCage;
use App\Exports\Cage\TemplateCage;
use App\Imports\Cage\CageImport;
use Maatwebsite\Excel\Facades\Excel;
use Validator;

class CageManagementController extends Controller
{
    // ─────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────

    private function getUserAccessInfo(Request $request): array
    {
        $user             = $request->user();
        $role             = strtolower($user->role ?? '');
        $jobTitleId       = $user->jobTitleId ?? null;
        $isAdministrator  = $role === 'administrator';
        $isAdminOrManager = in_array($role, ['administrator', 'manager']);

        return compact('role', 'jobTitleId', 'isAdministrator', 'isAdminOrManager');
    }

    /**
     * Build occupancy map: cageId → { petName, customerName, transactionType }
     * Query join ke tabel transaksi yang sudah ada tanpa mengubah FK.
     */
    private function getOccupancyMap(): array
    {
        $terminalStatuses = ['Selesai', 'Batal', 'Pet Ditolak Pet Hotel'];

        // Pet Hotel
        $hotel = DB::table('transactionPetHotelTreatmentCages as c')
            ->join('transaction_pet_hotels as t', 't.id', '=', 'c.transactionId')
            ->join('customerPets as p', 'p.id', '=', 't.petId')
            ->leftJoin('customer as cu', 'cu.id', '=', 't.customerId')
            ->whereNotIn('t.status', $terminalStatuses)
            ->where('c.isDeleted', 0)
            ->where('t.isDeleted', 0)
            ->select(
                'c.cageId',
                'p.petName',
                DB::raw("CONCAT(cu.firstName, ' ', IFNULL(cu.lastName,'')) as customerName"),
                DB::raw("'hotel' as transactionType")
            )->get();

        // Breeding
        $breeding = DB::table('transactionBreedingTreatmentCages as c')
            ->join('transaction_breedings as t', 't.id', '=', 'c.transactionId')
            ->join('customerPets as p', 'p.id', '=', 't.petId')
            ->leftJoin('customer as cu', 'cu.id', '=', 't.customerId')
            ->whereNotIn('t.status', $terminalStatuses)
            ->where('c.isDeleted', 0)
            ->where('t.isDeleted', 0)
            ->select(
                'c.cageId',
                'p.petName',
                DB::raw("CONCAT(cu.firstName, ' ', IFNULL(cu.lastName,'')) as customerName"),
                DB::raw("'breeding' as transactionType")
            )->get();

        // Salon
        $salon = DB::table('transactionPetSalonTreatmentCages as c')
            ->join('transaction_pet_salons as t', 't.id', '=', 'c.transactionId')
            ->join('customerPets as p', 'p.id', '=', 't.petId')
            ->leftJoin('customer as cu', 'cu.id', '=', 't.customerId')
            ->whereNotIn('t.status', $terminalStatuses)
            ->where('c.isDeleted', 0)
            ->where('t.isDeleted', 0)
            ->select(
                'c.cageId',
                'p.petName',
                DB::raw("CONCAT(cu.firstName, ' ', IFNULL(cu.lastName,'')) as customerName"),
                DB::raw("'salon' as transactionType")
            )->get();

        $map = [];
        foreach ($hotel->merge($breeding)->merge($salon) as $row) {
            $map[$row->cageId] = [
                'petName'         => trim($row->petName),
                'customerName'    => trim($row->customerName),
                'transactionType' => $row->transactionType,
            ];
        }

        return $map;
    }

    // ─────────────────────────────────────────────────────────────
    // CAGE CRUD
    // ─────────────────────────────────────────────────────────────

    /** GET /cage-management */
    public function index(Request $request)
    {
        $query = DB::table('cages as c')
            ->join('location as l', 'l.id', '=', 'c.locationId')
            ->where('c.isDeleted', 0)
            ->where('l.isDeleted', 0);

        if ($request->locationId) {
            $ids = is_array($request->locationId) ? $request->locationId : explode(',', $request->locationId);
            $query->whereIn('c.locationId', $ids);
        }
        if ($request->type) {
            $types = is_array($request->type) ? $request->type : explode(',', $request->type);
            $query->whereIn('c.type', $types);
        }
        if ($request->conditionStatus) {
            $conds = is_array($request->conditionStatus) ? $request->conditionStatus : explode(',', $request->conditionStatus);
            $query->whereIn('c.conditionStatus', $conds);
        }
        if ($request->status !== null && $request->status !== '') {
            $query->where('c.status', $request->status);
        }
        if ($request->search) {
            $query->where('c.cageName', 'like', '%' . $request->search . '%');
        }

        $query->select(
            'c.id', 'c.cageName', 'c.locationId', 'l.locationName',
            'c.type', 'c.size', 'c.status', 'c.conditionStatus',
            'c.capacity', 'c.amount', 'c.notes'
        )->orderBy('l.locationName')->orderBy('c.cageName');

        $data      = paginateData($query, $request);
        $occupancy = $this->getOccupancyMap();

        $data['data'] = collect($data['data'])->map(function ($cage) use ($occupancy) {
            $occ = $occupancy[$cage->id] ?? null;
            $cage->isOccupied       = (bool) $occ;
            $cage->occupantPet      = $occ['petName']         ?? null;
            $cage->occupantCustomer = $occ['customerName']    ?? null;
            $cage->occupantType     = $occ['transactionType'] ?? null;
            return $cage;
        });

        return response()->json($data);
    }

    /** POST /cage-management — tambah kandang baru */
    public function store(Request $request)
    {
        $access = $this->getUserAccessInfo($request);
        if (!$access['isAdminOrManager']) return responseUnauthorize();

        $validate = Validator::make($request->all(), [
            'locationId'      => 'required|integer|exists:location,id',
            'cageName'        => 'required|string|max:100',
            'type'            => 'required|in:hotel,breeding,salon,general',
            'size'            => 'nullable|in:S,M,L,XL',
            'status'          => 'required|in:0,1',
            'conditionStatus' => 'nullable|in:baik,perlu_perhatian,tidak_layak',
            'capacity'        => 'required|integer|min:1',
            'amount'          => 'required|integer|min:1',
            'notes'           => 'nullable|string|max:300',
        ]);

        if ($validate->fails()) return responseErrorValidation($validate->errors()->all());

        $cage = Cage::create([
            'locationId'      => $request->locationId,
            'cageName'        => $request->cageName,
            'type'            => $request->type,
            'size'            => $request->size,
            'status'          => $request->status,
            'conditionStatus' => $request->conditionStatus ?? 'baik',
            'capacity'        => $request->capacity,
            'amount'          => $request->amount,
            'notes'           => $request->notes,
            'userId'          => $request->user()->id,
        ]);

        recentActivity($request->user()->id, 'CageManagement', 'Create Cage', 'Created cage: ' . $request->cageName);

        return response()->json(['message' => 'Kandang berhasil ditambahkan', 'id' => $cage->id], 201);
    }

    /** GET /cage-management/detail */
    public function show(Request $request)
    {
        $cage = DB::table('cages as c')
            ->join('location as l', 'l.id', '=', 'c.locationId')
            ->where('c.id', $request->id)
            ->where('c.isDeleted', 0)
            ->select(
                'c.id', 'c.cageName', 'c.locationId', 'l.locationName',
                'c.type', 'c.size', 'c.status', 'c.conditionStatus',
                'c.capacity', 'c.amount', 'c.notes'
            )->first();

        if (!$cage) return responseError('not found', 'Kandang tidak ditemukan');

        $occupancy = $this->getOccupancyMap();
        $occ = $occupancy[$cage->id] ?? null;
        $cage->isOccupied       = (bool) $occ;
        $cage->occupantPet      = $occ['petName']         ?? null;
        $cage->occupantCustomer = $occ['customerName']    ?? null;
        $cage->occupantType     = $occ['transactionType'] ?? null;

        return response()->json($cage);
    }

    /** PUT /cage-management — update info kandang */
    public function update(Request $request)
    {
        $access = $this->getUserAccessInfo($request);
        if (!$access['isAdminOrManager']) return responseUnauthorize();

        $validate = Validator::make($request->all(), [
            'id'              => 'required|integer|exists:cages,id',
            'cageName'        => 'required|string|max:100',
            'type'            => 'required|in:hotel,breeding,salon,general',
            'size'            => 'nullable|in:S,M,L,XL',
            'status'          => 'required|in:0,1',
            'conditionStatus' => 'required|in:baik,perlu_perhatian,tidak_layak',
            'capacity'        => 'required|integer|min:1',
            'amount'          => 'required|integer|min:1',
            'notes'           => 'nullable|string|max:300',
        ]);

        if ($validate->fails()) return responseErrorValidation($validate->errors()->all());

        Cage::where('id', $request->id)->update([
            'cageName'        => $request->cageName,
            'type'            => $request->type,
            'size'            => $request->size,
            'status'          => $request->status,
            'conditionStatus' => $request->conditionStatus,
            'capacity'        => $request->capacity,
            'amount'          => $request->amount,
            'notes'           => $request->notes,
            'userUpdateId'    => $request->user()->id,
            'updated_at'      => now(),
        ]);

        recentActivity($request->user()->id, 'CageManagement', 'Update Cage', 'Updated cage id ' . $request->id);

        return responseSuccess([], 'Kandang berhasil diupdate');
    }

    /** DELETE /cage-management — soft delete kandang */
    public function destroy(Request $request)
    {
        $access = $this->getUserAccessInfo($request);
        if (!$access['isAdministrator']) return responseUnauthorize();

        $validate = Validator::make($request->all(), [
            'id' => 'required|integer|exists:cages,id',
        ]);

        if ($validate->fails()) return responseErrorValidation($validate->errors()->all());

        Cage::where('id', $request->id)->update([
            'isDeleted'   => 1,
            'deletedBy'   => $request->user()->id,
            'deletedAt'   => now(),
            'updated_at'  => now(),
        ]);

        recentActivity($request->user()->id, 'CageManagement', 'Delete Cage', 'Deleted cage id ' . $request->id);

        return responseSuccess([], 'Kandang berhasil dihapus');
    }

    // ─────────────────────────────────────────────────────────────
    // INSPEKSI
    // ─────────────────────────────────────────────────────────────

    /** GET /cage-management/inspection */
    public function getInspections(Request $request)
    {
        $query = DB::table('cage_inspections as i')
            ->join('users as u', 'u.id', '=', 'i.userId')
            ->where('i.cageId', $request->cageId);

        if ($request->dateFrom) $query->whereDate('i.inspectedAt', '>=', $request->dateFrom);
        if ($request->dateTo)   $query->whereDate('i.inspectedAt', '<=', $request->dateTo);

        $query->select(
            'i.id', 'i.cageId', 'i.conditionResult',
            'i.findings', 'i.recommendation', 'i.createMaintenance',
            DB::raw("DATE_FORMAT(i.inspectedAt, '%d/%m/%Y %H:%i') as inspectedAt"),
            DB::raw("CONCAT(u.firstName, ' ', IFNULL(u.lastName,'')) as inspectedBy")
        )->orderBy('i.inspectedAt', 'desc');

        return response()->json(paginateData($query, $request));
    }

    /** POST /cage-management/inspection */
    public function storeInspection(Request $request)
    {
        $access = $this->getUserAccessInfo($request);
        $allowedJobs = [2, 4, 5]; // Helper, Paramedis, Vetnurse
        if (!$access['isAdminOrManager'] && !in_array($access['jobTitleId'], $allowedJobs)) {
            return responseUnauthorize();
        }

        $validate = Validator::make($request->all(), [
            'cageId'            => 'required|integer|exists:cages,id',
            'conditionResult'   => 'required|in:baik,perlu_perhatian,tidak_layak',
            'findings'          => 'nullable|string',
            'recommendation'    => 'nullable|string',
            'createMaintenance' => 'nullable|boolean',
            'maintenanceTitle'  => 'required_if:createMaintenance,1|nullable|string|max:255',
        ]);

        if ($validate->fails()) return responseErrorValidation($validate->errors()->all());

        DB::beginTransaction();
        try {
            $inspection = CageInspection::create([
                'cageId'            => $request->cageId,
                'conditionResult'   => $request->conditionResult,
                'findings'          => $request->findings,
                'recommendation'    => $request->recommendation,
                'createMaintenance' => $request->createMaintenance ? 1 : 0,
                'inspectedAt'       => Carbon::now(),
                'userId'            => $request->user()->id,
            ]);

            // Update kondisi kandang otomatis
            Cage::where('id', $request->cageId)
                ->update(['conditionStatus' => $request->conditionResult, 'updated_at' => now()]);

            // Auto-create maintenance request jika diminta
            if ($request->createMaintenance) {
                CageMaintenance::create([
                    'cageId'      => $request->cageId,
                    'title'       => $request->maintenanceTitle,
                    'description' => $request->findings,
                    'status'      => 'pending',
                    'reportedBy'  => $request->user()->id,
                    'userId'      => $request->user()->id,
                ]);
            }

            recentActivity($request->user()->id, 'CageManagement', 'Inspection', 'Inspected cage id ' . $request->cageId);
            DB::commit();

            return response()->json(['message' => 'Inspeksi berhasil disimpan', 'id' => $inspection->id], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return responseError($e->getMessage(), 'Gagal menyimpan inspeksi');
        }
    }

    // ─────────────────────────────────────────────────────────────
    // MAINTENANCE
    // ─────────────────────────────────────────────────────────────

    /** GET /cage-management/maintenance */
    public function getMaintenances(Request $request)
    {
        $query = DB::table('cage_maintenances as m')
            ->join('users as ur', 'ur.id', '=', 'm.reportedBy')
            ->leftJoin('users as ua', 'ua.id', '=', 'm.assignedTo')
            ->where('m.cageId', $request->cageId);

        if ($request->status) {
            $statuses = is_array($request->status) ? $request->status : [$request->status];
            $query->whereIn('m.status', $statuses);
        }

        $query->select(
            'm.id', 'm.cageId', 'm.title', 'm.description', 'm.status',
            'm.estimatedDone', 'm.completedAt', 'm.completionNote',
            DB::raw("CONCAT(ur.firstName, ' ', IFNULL(ur.lastName,'')) as reportedBy"),
            DB::raw("CONCAT(ua.firstName, ' ', IFNULL(ua.lastName,'')) as assignedTo"),
            DB::raw("DATE_FORMAT(m.created_at, '%d/%m/%Y') as createdAt")
        )->orderBy('m.created_at', 'desc');

        return response()->json(paginateData($query, $request));
    }

    /** POST /cage-management/maintenance */
    public function storeMaintenance(Request $request)
    {
        $access = $this->getUserAccessInfo($request);
        if (!$access['isAdminOrManager']) return responseUnauthorize();

        $validate = Validator::make($request->all(), [
            'cageId'        => 'required|integer|exists:cages,id',
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'estimatedDone' => 'nullable|date',
            'assignedTo'    => 'nullable|integer|exists:users,id',
        ]);

        if ($validate->fails()) return responseErrorValidation($validate->errors()->all());

        $maintenance = CageMaintenance::create([
            'cageId'        => $request->cageId,
            'title'         => $request->title,
            'description'   => $request->description,
            'status'        => 'pending',
            'reportedBy'    => $request->user()->id,
            'assignedTo'    => $request->assignedTo,
            'estimatedDone' => $request->estimatedDone,
            'userId'        => $request->user()->id,
        ]);

        recentActivity($request->user()->id, 'CageManagement', 'Maintenance', 'Created maintenance for cage ' . $request->cageId);

        return response()->json(['message' => 'Maintenance request berhasil dibuat', 'id' => $maintenance->id], 201);
    }

    /** PUT /cage-management/maintenance */
    public function updateMaintenance(Request $request)
    {
        $access = $this->getUserAccessInfo($request);
        if (!$access['isAdminOrManager']) return responseUnauthorize();

        $validate = Validator::make($request->all(), [
            'id'             => 'required|integer|exists:cage_maintenances,id',
            'status'         => 'required|in:pending,in_progress,selesai',
            'assignedTo'     => 'nullable|integer|exists:users,id',
            'estimatedDone'  => 'nullable|date',
            'completionNote' => 'nullable|string',
        ]);

        if ($validate->fails()) return responseErrorValidation($validate->errors()->all());

        $patch = [
            'status'         => $request->status,
            'assignedTo'     => $request->assignedTo,
            'estimatedDone'  => $request->estimatedDone,
            'completionNote' => $request->completionNote,
            'userUpdateId'   => $request->user()->id,
            'updated_at'     => now(),
        ];

        if ($request->status === 'selesai') {
            $patch['completedAt'] = now();
            $maintenance = CageMaintenance::find($request->id);
            if ($maintenance) {
                Cage::where('id', $maintenance->cageId)
                    ->update(['conditionStatus' => 'baik', 'updated_at' => now()]);
            }
        }

        CageMaintenance::where('id', $request->id)->update($patch);

        return responseSuccess([], 'Status maintenance berhasil diupdate');
    }

    // ─────────────────────────────────────────────────────────────
    // CLEANING LOG
    // ─────────────────────────────────────────────────────────────

    /** GET /cage-management/cleaning-log */
    public function getCleaningLogs(Request $request)
    {
        $query = DB::table('cage_cleaning_logs as cl')
            ->join('users as u', 'u.id', '=', 'cl.userId')
            ->where('cl.cageId', $request->cageId);

        if ($request->dateFrom) $query->whereDate('cl.cleanedAt', '>=', $request->dateFrom);
        if ($request->dateTo)   $query->whereDate('cl.cleanedAt', '<=', $request->dateTo);

        $query->select(
            'cl.id', 'cl.cageId', 'cl.cleaningStatus', 'cl.catatan',
            DB::raw("DATE_FORMAT(cl.cleanedAt, '%d/%m/%Y %H:%i') as cleanedAt"),
            DB::raw("CONCAT(u.firstName, ' ', IFNULL(u.lastName,'')) as cleanedBy")
        )->orderBy('cl.cleanedAt', 'desc');

        return response()->json(paginateData($query, $request));
    }

    /** POST /cage-management/cleaning-log */
    public function storeCleaningLog(Request $request)
    {
        $access = $this->getUserAccessInfo($request);
        $allowedJobs = [2, 4, 5]; // Helper, Paramedis, Vetnurse
        if (!$access['isAdminOrManager'] && !in_array($access['jobTitleId'], $allowedJobs)) {
            return responseUnauthorize();
        }

        $validate = Validator::make($request->all(), [
            'cageId'         => 'required|integer|exists:cages,id',
            'cleaningStatus' => 'required|in:bersih,perlu_pembersihan_ulang,dilewati',
            'catatan'        => 'nullable|string',
        ]);

        if ($validate->fails()) return responseErrorValidation($validate->errors()->all());

        $log = CageCleaningLog::create([
            'cageId'         => $request->cageId,
            'cleaningStatus' => $request->cleaningStatus,
            'cleanedAt'      => Carbon::now(),
            'catatan'        => $request->catatan,
            'userId'         => $request->user()->id,
        ]);

        return response()->json(['message' => 'Cleaning log berhasil disimpan', 'id' => $log->id], 201);
    }

    // ─────────────────────────────────────────────────────────────
    // EXPORT / IMPORT
    // ─────────────────────────────────────────────────────────────

    /** GET /cage-management/export */
    public function export(Request $request)
    {
        $fileName = 'cage-management-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(
            new ExportCage(
                $request->search,
                $request->locationId,
                $request->type,
                $request->conditionStatus,
                $request->status
            ),
            $fileName
        );
    }

    /** GET /cage-management/import-template */
    public function downloadTemplate()
    {
        return Excel::download(new TemplateCage(), 'template-import-kandang.xlsx');
    }

    /** POST /cage-management/import */
    public function import(Request $request)
    {
        $access = $this->getUserAccessInfo($request);
        if (!$access['isAdminOrManager']) return responseUnauthorize();

        $validate = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls|max:5120',
        ]);

        if ($validate->fails()) return responseErrorValidation($validate->errors()->all());

        try {
            $importer = new CageImport($request->user()->id);
            Excel::import($importer, $request->file('file'));

            $inserted = $importer->getInserted();
            $skipped  = $importer->getSkipped();
            $errors   = $importer->getErrors();

            recentActivity(
                $request->user()->id,
                'CageManagement',
                'Import',
                "Import kandang: {$inserted} ditambahkan, {$skipped} dilewati, " . count($errors) . " error."
            );

            return response()->json([
                'message'  => "Import selesai. {$inserted} kandang ditambahkan, {$skipped} dilewati.",
                'inserted' => $inserted,
                'skipped'  => $skipped,
                'errors'   => $errors,
            ], 200);
        } catch (\Exception $e) {
            return responseError($e->getMessage(), 'Gagal memproses file import');
        }
    }
}
