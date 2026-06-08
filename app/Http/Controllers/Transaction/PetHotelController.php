<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Customer\Customer;
use App\Models\Customer\CustomerPets;
use App\Models\Customer\CustomerTelephones;
use App\Models\PromotionMaster;
use App\Models\Staff\UsersLocation;
use App\Models\transaction_pet_hotel_payment_based_sales;
use App\Models\transaction_pet_hotel_payment_bundle;
use App\Models\transaction_pet_hotel_payment_total;
use App\Models\transaction_pet_hotel_payments;
use App\Models\TransactionPetHotel;
use App\Models\TransactionPetHotelCheck;
use App\Models\transactionPetHotelTreatmentCage;
use App\Models\TransactionPetHotelTreatmentProduct;
use App\Models\TransactionPetHotelTreatmentService;
use App\Models\TransactionPetHotelTreatmentTreatPlan;
use App\Models\TransactionPetHotelPapanKerja;
use App\Models\TransactionPetHotelAdditionalTreatment;
use App\Models\TransactionPetHotelPrepayment;
use App\Models\TransactionPetHotelPolicyAgreement;
use App\Models\TransactionPetHotelCheckout;
use App\Models\User;
use Illuminate\Http\Request;
use Validator;
use DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Storage;

class PetHotelController extends Controller
{
    public function index(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $doctorId = DB::table('users as u')
            ->join('usersLocation as ul', 'u.id', 'ul.usersId')
            ->join('jobTitle as j', 'j.id', 'u.jobTitleId')
            ->select(
                'u.id',
            )->where('j.id', '=', 17)   //id job title dokter hewan
            ->where('u.isDeleted', '=', 0)
            ->where('u.id', '=', $request->user()->id)
            ->first();

        $statusDoc = 0;
        if ($doctorId) {
            $statusDoc = 1;
        }

        $data = DB::table('transaction_pet_hotels as t')
            ->join('location as l', 'l.id', 't.locationId')
            ->join('customer as c', 'c.id', 't.customerId')
            ->leftjoin('customerGroups as cg', 'cg.id', 'c.customerGroupId')
            ->leftJoin('users as u', 'u.id', 't.doctorId')
            ->join('users as uc', 'uc.id', 't.userId')
            ->select(
                't.id',
                'l.id as locationId',
                't.registrationNo',
                't.isTreatment',
                'l.locationName',
                'c.firstName',
                DB::raw("IFNULL(cg.customerGroup,'') as customerGroup"),
                DB::raw("IFNULL(t.startDate,'') as startDate"),
                DB::raw("IFNULL(t.endDate,'') as endDate"),
                't.status',
                'u.firstName as picDoctor',
                'uc.firstName as createdBy',
                DB::raw("DATE_FORMAT(t.created_at, '%d-%m-%Y %H:%i:%s') as createdAt"),
                DB::raw('CASE WHEN ' . $statusDoc . '=1 and u.id=' . $request->user()->id . ' and t.status="Cek Kondisi Pet" THEN 1 ELSE 0 END as isPetCheck')
            )
            ->where('t.isDeleted', '=', 0);

        if ($request->status == 'ongoing') {
            $data = $data->whereNotIn('t.status', ['Selesai', 'Batal']);
        } elseif ($request->status == 'finished') {
            $data = $data->whereIn('t.status', ['Selesai', 'Batal']);
        }

        // Kasir (jobTitleId=1) mendapat visibilitas penuh seperti Admin/Manager —
        // mereka mengelola semua transaksi (create/update/delete/payment).
        $isKasir = ($request->user()->jobTitleId == 1);

        if (
            $request->user()->roleId != 1   // bukan Administrator
            && $request->user()->roleId != 2 // bukan Manager
            && !$isKasir                     // bukan Kasir
        ) {
            // Role lain (dokter, staff, dll): filter hanya lokasi yang ditugaskan
            $locations = UsersLocation::select('locationId')
                ->where('usersId', $request->user()->id)
                ->pluck('locationId')
                ->toArray();

            if (!empty($locations)) {
                $data = $data->whereIn('l.id', $locations);
            }
            // Jika locations kosong (belum di-assign): tampilkan kosong — bukan bug
        } else {
            // Admin, Manager, dan Kasir: bisa filter by locationId dari request
            if ($request->locationId) {
                $data = $data->whereIn('l.id', $request->locationId);
            }
        }

        if ($request->customerGroupId) {

            $data = $data->whereIn('cg.id', $request->customerGroupId);
        }

        if ($request->search) {
            $data = $data->where(function ($q) use ($request) {
                $q->where('t.registrationNo', 'like', '%' . $request->search . '%')
                    ->orWhere('c.firstName', 'like', '%' . $request->search . '%')
                    ->orWhere('u.firstName', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->orderValue) {
            $data = $data->orderBy($request->orderColumn, $request->orderValue);
        }

        $data = $data->orderBy('t.updated_at', 'desc');

        $offset = ($page - 1) * $itemPerPage;

        $count_data = $data->count();
        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $data = $data->offset(0)->limit($itemPerPage)->get();
        } else {
            $data = $data->offset($offset)->limit($itemPerPage)->get();
        }

        $totalPaging = $count_data / $itemPerPage;

        return responseIndex(ceil($totalPaging), $data);
    }

    public function create(Request $request)
    {
        $access = $this->getUserAccessInfo($request);
        if (!$access['isAdminOrManager'] && $access['jobTitleId'] !== 1) {
            return $this->accessDenied('Hanya Kasir, Manager, atau Administrator yang dapat membuat transaksi.');
        }

        if ($request->isNewCustomer == true) {
            $validate = Validator::make($request->all(), [
                'isNewCustomer' => 'required|bool',
                'locationId' => 'required|integer',
                //'customerId' => 'nullable|integer',
                'customerName' => 'nullable|string',
                //'registrant' => 'nullable|string',
                //'petId' => 'nullable|integer',
                'petName' => 'required|string',
                'petCategory' => 'required|integer',
                'condition' => 'required|string',
                'petGender' => 'required|string|in:J,B',
                'isSterile' => 'required|bool',
                'doctorId' => 'required|int',
                'notes' => 'nullable|string',
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return responseInvalid($errors);
            }
        } else {
            $validate = Validator::make($request->all(), [
                'isNewCustomer' => 'required|bool',
                'locationId' => 'required|integer',
                'customerId' => 'nullable|integer',
                //'customerName' => 'nullable|string',
                'registrant' => 'nullable|string',
                'petId' => 'nullable|integer',
                //'petName' => 'required|string',
                //'petCategory' => 'required|integer',
                //'condition' => 'required|string',
                //'petGender' => 'required|string|in:J,B',
                //'isSterile' => 'required|bool',
                'doctorId' => 'required|int',
                'notes' => 'nullable|string',
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return responseInvalid($errors);
            }
        }

        $validate = Validator::make($request->all(), [
            'startDate' => 'required|date',
            'endDate' => 'required|date',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return responseInvalid($errors);
        }

        $startDate = Carbon::parse($request->startDate);
        $endDate = Carbon::parse($request->endDate);

        if ($startDate > $endDate) {
            return responseInvalid(['Start Date must be less than End Date']);
        }

        $loc = TransactionPetHotel::where('locationId', $request->locationId)->count();

        $date = Carbon::now()->format('d');
        $month = Carbon::now()->format('m');
        $year = Carbon::now()->format('Y');

        $petCheckRegistrationNo = str_pad($loc + 1, 3, 0, STR_PAD_LEFT) . '/LPIK-RIS-RPC-PH/' . $request->locationId . '/' . $date . '/' . $month . '/' . $year;

        DB::beginTransaction();
        try {

            if ($request->isNewCustomer == true) {
                $cust = Customer::create(
                    [
                        'firstName' => $request->customerName,
                        'locationId' => $request->locationId,
                        'typeId' => 0,

                        'memberNo' => '',
                        'gender' => '',
                        'joinDate' => Carbon::now(),
                        'createdBy' => $request->user()->id,
                        'userUpdateId' => $request->user()->id
                    ]
                );

                if ($request->isNewPet == true) {
                    $pet = CustomerPets::create(
                        [
                            'customerId' => $cust->id,
                            'petName' => $request->petName,
                            'petCategoryId' => $request->petCategory,
                            'races' => '',
                            'condition' => $request->condition,
                            'color' => '',
                            'petMonth' => $request->month,
                            'petYear' => $request->year,
                            'dateOfBirth' => $request->birthDate,
                            'petGender' => $request->petGender,
                            'isSteril' => $request->isSterile,
                            'createdBy' => $request->user()->id,
                            'userUpdateId' => $request->user()->id
                        ]
                    );
                }
            } else {
                //$locations = UsersLocation::select('id')->where('usersId', $request->user()->id)->get()->pluck('id')->toArray();
                $cust = Customer::select('id', 'isDeleted')->where('id', $request->customerId)->where('isDeleted', 0)->first();

                if (!$cust) {
                    DB::rollback();
                    return responseInvalid(['Customer is Not Found']);
                }

                if ($request->isNewPet == true) {
                    $pet = CustomerPets::create(
                        [
                            'customerId' => $cust->id,
                            'petName' => $request->petName,
                            'petCategoryId' => $request->petCategory,
                            'races' => '',
                            'condition' => $request->condition,
                            'color' => '',
                            'petMonth' => $request->month,
                            'petYear' => $request->year,
                            'dateOfBirth' => $request->birthDate,
                            'petGender' => $request->petGender,
                            'isSteril' => $request->isSterile,
                            'createdBy' => $request->user()->id,
                            'userUpdateId' => $request->user()->id
                        ]
                    );
                } else {

                    $pet = CustomerPets::select('id', 'isDeleted')->where('id', $request->petId)->where('customerId', $request->customerId)->where('isDeleted', 0)->first();

                    if (!$pet) {
                        DB::rollback();
                        return responseInvalid(['Pet is Not Found']);
                    }
                }
            }

            $doctorId = DB::table('users as u')
                ->join('usersLocation as ul', 'u.id', 'ul.usersId')
                ->join('jobTitle as j', 'j.id', 'u.jobTitleId')
                ->select(
                    'u.id',
                )->where('j.id', '=', 17)   //id job title dokter hewan
                ->where('u.isDeleted', '=', 0)
                ->where('u.id', '=', $request->doctorId)
                ->first();

            if (!$doctorId) {
                DB::rollback();
                return responseInvalid(['Doctor is Not Found']);
            }

            $trx = TransactionPetHotel::where('locationId', $request->locationId)->count();

            $regisNo = 'RPC.TRX.' . $request->locationId . '.' . str_pad($trx + 1, 8, 0, STR_PAD_LEFT);

            $tran = TransactionPetHotel::create([
                'registrationNo' => $regisNo,
                'petCheckRegistrationNo' => $petCheckRegistrationNo,
                'status' => 'Menunggu Dokter',
                'isNewCustomer' => $request->isNewCustomer,
                'isNewPet' => $request->isNewPet,
                'locationId' => $request->locationId,
                'customerId' => $cust->id,
                'petId' => $pet->id,
                'registrant' => $request->registrant,
                'startDate' => $request->startDate,
                'endDate' => $request->endDate,
                'doctorId' => $request->doctorId,
                'note' => $request->note ?? '',
                'userId' => $request->user()->id,
            ]);

            transactionPetHotelLog($tran->id, 'New Transaction', '', $request->user()->id);

            DB::commit();
            return responseCreate();
        } catch (\Throwable $th) {
            DB::rollback();
            return responseInvalid([$th->getMessage()]);
        }
    }

    public function detail(Request $request)
    {
        $detail = DB::table('transaction_pet_hotels as t')
            ->join('location as l', 'l.id', 't.locationId')
            ->join('customer as c', 'c.id', 't.customerId')
            ->join('customerPets as cp', 'cp.id', 't.PetId')
            ->join('petCategory as pc', 'pc.id', 'cp.petCategoryId')
            ->leftjoin('customerGroups as cg', 'cg.id', 'c.customerGroupId')
            ->join('users as u', 'u.id', 't.doctorId')
            ->join('users as uc', 'uc.id', 't.userId')
            ->select(
                't.id',
                't.registrationNo',
                't.isNewCustomer',
                't.registrant',
                'l.id as locationId',
                'l.locationName',
                'c.id as customerId',
                'c.firstName as customerName',
                DB::raw("IFNULL(cg.customerGroup,'') as customerGroup"),
                DB::raw("IFNULL(t.startDate,'') as startDate"),
                DB::raw("IFNULL(t.endDate,'') as endDate"),
                't.status',
                'u.id as doctorId',
                'u.firstName as picDoctor',
                't.note',

                'cp.id as petId',
                'cp.petName',
                'cp.petCategoryId',
                'pc.petCategoryName',
                'cp.condition',
                'cp.petGender',
                'cp.isSteril as petSterile',
                'cp.petMonth',
                'cp.petYear',
                'cp.dateOfBirth',

                'uc.firstName as createdBy',
                DB::raw("DATE_FORMAT(t.created_at, '%d-%m-%Y %H:%m:%s') as createdAt")
            )
            ->where('t.id', '=', $request->id)
            ->first();

        $log = DB::table('transaction_pet_hotel_logs as tl')
            ->join('transaction_pet_hotels as t', 't.id', 'tl.transactionId')
            ->join('users as u', 'u.id', 'tl.userId')
            ->select(
                'tl.id',
                'tl.activity',
                'tl.remark',
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(tl.created_at, '%d-%m-%Y %H:%m:%s') as createdAt")
            )
            ->where('tl.transactionId', '=', $request->id)
            ->orderBy('tl.id', 'desc')
            ->get();

        // DP (prepayment)
        $dpLogs = DB::table('transaction_pet_hotel_prepayments as p')
            ->leftJoin('paymentmethod as pm', 'pm.id', 'p.paymentMethodId')
            ->join('users as u', 'u.id', 'p.userId')
            ->select(
                'p.id',
                DB::raw("COALESCE(p.nota_number, CONCAT('DP-', p.id)) as notaNumber"),
                DB::raw("'DP / Pembayaran Awal' as type"),
                DB::raw("CONCAT('Rp ', FORMAT(p.amount, 0)) as amount"),
                DB::raw("COALESCE(pm.name, '-') as paymentMethod"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(p.created_at, '%d-%m-%Y %H:%i:%s') as date"),
                'p.catatan as note',
                DB::raw("IF(p.proofPath IS NOT NULL, 'ada', null) as hasProof")
            )
            ->where('p.transactionId', $request->id)
            ->get();

        // Pelunasan (checkout payment)
        $pelunasanLogs = DB::table('transaction_pet_hotel_payment_totals as tpt')
            ->leftJoin('paymentmethod as pm', 'pm.id', 'tpt.paymentMethodId')
            ->join('users as u', 'u.id', 'tpt.userId')
            ->select(
                'tpt.id',
                'tpt.nota_number as notaNumber',
                DB::raw("'Pelunasan' as type"),
                DB::raw("CONCAT('Rp ', FORMAT(tpt.amountPaid, 0)) as amount"),
                DB::raw("COALESCE(pm.name, '-') as paymentMethod"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(tpt.created_at, '%d-%m-%Y %H:%i:%s') as date"),
                'tpt.note as note',
                DB::raw("IF(tpt.proofOfPayment IS NOT NULL, 'ada', null) as hasProof")
            )
            ->where('tpt.transactionId', $request->id)
            ->get();

        $paymentLog = $pelunasanLogs->concat($dpLogs)->sortByDesc('date')->values();

        // Kandang yang dipakai
        $cage = DB::table('transactionPetHotelTreatmentCages as tc')
            ->join('cages as c', 'c.id', 'tc.cageId')
            ->select('c.cageName', 'c.type as cageType', 'c.size as cageSize')
            ->where('tc.transactionId', $request->id)
            ->first();

        if ($detail) {
            $detail->cageName = $cage->cageName ?? null;
            $detail->cageType = $cage->cageType ?? null;
            $detail->cageSize = $cage->cageSize ?? null;
        }

        // Policy agreements — sertakan signatureData dan isi policy (raw_content)
        $policyAgreements = DB::table('transaction_pet_hotel_policy_agreements as pa')
            ->leftJoin('users as u', 'u.id', 'pa.userId')
            ->leftJoin('contract_templates as ct', 'ct.id', 'pa.contractTemplateId')
            ->select(
                'pa.id',
                'pa.contractTitle',
                'pa.contractVersion',
                'pa.signerName',
                'pa.signatureData',
                DB::raw("DATE_FORMAT(pa.signedAt, '%d-%m-%Y %H:%i') as signedAt"),
                'u.firstName as recordedBy',
                DB::raw("IF(pa.signatureData IS NOT NULL AND pa.signatureData != '', 1, 0) as hasSigned"),
                'ct.raw_content as rawContent'
            )
            ->where('pa.transactionId', $request->id)
            ->orderBy('pa.id')
            ->get();

        $data = [
            'detail'           => $detail,
            'transactionLogs'  => $log,
            'paymentLogs'      => $paymentLog,
            'policyAgreements' => $policyAgreements,
        ];

        return response()->json($data, 200);
    }

    public function update(Request $request)
    {
        $access = $this->getUserAccessInfo($request);
        if (!$access['isAdminOrManager'] && $access['jobTitleId'] !== 1) {
            return $this->accessDenied('Hanya Kasir, Manager, atau Administrator yang dapat mengubah transaksi.');
        }

        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return responseInvalid($errors);
        }

        if ($request->isNewCustomer == true) {
            $validate = Validator::make($request->all(), [
                'isNewCustomer' => 'required|bool',
                'locationId' => 'required|integer',
                //'customerId' => 'nullable|integer',
                'customerName' => 'nullable|string',
                //'registrant' => 'nullable|string',
                //'petId' => 'nullable|integer',
                'petName' => 'required|string',
                'petCategory' => 'required|integer',
                'condition' => 'required|string',
                'petGender' => 'required|string|in:J,B',
                'isSterile' => 'required|bool',
                'doctorId' => 'required|int',
                'notes' => 'nullable|string',
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return responseInvalid($errors);
            }
        } else {
            $validate = Validator::make($request->all(), [
                'isNewCustomer' => 'required|bool',
                'locationId' => 'required|integer',
                'customerId' => 'nullable|integer',
                //'customerName' => 'nullable|string',
                'registrant' => 'nullable|string',
                'petId' => 'nullable|integer',
                //'petName' => 'required|string',
                //'petCategory' => 'required|integer',
                //'condition' => 'required|string',
                //'petGender' => 'required|string|in:J,B',
                //'isSterile' => 'required|bool',
                'doctorId' => 'required|int',
                'notes' => 'nullable|string',
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return responseInvalid($errors);
            }
        }

        $validate = Validator::make($request->all(), [
            'startDate' => 'required|date',
            'endDate' => 'required|date',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return responseInvalid($errors);
        }

        $startDate = Carbon::parse($request->startDate);
        $endDate = Carbon::parse($request->endDate);

        if ($startDate > $endDate) {
            return responseInvalid(['Start Date must be less than End Date']);
        }


        DB::beginTransaction();

        try {

            $oldTransaction = TransactionPetHotel::find($request->id);

            $transaction = TransactionPetHotel::updateOrCreate(
                ['id' => $request->id],
                [
                    'registrationNo' => $request->registrationNo,
                    'isNewCustomer' => $request->isNewCustomer,
                    'isNewPet' => $request->isNewPet,
                    'locationId' => $request->locationId,
                    'customerId' => $request->customerId,
                    'petId' => $request->petId,
                    'registrant' => $request->registrant,
                    'startDate' => $request->startDate,
                    'endDate' => $request->endDate,
                    'doctorId' => $request->doctorId,
                    'note' => $request->note ?? '',
                    'userUpdatedId' => $request->user()->id,
                ]
            );

            if ($oldTransaction) {
                $fieldNames = [
                    'registrationNo' => 'Nomor Registrasi',
                    'locationId' => 'Lokasi',
                    'customerId' => 'ID Pelanggan',
                    'petId' => 'Data Hewan',
                    'registrant' => 'Pendaftar',
                    'startDate' => 'Tanggal Mulai',
                    'endDate' => 'Tanggal Selesai',
                    'doctorId' => 'Dokter yang menangani',
                    'note' => 'Catatan',
                ];

                $changes = $transaction->getChanges();

                foreach ($changes as $field => $newValue) {
                    if ($field != 'updated_at') {
                        $customName = $fieldNames[$field] ?? $field;

                        if ($customName == 'Dokter yang menangani') {
                            $doctor = User::where([['id', '=', $newValue]])->first();

                            transactionPetHotelLog($request->id, 'Update Transaction', "Data '{$customName}' telah diubah menjadi {$doctor->firstName}", $request->user()->id);
                        } else {
                            transactionPetHotelLog($request->id, 'Update Transaction', "Data '{$customName}' telah diubah menjadi {$newValue}", $request->user()->id);
                        }
                    }
                }
            }

            DB::commit();
            return responseUpdate();
        } catch (\Throwable $th) {
            DB::rollback();
            return responseInvalid([$th->getMessage()]);
        }
    }

    public function delete(Request $request)
    {
        $access = $this->getUserAccessInfo($request);
        if (!$access['isAdministrator']) {
            return $this->accessDenied('Hanya Administrator yang dapat menghapus transaksi.');
        }

        $transactions = TransactionPetHotel::whereIn('id', $request->id)->get();

        if ($transactions->count() !== count($request->id)) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['There is any Data not found!'],
            ], 422);
        }

        $now = Carbon::now();
        TransactionPetHotel::whereIn('id', $request->id)->update([
            'DeletedBy' => $request->user()->id,
            'isDeleted' => true,
            'DeletedAt' => $now
        ]);

        foreach ($request->id as $va) {
            transactionPetHotelLog($va, 'Transaction Deleted', '', $request->user()->id);
        }

        return responseDelete();
    }

    public function export(Request $request)
    {

        $data = DB::table('transaction_pet_hotels as t')
            ->join('location as l', 'l.id', 't.locationId')
            ->join('customer as c', 'c.id', 't.customerId')
            ->join('customerPets as cp', 'cp.id', 't.PetId')
            ->leftjoin('customerGroups as cg', 'cg.id', 'c.customerGroupId')
            ->join('users as u', 'u.id', 't.doctorId')
            ->join('users as uc', 'uc.id', 't.userId')
            ->select(
                't.registrationNo',
                'l.locationName',
                'c.firstName',
                DB::raw("IFNULL(cg.customerGroup,'') as customerGroup"),
                DB::raw("IFNULL(t.startDate,'') as startDate"),
                DB::raw("IFNULL(t.endDate,'') as endDate"),
                't.status',
                'u.firstName as picDoctor',
                'uc.firstName as createdBy',
                DB::raw("DATE_FORMAT(t.created_at, '%d-%m-%Y %H:%m:%s') as createdAt")
            )
            ->where('t.isDeleted', '=', 0);

        if ($request->status == 'ongoing') {
            $data = $data->whereNotIn('t.status', ['Selesai', 'Batal']);
        } elseif ($request->status == 'finished') {
            $data = $data->whereIn('t.status', ['Selesai', 'Batal']);
        }

        $data = $data->orderBy('t.updated_at', 'desc')->get();

        $spreadsheet = IOFactory::load(public_path() . '/template/transaction/' . 'Template_Export_Transaction.xlsx');

        $sheet = $spreadsheet->getSheet(0);

        $row = 2;
        foreach ($data as $item) {

            $sheet->setCellValue("A{$row}", $row - 1);
            $sheet->setCellValue("B{$row}", $item->registrationNo);
            $sheet->setCellValue("C{$row}", $item->locationName);
            $sheet->setCellValue("D{$row}", $item->firstName);
            $sheet->setCellValue("E{$row}", $item->customerGroup);
            $sheet->setCellValue("F{$row}", $item->startDate);
            $sheet->setCellValue("G{$row}", $item->endDate);
            $sheet->setCellValue("H{$row}", $item->status);
            $sheet->setCellValue("I{$row}", $item->picDoctor);
            $sheet->setCellValue("J{$row}", $item->createdBy);
            $sheet->setCellValue("K{$row}", $item->createdAt);

            $row++;
        }

        $fileName = 'Export Transaksi Pet Hotel.xlsx';

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . $fileName; // Set the desired path
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function acceptionTransaction(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'transactionId' => 'required|integer',
            'status' => 'required|bool',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return responseInvalid($errors);
        }

        $tran = TransactionPetHotel::where([['id', '=', $request->transactionId]])->first();

        $locs = UsersLocation::where([['usersId', '=', $request->user()->id]])->get();

        $user = User::where([['id', '=', $request->user()->id]])->first();

        if ($user->jobTitleId != 17) { //id job title dokter hewan
            return responseErrorValidation('You are not a doctor!', 'You are not a doctor!');
        }

        $temp = false;
        foreach ($locs as $val) {
            if ($val['locationId'] == $tran->locationId) {
                $temp = true;
            }
        }

        if (!$temp) {
            return responseErrorValidation('Can not accept transaction because the designated doctor is different!', 'Can not accept transaction because the designated doctor is different!');
        }

        $doctor = User::where([['id', '=', $request->user()->id]])->first();

        if ($request->status == 1) {

            statusTransactionPetHotel($request->transactionId, 'Cek Kondisi Pet', $request->user()->id);

            transactionPetHotelLog($request->transactionId, 'Pemeriksaan pasien oleh ' . $doctor->firstName, '', $request->user()->id);
        } else {

            $validate = Validator::make($request->all(), [
                'reason' => 'required|string',
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return responseInvalid($errors);
            }

            statusTransactionPetHotel($request->transactionId, 'Ditolak Dokter', $request->user()->id);

            transactionPetHotelLog($request->transactionId, 'Pasien Ditolak oleh ' . $doctor->firstName, $request->reason, $request->user()->id);
        }

        return responseCreate();
    }

    public function reassignDoctor(Request $request)
    {
        $access = $this->getUserAccessInfo($request);
        if (!$access['isAdminOrManager'] && $access['jobTitleId'] !== 1) {
            return $this->accessDenied('Hanya Kasir, Manager, atau Administrator yang dapat melakukan reassign dokter.');
        }

        $validate = Validator::make($request->all(), [
            'transactionId' => 'required|integer',
            'doctorId' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return responseInvalid($errors);
        }

        $doctor = User::where([['id', '=', $request->doctorId]])->first();

        if (!$doctor) {
            return responseInvalid(['Doctor is not found!']);
        }

        $user = User::where([['id', '=', $request->user()->id]])->first();

        TransactionPetHotel::where('id', $request->transactionId)->update([
            'doctorId' => $request->doctorId,
        ]);

        statusTransactionPetHotel($request->transactionId, 'Menunggu Dokter', $request->user()->id);

        transactionPetHotelLog($request->transactionId, 'Menunggu konfirmasi dokter', 'Dokter dipindahkan ke ' . $doctor->firstName . ' oleh ' . $user->firstName, $request->user()->id);

        return responseCreate();
    }

    public function createPetCheck(Request $request)
    {
        $access = $this->getUserAccessInfo($request);
        if (!$access['isAdministrator'] && $access['jobTitleId'] !== 17) {
            return $this->accessDenied('Hanya Dokter Hewan atau Administrator yang dapat mengisi data cek kondisi pet.');
        }

        $validate = Validator::make($request->all(), [
            'transactionId' => 'required|integer',
            'numberVaccines' => 'required|integer',
            'isLiceFree' => 'required|bool',
            'noteLiceFree' => 'nullable|string',
            'isFungusFree' => 'required|bool',
            'noteFungusFree' => 'nullable|string',
            'isPregnant' => 'required|bool',
            'estimateDateofBirth' => 'nullable|date_format:Y-m-d',
            'isRecomendInpatient' => 'nullable|bool',
            'noteInpatient' => 'nullable|string',
            'isParent' => 'required|bool',
            'isBreastfeeding' => 'nullable|bool',
            'numberofChildren' => 'nullable|integer',
            'isAcceptToProcess' => 'required|bool',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return responseInvalid($errors);
        }

        if ($request->isAcceptToProcess == 0) {
            $validate = Validator::make($request->all(), [
                'reasonReject' => 'required|string',
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return responseInvalid($errors);
            }
        }

        TransactionPetHotelCheck::create([
            'transactionId' => $request->transactionId,
            'numberVaccines' => $request->numberVaccines,
            'isLiceFree' => $request->isLiceFree,
            'noteLiceFree' => $request->noteLiceFree,
            'isFungusFree' => $request->isFungusFree,
            'noteFungusFree' => $request->noteFungusFree,
            'isPregnant' => $request->isPregnant,
            'estimateDateofBirth' => $request->estimateDateofBirth,
            'isRecomendInpatient' => $request->isRecomendInpatient,
            'noteInpatient' => $request->noteInpatient,
            'isParent' => $request->isParent,
            'isBreastfeeding' => $request->isBreastfeeding,
            'numberofChildren' => $request->numberofChildren,
            'isAcceptToProcess' => $request->isAcceptToProcess,
            'reasonReject' => $request->reasonReject,
            'userId' => $request->user()->id,
        ]);

        $doctor = User::where([['id', '=', $request->user()->id]])->first();

        if ($request->isAcceptToProcess) {

            if ($request->isRecomendInpatient) {
                transactionPetHotelLog($request->transactionId, 'Pet Selesai diperiksa oleh ' . $doctor->firstName, 'Pet dipindahkan ke Pet Clinic', $request->user()->id);
                statusTransactionPetHotel($request->transactionId, 'Pet Dipindahkan ke Pet Clinic', $request->user()->id);
            } else {
                transactionPetHotelLog($request->transactionId, 'Pet Selesai diperiksa oleh ' . $doctor->firstName, 'Pet lolos cek kondisi, siap check-in', $request->user()->id);
                statusTransactionPetHotel($request->transactionId, 'Pet Check-In', $request->user()->id);
                TransactionPetHotel::where('id', '=', $request->transactionId)
                    ->update([
                        'isTreatment' => true,
                    ]);
            }
        } else {
            transactionPetHotelLog($request->transactionId, 'Pet Selesai diperiksa oleh ' . $doctor->firstName, 'Pet ditolak masuk Pet Hotel karena ' . $request->reasonReject, $request->user()->id);
            statusTransactionPetHotel($request->transactionId, 'Pet Ditolak Pet Hotel', $request->user()->id);
        }

        return responseCreate();
    }

    /**
     * GET /transaction/pethotel/check-condition?transactionId=X
     * Mengembalikan data kondisi pet (hasil cek dokter) untuk transaksi tertentu.
     * Digunakan FE treatment form agar bisa menampilkan banner & filter kandang.
     */
    public function getCheckCondition(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'transactionId' => 'required|integer',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $check = TransactionPetHotelCheck::where('transactionId', $request->transactionId)
            ->where('isAcceptToProcess', 1)
            ->latest()
            ->first();

        if (!$check) {
            return response()->json([
                'isPregnant'         => false,
                'estimateDateofBirth'=> null,
                'isRecomendInpatient'=> false,
                'noteInpatient'      => null,
                'isParent'           => false,
                'isBreastfeeding'    => false,
                'numberofChildren'   => 0,
            ], 200);
        }

        return response()->json([
            'isPregnant'          => (bool) $check->isPregnant,
            'estimateDateofBirth' => $check->estimateDateofBirth,
            'isRecomendInpatient' => (bool) $check->isRecomendInpatient,
            'noteInpatient'       => $check->noteInpatient,
            'isParent'            => (bool) $check->isParent,
            'isBreastfeeding'     => (bool) $check->isBreastfeeding,
            'numberofChildren'    => (int) $check->numberofChildren,
        ], 200);
    }

    public function Treatment(Request $request)
    {
        $access = $this->getUserAccessInfo($request);
        if (!$access['isAdministrator'] && $access['jobTitleId'] !== 17) {
            return $this->accessDenied('Hanya Dokter Hewan atau Administrator yang dapat menginput treatment.');
        }

        $validate = Validator::make($request->all(), [
            'transactionId' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return responseInvalid($errors);
        }

        $tran = TransactionPetHotel::where('id', '=', $request->transactionId)->where('isDeleted', '=', 0)->first();

        if (!$tran) {
            return responseInvalid(['Transaction is not found or already deleted!']);
        }

        if (!$request->cageId) {
            return responseInvalid(['Kandang wajib dipilih!']);
        }

        if (!$request->stayServiceId) {
            return responseInvalid(['Tarif menginap wajib dipilih!']);
        }

        $services = json_decode($request->services, true) ?? [];
        $productSell = json_decode($request->productSells, true) ?? [];
        $productClinic = json_decode($request->productClinics, true) ?? [];
        $treatmentPlans = json_decode($request->treatmentPlans, true) ?? [];

        if (count($treatmentPlans) === 0) {
            return responseInvalid(['Minimal satu rencana perawatan (Treatment Plan) harus diisi!']);
        }

        //proses insert
        DB::beginTransaction();
        try {
            foreach ($services as $value) {

                TransactionPetHotelTreatmentService::create([
                    'transactionId' => $request->transactionId,
                    'serviceId' => $value['id'],
                    'quantity' => $value['quantity'],
                    'userId' => $request->user()->id,
                ]);
            }

            foreach ($productSell as $value) {
                TransactionPetHotelTreatmentProduct::create([
                    'transactionId' => $request->transactionId,
                    'productId' => $value['id'],
                    'quantity' => $value['quantity'],
                    'userId' => $request->user()->id,
                ]);
            }

            foreach ($productClinic as $value) {
                TransactionPetHotelTreatmentProduct::create([
                    'transactionId' => $request->transactionId,
                    'productId' => $value['id'],
                    'quantity' => $value['quantity'],
                    'userId' => $request->user()->id,
                ]);
            }

            foreach ($treatmentPlans as $value) {
                TransactionPetHotelTreatmentTreatPlan::create([
                    'transactionId' => $request->transactionId,
                    'treatmentPlanId' => $value['id'],
                    'userId' => $request->user()->id,
                ]);
            }

            transactionPetHotelTreatmentCage::create([
                'transactionId' => $request->transactionId,
                'cageId' => $request->cageId,
                'userId' => $request->user()->id,
            ]);

            // Simpan service yang menjadi tarif menginap per hari
            if ($request->stayServiceId) {
                TransactionPetHotel::where('id', $request->transactionId)
                    ->update(['stayServiceId' => $request->stayServiceId]);
            }

            $this->generatePapanKerja($request->transactionId, $request->user()->id);

            // Status menunggu owner e-sign policies sebelum resmi "Dalam Perawatan"
            statusTransactionPetHotel($request->transactionId, 'Menunggu Persetujuan Policy', $request->user()->id);

            transactionPetHotelLog($request->transactionId, 'Rencana perawatan & kandang ditetapkan — menunggu persetujuan owner', '', $request->user()->id);

            DB::commit();
            return responseCreate();
        } catch (\Throwable $th) {
            DB::rollback();

            return response()->json([
                'message' => 'Failed',
                'errors' => $th,
            ]);
        }
    }

    // ─── Policy Agreement ────────────────────────────────────────────────────────

    /** Daftar policies aktif untuk dipilih kasir */
    public function getPoliciesForAgreement(Request $request)
    {
        $policies = DB::table('contract_templates')
            ->where('isDeleted', 0)
            ->where('status', 'active')
            ->select('id', 'title', 'version', 'raw_content')
            ->orderBy('title')
            ->get();

        return response()->json($policies);
    }

    /** Simpan tanda tangan owner per policy, lalu set status "Dalam Perawatan" */
    public function savePolicyAgreement(Request $request)
    {
        $access = $this->getUserAccessInfo($request);
        if (!$access['isAdminOrManager'] && $access['jobTitleId'] !== 1) {
            return $this->accessDenied('Hanya Kasir, Manager, atau Administrator yang dapat menyimpan persetujuan policy.');
        }

        $validate = Validator::make($request->all(), [
            'transactionId'  => 'required|integer',
            'signerName'     => 'required|string',
            'signatureData'  => 'required|string', // base64
            'policies'       => 'required|string', // JSON array of {id, title, version}
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $tran = TransactionPetHotel::where('id', $request->transactionId)
            ->where('isDeleted', 0)
            ->first();

        if (!$tran) {
            return responseInvalid(['Transaksi tidak ditemukan.']);
        }

        if ($tran->status !== 'Menunggu Persetujuan Policy') {
            return responseInvalid(['Status transaksi tidak valid untuk persetujuan policy.']);
        }

        $policies = json_decode($request->policies, true) ?? [];

        if (empty($policies)) {
            return responseInvalid(['Minimal satu policy harus dipilih.']);
        }

        DB::beginTransaction();
        try {
            $signedAt = now();

            foreach ($policies as $policy) {
                TransactionPetHotelPolicyAgreement::create([
                    'transactionId'      => $request->transactionId,
                    'contractTemplateId' => $policy['id'],
                    'contractTitle'      => $policy['title'],
                    'contractVersion'    => $policy['version'],
                    'signatureData'      => $request->signatureData,
                    'signerName'         => $request->signerName,
                    'signedAt'           => $signedAt,
                    'userId'             => $request->user()->id,
                ]);
            }

            statusTransactionPetHotel($request->transactionId, 'Dalam Perawatan', $request->user()->id);

            transactionPetHotelLog(
                $request->transactionId,
                'Owner menyetujui dan menandatangani ' . count($policies) . ' policy — pet resmi dalam perawatan',
                'Ditandatangani oleh: ' . $request->signerName,
                $request->user()->id
            );

            DB::commit();
            return responseCreate();
        } catch (\Throwable $th) {
            DB::rollback();
            return responseInvalid([$th->getMessage()]);
        }
    }

    public function showDataBeforePayment(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'transactionId' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return responseInvalid($errors);
        }

        $trans = TransactionPetHotel::find($request->transactionId);

        $cages = TransactionPetHotelTreatmentCage::from('transactionPetHotelTreatmentCages as tpcs')
            ->join('cages as c', 'c.id', '=', 'tpcs.cageId')
            ->select('c.id', 'c.cageName as unitName')->where('transactionId', $request->transactionId)
            ->first();

        if (!$trans) {
            return responseInvalid(['Transaction is not found!']);
        }

        $phone = CustomerTelephones::where('customerId', '=', $trans->customerId)
            ->where('usage', '=', 'Utama')
            ->first();

        $cust = Customer::find($trans->customerId);

        $dataServices = TransactionPetHotelTreatmentService::from('transactionPetHotelTreatmentServices as tpcs')
            ->join('services as s', 's.id', '=', 'tpcs.serviceId')
            ->join('servicesPrice as sp', 's.id', '=', 'sp.service_id')
            ->select(
                's.id as serviceId',
                's.fullName as serviceName',
                DB::raw("TRIM(tpcs.quantity)+0 as quantity"),
                DB::raw("CAST(REPLACE(sp.price, ',', '') AS UNSIGNED) as basedPrice"),
            )
            ->where('tpcs.transactionId', '=', $request->transactionId)
            ->where('sp.location_id', '=', $trans->locationId)
            ->get();

        $dataProducts = TransactionPetHotelTreatmentProduct::from('transactionPetHotelTreatmentProducts as rc')
            ->join('products as p', 'p.id', '=', 'rc.productId')
            ->join('productLocations as pl', 'p.id', '=', 'pl.productId')
            ->select(
                'p.id as productId',
                'p.fullName as productName',
                DB::raw("CASE WHEN p.category = 'sell' THEN 'Produk Jual' WHEN p.category = 'clinic' THEN 'Produk Klinik' END as productType"),
                DB::raw("TRIM(rc.quantity)+0 AS quantity"),
                DB::raw("TRIM(p.price)+0 AS basedPrice")
            )
            ->where('rc.transactionId', '=', $request->transactionId)
            ->where('pl.locationId', '=', $trans->locationId)
            ->get();

        $data = [
            'services' => $dataServices,
            'products' => $dataProducts,
        ];

        $date = Carbon::parse($trans->endDate);
        $formatted = $date->locale('id')->isoFormat('dddd, D MMMM YYYY');

        return response()->json([
            'customerName' => $cust ? $cust->firstName : '',
            'phoneNumber' => $phone ? $phone->phoneNumber : '',
            'arrivalTime' => $trans->created_at->locale('id')->translatedFormat('l, j F Y H:i'),
            'finishTime' => $formatted,
            'cage' => $cages,
            'data' => $data,
        ]);
    }

    protected function ensureIsArray(mixed $data): ?array
    {
        // Jika data sudah berupa array, kembalikan saja.
        if (is_array($data)) {
            return $data;
        }

        // Jika data berupa string (kemungkinan JSON), coba decode.
        if (is_string($data)) {
            $decoded = json_decode($data, true);

            // Pastikan hasil decode adalah array yang valid
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Kembalikan null atau array kosong jika input tidak valid
        return null;
    }

    public function checkPromo(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'transactionId' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return responseInvalid($errors);
        }

        $trans = TransactionPetHotel::find($request->transactionId);

        if (!$trans) {
            return responseInvalid(['Transaction not found!']);
        }

        $custGroup = "";

        if (!is_null($trans->customerId)) {
            $cust = Customer::find($trans->customerId);
            $custGroup = $cust->customerGroupId;
        }

        $dataServices = $this->ensureIsArray($request->services) ?? [];
        $dataProducts = $this->ensureIsArray($request->products) ?? [];

        $tempFree = [];
        $tempDiscount = [];
        $resultBundle = [];

        $productIds = array_column($dataProducts, 'productId');
        $serviceIds = array_column($dataServices, 'serviceId');

        $now = Carbon::now();

        if (!empty($productIds)) {
            // Free product
            $resFree = DB::table('promotionMasters as pm')
                ->leftjoin('promotionCustomerGroups as pcg', 'pm.id', 'pcg.promoMasterId')
                ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
                ->join('promotionFreeItems as fi', 'pm.id', 'fi.promoMasterId')
                ->join('products as pbuy', 'pbuy.id', 'fi.productBuyId')
                ->join('products as pfree', 'pfree.id', 'fi.productFreeId')
                ->select(
                    'pm.id',
                    'pm.name',
                    DB::raw("CONCAT('Pembelian ', fi.quantityBuyItem, ' ',pbuy.fullName,' gratis ',fi.quantityFreeItem,' ',pfree.fullName) as note")
                )
                ->where('pl.locationId', '=', $trans->locationId)
                ->whereIn('fi.productBuyId', $productIds)
                ->where('pcg.customerGroupId', '=', $custGroup)
                ->where('pm.startDate', '<=', $now)
                ->where('pm.endDate', '>=', $now)
                ->where('pm.status', '=', 1)
                ->get()
                ->toArray();

            $tempFree = array_merge($tempFree, $resFree);

            // Discount products
            $resDiscProd = DB::table('promotionMasters as pm')
                ->leftjoin('promotionCustomerGroups as pcg', 'pm.id', 'pcg.promoMasterId')
                ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
                ->join('promotion_discount_products as pd', 'pm.id', 'pd.promoMasterId')
                ->join('products as p', 'p.id', 'pd.productId')
                ->select(
                    'pm.id',
                    'pm.name',
                    DB::raw("
                            CONCAT(
                                'Pembelian Produk ',
                                p.fullName,
                                CASE
                                    WHEN pd.discountType = 'percent' THEN CONCAT(' diskon ', pd.percent, '%')
                                    WHEN pd.discountType = 'amount' THEN CONCAT(' diskon Rp ', pd.amount)
                                    ELSE ''
                                END
                            ) as note
                        ")
                )
                ->where('pl.locationId', '=', $trans->locationId)
                ->whereIn('pd.productId', $productIds)
                ->where('pcg.customerGroupId', '=', $custGroup)
                ->where('pm.startDate', '<=', $now)
                ->where('pm.endDate', '>=', $now)
                ->where('pm.status', '=', 1)
                ->get()
                ->toArray();

            $tempDiscount = array_merge($tempDiscount, $resDiscProd);

            // Bundle products
            $resBundleProd = DB::table('promotionMasters as pm')
                ->leftjoin('promotionCustomerGroups as pcg', 'pm.id', 'pcg.promoMasterId')
                ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
                ->join('promotionBundles as pb', 'pm.id', 'pb.promoMasterId')
                ->join('promotion_bundle_detail_products as pbd', 'pb.id', 'pbd.promoBundleId')
                ->join('products as p', 'p.id', 'pbd.productId')
                ->select(
                    'pbd.promoBundleId',
                    'pm.name',
                )
                ->where('pl.locationId', '=', $trans->locationId)
                ->whereIn('pbd.productId', $productIds)
                ->where('pcg.customerGroupId', '=', $custGroup)
                ->where('pm.startDate', '<=', $now)
                ->where('pm.endDate', '>=', $now)
                ->where('pm.status', '=', 1)
                ->get()
                ->unique('promoBundleId');

            foreach ($resBundleProd as $valdtl) {
                $data = DB::table('promotion_bundle_detail_products as b')
                    ->join('products as p', 'p.id', 'b.productId')
                    ->join('promotionBundles as pb', 'pb.id', 'b.promoBundleId')
                    ->join('promotionMasters as m', 'pb.promoMasterId', 'm.id')
                    ->select('pb.id', 'p.fullName', 'b.quantity', 'pb.price', 'm.name')
                    ->where('b.promoBundleId', '=', $valdtl->promoBundleId)
                    ->get();

                $kalimat = 'paket bundling produk ';
                for ($i = 0; $i < count($data); $i++) {
                    if (count($data) == 1) {
                        $kalimat .= $data[$i]->quantity . ' ' . $data[$i]->fullName;
                    } else {
                        if ($i == count($data) - 1) {
                            $kalimat .= 'dan ' . $data[$i]->quantity . ' ' . $data[$i]->fullName;
                        } else {
                            $kalimat .= $data[$i]->quantity . ' ' . $data[$i]->fullName . ', ';
                        }
                    }
                }
                $kalimat .= ' sebesar Rp ' . $data[0]->price;
                $resultBundle[] = [
                    'id' => $data[0]->id,
                    'note' => $kalimat,
                    'name' => $data[0]->name
                ];
            }
        }

        if (!empty($serviceIds)) {
            // Discount services
            $resDiscServ = DB::table('promotionMasters as pm')
                ->leftjoin('promotionCustomerGroups as pcg', 'pm.id', 'pcg.promoMasterId')
                ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
                ->join('promotion_discount_services as pd', 'pm.id', 'pd.promoMasterId')
                ->join('products as p', 'p.id', 'pd.serviceId')
                ->select(
                    'pm.id',
                    'pm.name',
                    DB::raw("
                            CONCAT(
                                'Pembelian Produk ',
                                p.fullName,
                                CASE
                                    WHEN pd.discountType = 'percent' THEN CONCAT(' diskon ', pd.percent, '%')
                                    WHEN pd.discountType = 'amount' THEN CONCAT(' diskon Rp ', pd.amount)
                                    ELSE ''
                                END
                            ) as note
                        ")
                )
                ->where('pl.locationId', '=', $trans->locationId)
                ->whereIn('pd.serviceId', $serviceIds)
                ->where('pcg.customerGroupId', '=', $custGroup)
                ->where('pm.startDate', '<=', $now)
                ->where('pm.endDate', '>=', $now)
                ->where('pm.status', '=', 1)
                ->get()
                ->toArray();

            $tempDiscount = array_merge($tempDiscount, $resDiscServ);

            // Bundle services
            $resBundleServ = DB::table('promotionMasters as pm')
                ->leftjoin('promotionCustomerGroups as pcg', 'pm.id', 'pcg.promoMasterId')
                ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
                ->join('promotionBundles as pb', 'pm.id', 'pb.promoMasterId')
                ->join('promotion_bundle_detail_services as pbd', 'pb.id', 'pbd.promoBundleId')
                ->join('products as p', 'p.id', 'pbd.serviceId')
                ->select(
                    'pbd.promoBundleId',
                    'pm.name',
                )
                ->where('pl.locationId', '=', $trans->locationId)
                ->whereIn('pbd.serviceId', $serviceIds)
                ->where('pcg.customerGroupId', '=', $custGroup)
                ->where('pm.startDate', '<=', $now)
                ->where('pm.endDate', '>=', $now)
                ->where('pm.status', '=', 1)
                ->get()
                ->unique('promoBundleId');

            foreach ($resBundleServ as $valdtl) {
                $data = DB::table('promotion_bundle_detail_services as b')
                    ->join('products as p', 'p.id', 'b.serviceId')
                    ->join('promotionBundles as pb', 'pb.id', 'b.promoBundleId')
                    ->join('promotionMasters as m', 'pb.promoMasterId', 'm.id')
                    ->select('pb.id', 'p.fullName', 'b.quantity', 'pb.price', 'm.name')
                    ->where('b.promoBundleId', '=', $valdtl->promoBundleId)
                    ->get();

                $kalimat = 'paket bundling layanan ';
                for ($i = 0; $i < count($data); $i++) {
                    if (count($data) == 1) {
                        $kalimat .= $data[$i]->quantity . ' ' . $data[$i]->fullName;
                    } else {
                        if ($i == count($data) - 1) {
                            $kalimat .= 'dan ' . $data[$i]->quantity . ' ' . $data[$i]->fullName;
                        } else {
                            $kalimat .= $data[$i]->quantity . ' ' . $data[$i]->fullName . ', ';
                        }
                    }
                }
                $kalimat .= ' sebesar Rp ' . $data[0]->price;
                $resultBundle[] = [
                    'id' => $data[0]->id,
                    'note' => $kalimat,
                    'name' => $data[0]->name
                ];
            }
        }

        $resultBasedSales = [];

        $totalTransaction = 0;

        foreach ($dataServices as $value) {
            $totalTransaction += (float) ($value['priceOverall'] ?? 0);
        }

        foreach ($dataProducts as $value) {
            $totalTransaction += (float) ($value['priceOverall'] ?? 0);
        }

        $findBasedSales = DB::table('promotionMasters as pm')
            ->leftjoin('promotionCustomerGroups as pcg', 'pm.id', 'pcg.promoMasterId')
            ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
            ->join('promotionBasedSales as bs', 'pm.id', 'bs.promoMasterId')
            ->select('pm.id', 'pm.name', 'bs.percentOrAmount', 'bs.percent', 'bs.amount', 'bs.minPurchase', 'bs.maxPurchase')
            ->where('pl.locationId', '=', $trans->locationId)
            ->where('bs.minPurchase', '<=', $totalTransaction)
            ->where('bs.maxPurchase', '>=', $totalTransaction)
            ->where('pcg.customerGroupId', '=', $custGroup)
            ->where('pm.startDate', '<=', Carbon::now())
            ->where('pm.endDate', '>=', Carbon::now())
            ->where('pm.status', '=', 1)
            ->get();

        $text = "";

        foreach ($findBasedSales as $sale) {

            if ($sale->percentOrAmount == 'percent') {
                $text = 'Diskon ' . $sale->percent . ' % setiap pembelian minimal Rp ' . $sale->minPurchase;
            } elseif ($sale->percentOrAmount == 'amount') {
                $text = 'Potongan harga sebesar Rp ' . $sale->amount . ' setiap pembelian minimal Rp ' . $sale->minPurchase;
            }

            $resultBasedSales[] = [
                'id' => $sale->id,
                'note' => $text,
                'name' => $sale->name
            ];

            $text = "";
        }

        $result = [
            'freeItems'  => $tempFree,
            'discounts'  => $tempDiscount,
            'bundles'    => $resultBundle,
            'basedSales' => $resultBasedSales,
        ];

        return response()->json($result);
    }

    public function transactionDiscount(Request $request)
    {
        $services = $this->ensureIsArray($request->services) ?? [];
        $products = $this->ensureIsArray($request->products) ?? [];
        $freeItems = $this->ensureIsArray($request->freeItems) ?? [];
        $discounts = $this->ensureIsArray($request->discounts) ?? [];
        $bundles = $this->ensureIsArray($request->bundles) ?? [];

        $results = [];
        $promoNotes = [];
        $subtotal = 0;
        $totalDiscount = 0;

        $trans = TransactionPetHotel::find($request->transactionId);
        if (!$trans) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        // --- PRE-FETCH PROMOTIONS FOR BATCH PROCESSING ---
        $promoServiceDiscounts = [];
        if (!empty($discounts) && !empty($services)) {
            $serviceIds = array_column($services, 'serviceId');
            $data = DB::table('promotionMasters as pm')
                ->join('promotion_discount_services as pd', 'pm.id', 'pd.promoMasterId')
                ->join('services as s', 's.id', 'pd.serviceId')
                ->join('serviceCategory as sc', 's.type', 'sc.id')
                ->select(
                    'pm.id as promoId',
                    's.id as serviceId',
                    's.fullName as item_name',
                    's.type as category',
                    'pd.discountType',
                    'pd.percent',
                    'pd.amount'
                )
                ->whereIn('pm.id', $discounts)
                ->whereIn('pd.serviceId', $serviceIds)
                ->get();
            foreach ($data as $d) {
                $promoServiceDiscounts[$d->serviceId][$d->promoId] = $d;
            }
        }

        $promoServiceBundles = [];
        if (!empty($bundles) && !empty($services)) {
            $serviceIds = array_column($services, 'serviceId');
            $data = DB::table('promotionMasters as pm')
                ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
                ->join('promotionBundles as pb', 'pm.id', 'pb.promoMasterId')
                ->join('promotion_bundle_detail_services as pbd', 'pb.id', 'pbd.promoBundleId')
                ->select('pm.id as promoId', 'pm.name as item_name', 'pb.price as total', 'pb.id as promoBundleId', 'pbd.serviceId')
                ->whereIn('pm.id', $bundles)
                ->whereIn('pbd.serviceId', $serviceIds)
                ->where('pl.locationId', '=', $trans->locationId)
                ->get();

            foreach ($data as $d) {
                $includedItems = DB::table('promotion_bundle_detail_services as pbd')
                    ->join('services as s', 's.id', '=', 'pbd.serviceId')
                    ->join('servicesPrice as sp', 'sp.serviceId', '=', 's.id')
                    ->where('pbd.promoBundleId', '=', $d->promoBundleId)
                    ->where('sp.location_id', '=', $trans->locationId)
                    ->select('s.id as serviceId', 's.fullName as name', 'sp.price as normal_price')
                    ->get()
                    ->toArray();
                $d->included_items = $includedItems;
                $promoServiceBundles[$d->serviceId][$d->promoId] = $d;
            }
        }

        $promoProductFreeItems = [];
        if (!empty($freeItems) && !empty($products)) {
            $productIds = array_column($products, 'productId');
            $data = DB::table('promotionMasters as pm')
                ->join('promotionFreeItems as fi', 'pm.id', 'fi.promoMasterId')
                ->join('products as pbuy', 'pbuy.id', 'fi.productBuyId')
                ->join('products as pfree', 'pfree.id', 'fi.productFreeId')
                ->select(
                    'pm.id as promoId',
                    'pbuy.fullName as item_name',
                    'pbuy.id as buy_product_id',
                    'pfree.id as free_product_id',
                    'pbuy.category',
                    'fi.quantityBuyItem',
                    'fi.quantityFreeItem',
                    'pfree.fullName as free_product_name'
                )
                ->whereIn('pm.id', $freeItems)
                ->whereIn('pbuy.id', $productIds)
                ->get();
            foreach ($data as $d) {
                $promoProductFreeItems[$d->buy_product_id][$d->promoId] = $d;
            }
        }

        $promoProductBundles = [];
        if (!empty($bundles) && !empty($products)) {
            $productIds = array_column($products, 'productId');
            $data = DB::table('promotionMasters as pm')
                ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
                ->join('promotionBundles as pb', 'pm.id', 'pb.promoMasterId')
                ->join('promotion_bundle_detail_products as pbd', 'pb.id', 'pbd.promoBundleId')
                ->select('pm.id as promoId', 'pm.name as item_name', 'pb.price as total', 'pb.id as promoBundleId', 'pbd.productId')
                ->whereIn('pm.id', $bundles)
                ->whereIn('pbd.productId', $productIds)
                ->where('pl.locationId', '=', $trans->locationId)
                ->get();

            foreach ($data as $d) {
                $includedItems = DB::table('promotion_bundle_detail_products as pbd')
                    ->join('products as p', 'p.id', '=', 'pbd.productId')
                    ->where('pbd.promoBundleId', '=', $d->promoBundleId)
                    ->select('p.id as productId', 'p.fullName as name', 'p.price as normal_price')
                    ->get()
                    ->toArray();
                $d->included_items = $includedItems;
                $promoProductBundles[$d->productId][$d->promoId] = $d;
            }
        }

        $promoProductDiscounts = [];
        if (!empty($discounts) && !empty($products)) {
            $productIds = array_column($products, 'productId');
            $data = DB::table('promotionMasters as pm')
                ->join('promotion_discount_products as pd', 'pm.id', 'pd.promoMasterId')
                ->join('products as p', 'p.id', 'pd.productId')
                ->select(
                    'pm.id as promoId',
                    'p.id as productId',
                    'p.fullName as item_name',
                    'p.category',
                    'pd.discountType',
                    'pd.percent',
                    'pd.amount'
                )
                ->whereIn('pm.id', $discounts)
                ->whereIn('pd.productId', $productIds)
                ->get();
            foreach ($data as $d) {
                $promoProductDiscounts[$d->productId][$d->promoId] = $d;
            }
        }

        // --- PROCESSING SERVICES ---
        foreach ($services as $value) {
            // Skip item tanpa serviceId (misal: additional treatment yang belum termap)
            if (empty($value['serviceId'])) {
                $results[] = [
                    'item_name'  => $value['name'] ?? '-',
                    'category'   => '-',
                    'quantity'   => $value['quantity'] ?? 1,
                    'bonus'      => 0,
                    'discount'   => 0,
                    'unit_price' => $value['eachPrice'] ?? 0,
                    'total'      => $value['priceOverall'] ?? 0,
                ];
                $subtotal += $value['priceOverall'] ?? 0;
                continue;
            }

            $isGetPromo = false;

            if ($request->has('discounts')) {
                foreach ($discounts as $disc) {
                    if (isset($promoServiceDiscounts[$value['serviceId']][$disc])) {
                        $data = $promoServiceDiscounts[$value['serviceId']][$disc];

                        if ($data->discountType === 'percent') {
                            $amount_discount = ($data->percent / 100) * $value['eachPrice'];
                            $discountNote = 'Diskon produk ' . $data->item_name . ' sebesar ' . $data->percent . '% (hemat Rp' . number_format($amount_discount, 0, ',', '.') . ')';
                            $saved = $amount_discount;
                        } else {
                            $discountNote = 'Diskon produk ' . $data->item_name . ' sebesar Rp' . number_format($data->amount, 0, ',', '.');
                            $saved = $data->amount;
                        }

                        $results[] = [
                            'item_name' => $data->item_name,
                            'category' => $data->category,
                            'quantity' => $value['quantity'],
                            'bonus' => 0,
                            'discount' => ($data->discountType === 'percent') ? $data->percent : $data->amount,
                            'unit_price' => $value['eachPrice'],
                            'total' => $value['priceOverall'] - $saved,
                            'promoId' => $data->promoId,
                            'serviceId' => $data->serviceId,
                            'promoCategory' => 'discount',
                        ];

                        $subtotal += ($value['priceOverall'] - $saved);
                        $totalDiscount += $saved;
                        $promoNotes[] = $discountNote;
                        $isGetPromo = true;
                    }
                }
            }

            if ($request->has('bundles')) {
                foreach ($bundles as $bundle) {
                    if (isset($promoServiceBundles[$value['serviceId']][$bundle])) {
                        $bundleData = $promoServiceBundles[$value['serviceId']][$bundle];

                        $normalTotal = array_sum(array_column($bundleData->included_items, 'normal_price'));
                        $bundleNote = $bundleData->item_name . " only Rp" . number_format($bundleData->total, 0, ',', '.') .
                            " (save Rp" . number_format($normalTotal - $bundleData->total, 0, ',', '.') . ")";

                        $results[] = [
                            'item_name' => $bundleData->item_name,
                            'category' => "",
                            'quantity' => 1,
                            'bonus' => 0,
                            'discount' => 0,
                            'total' => $bundleData->total,
                            'included_items' => $bundleData->included_items,
                            'promoId' => $bundleData->promoId,
                            'promoCategory' => 'bundle',
                        ];

                        $subtotal += $bundleData->total;
                        $promoNotes[] = $bundleNote;
                        $isGetPromo = true;
                    }
                }
            }

            if (!$isGetPromo) {
                $res = DB::table('services as p')
                    ->join('serviceCategory as sc', 'p.type', 'sc.id')
                    ->select(
                        DB::raw('NULL as promoId'),
                        'p.id as serviceId',
                        'p.fullName as item_name',
                        'sc.categoryName as category',
                        DB::raw($value['quantity'] . ' as quantity'),
                        DB::raw('0 as bonus'),
                        DB::raw('0 as discount'),
                        DB::raw($value['eachPrice'] . ' as unit_price'),
                        DB::raw($value['priceOverall'] . ' as total'),
                        DB::raw("'' as note")
                    )
                    ->where('p.id', '=', $value['serviceId'])
                    ->get();

                foreach ($res as $item) {
                    $results[] = (array)$item;
                    $subtotal += $item->total;
                }
            }
        }

        // --- PROCESSING PRODUCTS ---
        foreach ($products as $value) {
            $isGetPromo = false;

            if ($request->has('freeItems')) {
                foreach ($freeItems as $free) {
                    if (isset($promoProductFreeItems[$value['productId']][$free])) {
                        $data = $promoProductFreeItems[$value['productId']][$free];

                        $note = 'Beli ' . $data->quantityBuyItem . ' ' . $data->item_name . ' Gratis ' . $data->quantityFreeItem . ' ' . $data->free_product_name;

                        $results[] = [
                            'promoId' => $data->promoId,
                            'item_name' => $data->item_name,
                            'buy_product_id' => $data->buy_product_id,
                            'free_product_id' => $data->free_product_id,
                            'category' => $data->category,
                            'quantity' => $data->quantityBuyItem,
                            'bonus' => $data->quantityFreeItem,
                            'discount' => 0,
                            'unit_price' => $value['eachPrice'],
                            'total' => $value['priceOverall'],
                            'note' => $note,
                            'promoCategory' => 'freeItem',
                        ];

                        $subtotal += $value['priceOverall'];
                        $promoNotes[] = $note;
                        $isGetPromo = true;
                    }
                }
            }

            if ($request->has('bundles')) {
                foreach ($bundles as $bundle) {
                    if (isset($promoProductBundles[$value['productId']][$bundle])) {
                        $bundleData = $promoProductBundles[$value['productId']][$bundle];

                        $normalTotal = array_sum(array_column($bundleData->included_items, 'normal_price'));
                        $bundleNote = $bundleData->item_name . " only Rp" . number_format($bundleData->total, 0, ',', '.') .
                            " (save Rp" . number_format($normalTotal - $bundleData->total, 0, ',', '.') . ")";

                        $results[] = [
                            'item_name' => $bundleData->item_name,
                            'category' => "",
                            'quantity' => 1,
                            'bonus' => 0,
                            'discount' => 0,
                            'total' => $bundleData->total,
                            'included_items' => $bundleData->included_items,
                            'promoId' => $bundleData->promoId,
                            'promoCategory' => 'bundle',
                        ];

                        $subtotal += $bundleData->total;
                        $promoNotes[] = $bundleNote;
                        $isGetPromo = true;
                    }
                }
            }

            if ($request->has('discounts')) {
                foreach ($discounts as $disc) {
                    if (isset($promoProductDiscounts[$value['productId']][$disc])) {
                        $data = $promoProductDiscounts[$value['productId']][$disc];

                        if ($data->discountType === 'percent') {
                            $amount_discount = ($data->percent / 100) * $value['eachPrice'];
                            $discountNote = 'Diskon produk ' . $data->item_name . ' sebesar ' . $data->percent . '% (hemat Rp' . number_format($amount_discount, 0, ',', '.') . ')';
                            $saved = $amount_discount;
                        } else {
                            $discountNote = 'Diskon produk ' . $data->item_name . ' sebesar Rp' . number_format($data->amount, 0, ',', '.');
                            $saved = $data->amount * $value['quantity'];
                        }

                        $existingIdx = collect($results)->search(function ($item) use ($data) {
                            return $item['item_name'] === $data->item_name && isset($item['promoCategory']) && $item['promoCategory'] == 'discount';
                        });

                        if ($existingIdx === false) {
                            $results[] = [
                                'item_name' => $data->item_name,
                                'category' => $data->category,
                                'quantity' => $value['quantity'],
                                'bonus' => 0,
                                'discountType' => $data->discountType,
                                'discount' => ($data->discountType === 'percent') ? $data->percent : $data->amount,
                                'total' => $value['priceOverall'] - $saved,
                                'note' => $discountNote,
                                'promoId' => $data->promoId,
                                'productId' => $data->productId,
                                'promoCategory' => 'discount',
                            ];

                            $subtotal += ($value['priceOverall'] - $saved);
                            $totalDiscount += $saved;
                            $promoNotes[] = $discountNote;
                        }
                        $isGetPromo = true;
                    }
                }
            }

            if (!$isGetPromo) {
                $res = DB::table('products as p')
                    ->select(
                        'p.id as productId',
                        DB::raw('NULL as promoId'),
                        'p.fullName as item_name',
                        'p.category',
                        DB::raw($value['quantity'] . ' as quantity'),
                        DB::raw('0 as bonus'),
                        DB::raw('0 as discount'),
                        DB::raw($value['eachPrice'] . ' as unit_price'),
                        DB::raw($value['priceOverall'] . ' as total'),
                        DB::raw("'' as note")
                    )
                    ->where('p.id', '=', $value['productId'])
                    ->get();

                foreach ($res as $item) {
                    $results[] = (array)$item;
                    $subtotal += $item->total;
                }
            }
        }

        $discount_based_sales = 0;
        $discountNote = '';
        if ($request->basedSale) {
            $res = DB::table('promotionMasters as pm')
                ->join('promotionBasedSales as pb', 'pm.id', 'pb.promoMasterId')
                ->select(
                    'pm.name',
                    'pb.minPurchase',
                    'pb.maxPurchase',
                    DB::raw("CASE WHEN percentOrAmount = 'amount' THEN 'amount' WHEN percentOrAmount = 'percent' THEN 'percent' ELSE '' END as discountType"),
                    DB::raw("CASE WHEN percentOrAmount = 'amount' THEN amount WHEN percentOrAmount = 'percent' THEN percent ELSE 0 END as totaldiscount")
                )
                ->where('pm.id', '=', $request->basedSale)
                ->where('minPurchase', '<=', $subtotal)
                ->where(function ($q) use ($subtotal) {
                    // maxPurchase NULL berarti tidak ada batas atas
                    $q->whereNull('pb.maxPurchase')->orWhere('pb.maxPurchase', '>=', $subtotal);
                })
                ->first();

            if ($res) {
                if ($res->discountType == 'amount') {
                    $discount_based_sales = (float) $res->totaldiscount;
                    $promoNotes[] = 'Diskon Rp ' . number_format($discount_based_sales, 0, ',', '.') . ' untuk pembelian lebih dari Rp ' . number_format($res->minPurchase, 0, ',', '.');
                    $discountNote = 'Diskon Nominal (Belanja > Rp ' . number_format($res->minPurchase, 0, ',', '.') . ')';
                    $totalDiscount += $discount_based_sales;
                } else if ($res->discountType == 'percent') {
                    $discount_based_sales = $subtotal * ((float) $res->totaldiscount / 100);
                    $promoNotes[] = 'Diskon ' . $res->totaldiscount . '% untuk pembelian lebih dari Rp ' . number_format($res->minPurchase, 0, ',', '.');
                    $discountNote = 'Diskon ' . $res->totaldiscount . '% (Belanja > Rp ' . number_format($res->minPurchase, 0, ',', '.') . ')';
                    $totalDiscount += $discount_based_sales; // pakai nilai nominal, bukan persen
                }
            }
        }

        $response = [
            'purchases'            => $results,
            'subtotal'             => floatval($subtotal),
            'discount_note'        => $discountNote,
            'discount_based_sales' => floatval($discount_based_sales),
            'total_discount'       => floatval($totalDiscount),
            'total_payment'        => floatval(max(0, $subtotal - $totalDiscount)),
            'promo_notes'          => $promoNotes,
        ];

        if ($request->basedSale) {
            $response['promoBasedSaleId'] = $request->basedSale;
        }

        return response()->json($response);
    }
    public function payment(Request $request)
    {
        $access = $this->getUserAccessInfo($request);
        if (!$access['isAdminOrManager'] && $access['jobTitleId'] !== 1) {
            return $this->accessDenied('Hanya Kasir, Manager, atau Administrator yang dapat memproses pembayaran.');
        }

        $purchases = $this->ensureIsArray($request->purchases);
        //$json_string = $request->payment_method;
        $payment = $this->ensureIsArray($request->payment_method);

        $validate = Validator::make($request->all(), [
            'transactionId' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return responseInvalid($errors);
        }

        $trans = TransactionPetHotel::find($request->transactionId);
        if (!$trans) {
            return responseInvalid(['Transaction not found!']);
        }

        try {
            DB::beginTransaction();

            foreach ($purchases as $value) {

                if (array_key_exists('serviceId', $value)) {

                    if ($value['promoId'] != null) {

                        $promo = PromotionMaster::find($value['promoId']);
                        if (!$promo) {
                            DB::rollBack();
                            return responseInvalid(['Promotion not found!']);
                        }

                        //promo free item
                        if ($promo->type == 2) {

                            $trx = new transaction_pet_hotel_payments();
                            $trx->transactionId = $request->transactionId;
                            $trx->paymentMethodId = $payment['paymentId'];
                            $trx->promoId = $promo->id;
                            $trx->serviceId = $value['serviceId'];
                            $trx->quantity = $value['quantity'];
                            $trx->discountType = $value['discountType'];
                            if ($value['discountType'] == 'percent') {
                                $trx->discountPercent = $value['discount'];
                            } else {
                                $trx->discountAmount = $value['discount'];
                            }
                            $trx->userId = $request->user()->id;
                            $trx->save();
                        }
                    } else {
                        $trx = new transaction_pet_hotel_payments();
                        $trx->transactionId = $request->transactionId;
                        $trx->paymentMethodId = $payment['paymentId'];
                        $trx->serviceId = $value['serviceId'];
                        $trx->quantity = $value['quantity'];
                        $trx->price = $value['unit_price'];
                        $trx->priceOverall = $value['total'];
                        $trx->userId = $request->user()->id;
                        $trx->save();
                    }
                } else if (array_key_exists('productId', $value)) {

                    if ($value['promoId'] != null) {

                        $promo = PromotionMaster::find($value['promoId']);
                        if (!$promo) {
                            DB::rollBack();
                            return responseInvalid(['Promotion not found!']);
                        }

                        if ($promo->type == 2) {

                            $trx = new transaction_pet_hotel_payments();
                            $trx->transactionId = $request->transactionId;
                            $trx->paymentMethodId = $payment['paymentId'];
                            $trx->promoId = $promo->id;
                            $trx->productId = $value['productId'];
                            $trx->quantity = $value['quantity'];
                            $trx->discountType = $value['discountType'];
                            if ($value['discountType'] == 'percent') {
                                $trx->discountPercent = $value['discount'];
                                $trx->discountAmount = 0;
                            } else {
                                $trx->discountAmount = $value['discount'];
                                $trx->discountPercent = 0;
                            }
                            $trx->price = $value['unit_price'];
                            $trx->priceOverall = $value['total'];
                            $trx->userId = $request->user()->id;
                            $trx->save();
                        } elseif ($promo->type == 3) {
                            //bundle

                        }
                    } else {
                        $trx = new transaction_pet_hotel_payments();
                        $trx->transactionId = $request->transactionId;
                        $trx->paymentMethodId = $payment['paymentId'];
                        $trx->productId = $value['productId'];
                        $trx->quantity = $value['quantity'];
                        $trx->price = $value['unit_price'];
                        $trx->priceOverall = $value['total'];
                        $trx->userId = $request->user()->id;
                        $trx->save();
                    }
                } else if (array_key_exists('buy_product_id', $value)) {

                    $promo = PromotionMaster::find($value['promoId']);
                    if (!$promo) {
                        DB::rollBack();
                        return responseInvalid(['Promotion not found!']);
                    }

                    $trx = new transaction_pet_hotel_payments();
                    $trx->transactionId = $request->transactionId;
                    $trx->paymentMethodId = $payment['paymentId'];
                    $trx->promoId = $promo->id;
                    $trx->productBuyId = $value['buy_product_id'];
                    $trx->productFreeId = $value['free_product_id'];
                    $trx->quantity = $value['quantity'] + $value['bonus'];
                    $trx->quantityBuy = $value['quantity'];
                    $trx->quantityFree = $value['bonus'];
                    $trx->price = $value['unit_price'];
                    $trx->priceOverall = $value['total'];
                    $trx->userId = $request->user()->id;
                    $trx->save();
                } else if ($value['promoId'] != 'null' && $value['promoCategory'] == 'bundle') {

                    //bundle
                    $promo = PromotionMaster::find($value['promoId']);
                    if (!$promo) {
                        DB::rollBack();
                        return responseInvalid(['Promotion not found!']);
                    }

                    $trx = new transaction_pet_hotel_payments();
                    $trx->transactionId = $request->transactionId;
                    $trx->paymentMethodId = $payment['paymentId'];
                    $trx->promoId = $promo->id;
                    $trx->price = $value['unit_price'];
                    $trx->priceOverall = $value['total'];
                    $trx->isBundle = true;
                    $trx->userId = $request->user()->id;
                    $trx->save();

                    // $amountBundling = $value['total'];
                    // $amountTotal = 0;

                    // foreach ($value['included_items'] as $item) {
                    //     $amountTotal += $item['unit_price'];
                    // }

                    // $normalPriceRatio = $amountBundling / $amountTotal;

                    foreach ($value['included_items'] as $item) {
                        if (array_key_exists('serviceId', $item)) {

                            $bundle = new transaction_pet_hotel_payment_bundle();
                            $bundle->paymentId = $trx->id;
                            $bundle->promoId = $promo->id;
                            $bundle->serviceId = $item['serviceId'];
                            $bundle->quantity = $item['quantity'];
                            $bundle->amount = $item['unit_price'];
                            //* $normalPriceRatio;
                            //$bundle->priceOverall = $item['quantity'] * ($item['unit_price'] * $normalPriceRatio);
                            $bundle->userId = $request->user()->id;
                            $bundle->save();
                        } else if (array_key_exists('productId', $item)) {

                            $bundle = new transaction_pet_hotel_payment_bundle();
                            $bundle->paymentId = $trx->id;
                            $bundle->promoId = $promo->id;
                            $bundle->productId = $item['productId'];
                            $bundle->quantity = $item['quantity'];
                            $bundle->amount = $item['unit_price'];
                            //* $normalPriceRatio;
                            //$bundle->priceOverall = $item['quantity'] * ($item['unit_price'] * $normalPriceRatio);
                            $bundle->userId = $request->user()->id;
                            $bundle->save();
                        }
                    }
                }
            }

            $detail = $this->ensureIsArray($request->detail_total);

            if (array_key_exists('promoBasedSaleId', $detail) && !is_null($detail['promoBasedSaleId'])) {

                $promo = PromotionMaster::find($detail['promoBasedSaleId']);
                if (!$promo) {
                    DB::rollBack();
                    return responseInvalid(['Promotion based sales not found!']);
                }

                $sales = new transaction_pet_hotel_payment_based_sales();
                $sales->transactionId = $request->transactionId;
                $sales->paymentMethodId = $payment['paymentId'];
                $sales->promoId = $detail['promoBasedSaleId'];
                $sales->amountDiscount = $detail['discount_based_sales'];
                $sales->userId = $request->user()->id;
                $sales->save();
            }

            //detail total
            $total = new transaction_pet_hotel_payment_total();
            $total->transactionId = $request->transactionId;
            $total->paymentmethodId = $payment['paymentId'];
            $total->amount = $detail['total_payment'];
            $total->amountPaid = $payment['amountPaid'];

            if (array_key_exists('next_payment', $payment)) {
                $total->nextPayment = $payment['next_payment'];
            }

            if (array_key_exists('duration', $payment)) {
                $total->duration = $payment['duration'];
                $total->tenor = $payment['tenor'];
            }

            $locationId = $trans->locationId;

            $now = Carbon::now();
            $tahun = $now->format('Y');
            $bulan = $now->format('m');

            $jumlahTransaksi = DB::table('transaction_pet_hotel_payment_totals as tp')
                ->join('transaction_pet_hotels as tpc', 'tp.transactionId', '=', 'tpc.id')
                ->where('tpc.locationId', $locationId)
                ->whereYear('tp.created_at', $tahun)
                ->whereMonth('tp.created_at', $bulan)
                ->count();

            $nomorUrut = str_pad($jumlahTransaksi + 1, 4, '0', STR_PAD_LEFT);

            $notaNumber = "INV/PH/{$locationId}/{$tahun}/{$bulan}/{$nomorUrut}";
            $total->nota_number = $notaNumber;

            $total->userId = $request->user()->id;
            $total->save();

            transactionPetHotelLog($request->transactionId, 'Nota diterbitkan', '', $request->user()->id);

            statusTransactionPetHotel($request->transactionId, 'Menunggu konfirmasi pembayaran', $request->user()->id);
            DB::commit();

            updateLastTransaction($trans->customerId);

            return responseCreate();
        } catch (\Throwable $th) {
            DB::rollback();
            return responseInvalid([$th->getMessage()]);
        }
    }

    public function printInvoce(Request $request)
    {
        $trans = TransactionPetHotel::find($request->transactionId);

        if (!$trans) {
            return responseInvalid(['Transaction not found!']);
        }

        $formattedLocations = $this->getActiveLocationsForInvoice();

        $customer = DB::table('customer as c')
            ->join('customerTelephones as ct', 'c.id', '=', 'ct.customerId')
            ->where('c.id', '=', $trans->customerId)
            ->select('c.firstName', 'ct.phoneNumber', 'c.memberNo')
            ->first();

        $details = $this->ensureIsArray($request->purchases);
        $namaFile = str_replace('/', '_', $trans->nota_number ?? 'INV') . '.pdf';

        $detail_total = $this->ensureIsArray($request->detail_total);

        $data = [
            'locations'      => $formattedLocations,
            'nota_date'      => Carbon::parse($trans->created_at)->format('d/m/Y'),
            'no_nota'        => $trans->nota_number ?? '___________',
            'member_no'      => $customer->memberNo ?? '-',
            'customer_name'  => $customer->firstName ?? '-',
            'phone_number'   => $customer->phoneNumber ?? '-',
            'arrival_time'   => Carbon::parse($trans->created_at)->format('H:i'),
            'details'        => $details,
            'total'          => $detail_total,
            'deposit'        => '-',
            'total_payment'  => $detail_total['total_payment'],
            'total_discount'  => $detail_total['total_discount'],
            'subtotal'  => $detail_total['subtotal'],
        ];

        $pdf = Pdf::loadView('invoice.invoice_pethotel', $data);
        return $pdf->download($namaFile);
    }

    public function confirmPayment(Request $request)
    {
        $access = $this->getUserAccessInfo($request);
        if (!$access['isAdminOrManager'] && $access['jobTitleId'] !== 1) {
            return $this->accessDenied('Hanya Kasir, Manager, atau Administrator yang dapat mengkonfirmasi pembayaran.');
        }

        $request->validate([
            'id' => 'required|integer',
            'proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048'
        ]);

        $trans_pay = transaction_pet_hotel_payment_total::find($request->id);

        if (!$trans_pay) {
            return responseInvalid(['Transaction is not found!']);
        }

        if ($trans_pay->isPayed === true) {
            return response()->json([
                'status' => 'error',
                'message' => 'Transaksi sudah dikonfirmasi sebelumnya.'
            ], 400);
        }

        if (!$request->hasFile('proof')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bukti pembayaran wajib diunggah!'
            ], 422);
        }

        $filePath = null;
        $originalName = null;
        $randomName = null;

        if ($request->hasFile('proof')) {
            $file = $request->file('proof');
            $originalName = $file->getClientOriginalName();
            $randomName = 'proof_' . $trans_pay->id . '_' . time() . '.' . $file->getClientOriginalExtension();

            if (!Storage::disk('public')->exists('Transaction/Pethotel/proof_of_payment')) {
                Storage::disk('public')->makeDirectory('Transaction/Pethotel/proof_of_payment');
            }

            $filePath = $file->storeAs('Transaction/Pethotel/proof_of_payment', $randomName, 'public');

            $trans_pay->proofOfPayment = $filePath;
            $trans_pay->originalName = $originalName;
            $trans_pay->proofRandomName = $randomName;
        }

        $trans_pay->isPayed = 1;
        $trans_pay->updated_at = now();
        $trans_pay->save();

        $trans = transaction_pet_hotel_payment_total::where('transactionId', $trans_pay->transactionId)->first();

        $total_amount = $trans->amount;
        $amount_paid = transaction_pet_hotel_payment_total::where('transactionId', $trans_pay->transactionId)->sum('amountPaid');

        // if ($amount_paid < $total_amount) {
        //     statusTransactionPetHotel($trans_pay->transactionId, 'Menunggu Pembayaran Berikutnya', $request->user()->id);
        // } else {

        // }

        DB::beginTransaction();
        try {
            statusTransactionPetHotel($trans_pay->transactionId, 'Selesai', $request->user()->id);
            transactionPetHotelLog($trans_pay->transactionId, 'Pembayaran Dikonfirmasi, Pet Resmi Keluar Hotel', '', $request->user()->id);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollback();
            return responseInvalid([$th->getMessage()]);
        }

        return responseCreate();
    }

    // ─── Fase 2: Additional Treatment & Extend Stay ─────────────────────────────

    public function getAvailableItems(Request $request)
    {
        $request->validate([
            'transactionId' => 'required|integer',
            'type'          => 'required|in:service,product,petshop,petsell,clinic',
        ]);

        $tran = TransactionPetHotel::find($request->transactionId);
        if (!$tran) {
            return responseInvalid(['Transaksi tidak ditemukan.']);
        }

        $search = $request->search;

        if (in_array($request->type, ['service', 'clinic'])) {
            // service = semua layanan di lokasi
            // clinic  = layanan medis/klinik (exclude kategori hotel & grooming)
            $hotelGroomingCategoryIds = DB::table('serviceCategory')
                ->where('isDeleted', 0)
                ->where(function ($q) {
                    $q->where('categoryName', 'like', '%Hotel%')
                      ->orWhere('categoryName', 'like', '%Grooming%')
                      ->orWhere('categoryName', 'like', '%Salon%');
                })
                ->pluck('id');

            $query = DB::table('services as s')
                ->join('servicesPrice as sp', 's.id', 'sp.service_id')
                ->select('s.id', 's.fullName as name', DB::raw("MIN(CAST(REPLACE(sp.price, ',', '') AS UNSIGNED)) as price"))
                ->where('sp.location_id', $tran->locationId)
                ->where('s.isDeleted', 0)
                ->when($search, fn($q) => $q->where('s.fullName', 'like', '%' . $search . '%'))
                ->groupBy('s.id', 's.fullName');

            if ($request->type === 'clinic') {
                // Hanya layanan yang termasuk kategori non-hotel/grooming
                $clinicServiceIds = DB::table('servicesCategoryList')
                    ->whereNotIn('category_id', $hotelGroomingCategoryIds)
                    ->where('isDeleted', 0)
                    ->pluck('service_id')
                    ->unique();
                $query->whereIn('s.id', $clinicServiceIds);
            }

            $items = $query->orderBy('s.fullName')->limit(30)->get();

        } elseif ($request->type === 'petshop') {
            // Produk yang bisa dibeli customer (petshop)
            $items = DB::table('products as p')
                ->join('productLocations as pl', 'p.id', 'pl.productId')
                ->select('p.id', 'p.fullName as name', 'p.price')
                ->where('pl.locationId', $tran->locationId)
                ->where('p.isDeleted', 0)
                ->where('p.isCustomerPurchase', 1)
                ->when($search, fn($q) => $q->where('p.fullName', 'like', '%' . $search . '%'))
                ->groupBy('p.id', 'p.fullName', 'p.price')
                ->orderBy('p.fullName')
                ->limit(30)
                ->get();

        } elseif ($request->type === 'petsell') {
            // Semua produk di lokasi (tidak harus customer purchase)
            $items = DB::table('products as p')
                ->join('productLocations as pl', 'p.id', 'pl.productId')
                ->select('p.id', 'p.fullName as name', 'p.price')
                ->where('pl.locationId', $tran->locationId)
                ->where('p.isDeleted', 0)
                ->when($search, fn($q) => $q->where('p.fullName', 'like', '%' . $search . '%'))
                ->groupBy('p.id', 'p.fullName', 'p.price')
                ->orderBy('p.fullName')
                ->limit(30)
                ->get();

        } else {
            // type=product (legacy, semua produk)
            $items = DB::table('products as p')
                ->join('productLocations as pl', 'p.id', 'pl.productId')
                ->select('p.id', 'p.fullName as name', 'p.price')
                ->where('pl.locationId', $tran->locationId)
                ->where('p.isDeleted', 0)
                ->when($search, fn($q) => $q->where('p.fullName', 'like', '%' . $search . '%'))
                ->groupBy('p.id', 'p.fullName', 'p.price')
                ->orderBy('p.fullName')
                ->limit(30)
                ->get();
        }

        return response()->json($items, 200);
    }

    public function getAdditionalTreatments(Request $request)
    {
        $access = $this->getUserAccessInfo($request);
        // Kasir (1), Dokter (17), Admin/Manager boleh melihat tambahan treatment
        if (!$access['isAdminOrManager'] && !in_array($access['jobTitleId'], [1, 17])) {
            return $this->accessDenied('Anda tidak memiliki akses untuk melihat tambahan treatment.');
        }

        $request->validate(['transactionId' => 'required|integer']);

        $data = TransactionPetHotelAdditionalTreatment::where('transactionId', $request->transactionId)
            ->with('user:id,firstName')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id'       => $item->id,
                    'itemId'   => $item->itemId,
                    'type'     => $item->type,
                    'itemName' => $item->itemName,
                    'quantity' => $item->quantity,
                    'price'    => $item->price,
                    'total'    => $item->price * $item->quantity,
                    'catatan'  => $item->catatan,
                    'addedBy'  => optional($item->user)->firstName,
                    'addedAt'  => $item->created_at->format('d-m-Y H:i'),
                ];
            });

        return response()->json($data, 200);
    }

    public function addAdditionalTreatment(Request $request)
    {
        $access = $this->getUserAccessInfo($request);
        // Kasir (1) boleh tambah item saat checkout; Dokter (17) saat treatment
        if (!$access['isAdminOrManager'] && !in_array($access['jobTitleId'], [1, 17])) {
            return $this->accessDenied('Anda tidak memiliki akses untuk menambah item.');
        }

        $validate = Validator::make($request->all(), [
            'transactionId' => 'required|integer',
            'type'          => 'required|in:service,product,petshop,petsell,clinic',
            'itemId'        => 'required|integer',
            'quantity'      => 'required|numeric|min:1',
            'catatan'       => 'nullable|string',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $tran = TransactionPetHotel::find($request->transactionId);
        if (!$tran) {
            return responseInvalid(['Transaksi tidak ditemukan.']);
        }

        if (in_array($request->type, ['service', 'clinic'])) {
            $item = DB::table('services as s')
                ->join('servicesPrice as sp', 's.id', 'sp.service_id')
                ->select('s.fullName as name', DB::raw("CAST(REPLACE(sp.price, ',', '') AS UNSIGNED) as price"))
                ->where('s.id', $request->itemId)
                ->where('sp.location_id', $tran->locationId)
                ->first();
        } else {
            // product / petshop / petsell — p.price sudah numeric "35000.00"
            $item = DB::table('products as p')
                ->select('p.fullName as name', 'p.price')
                ->where('p.id', $request->itemId)
                ->first();
        }

        if (!$item) {
            return responseInvalid(['Item tidak ditemukan.']);
        }

        TransactionPetHotelAdditionalTreatment::create([
            'transactionId' => $request->transactionId,
            'type'          => $request->type,
            'itemId'        => $request->itemId,
            'itemName'      => $item->name,
            'quantity'      => $request->quantity,
            'price'         => $item->price,
            'catatan'       => $request->catatan,
            'userId'        => $request->user()->id,
        ]);

        transactionPetHotelLog($request->transactionId, 'Tambah Treatment: ' . $item->name . ' x' . $request->quantity, $request->catatan ?? '', $request->user()->id);

        return responseCreate();
    }

    public function deleteAdditionalTreatment(Request $request)
    {
        $access = $this->getUserAccessInfo($request);
        if (!$access['isAdminOrManager'] && !in_array($access['jobTitleId'], [1, 17])) {
            return $this->accessDenied('Anda tidak memiliki akses untuk menghapus item.');
        }

        $item = TransactionPetHotelAdditionalTreatment::find($request->id);
        if (!$item) {
            return responseInvalid(['Item tidak ditemukan.']);
        }

        transactionPetHotelLog($item->transactionId, 'Hapus Item: ' . $item->itemName, '', $request->user()->id);
        $item->delete();

        return responseDelete();
    }

    public function getPrepayments(Request $request)
    {
        $access = $this->getUserAccessInfo($request);
        if (!$access['isAdminOrManager'] && $access['jobTitleId'] !== 1) {
            return $this->accessDenied('Anda tidak memiliki akses untuk melihat data DP.');
        }

        $request->validate(['transactionId' => 'required|integer']);

        // Info transaksi untuk tanda terima
        $tran = DB::table('transaction_pet_hotels as t')
            ->leftJoin('customer as c', 'c.id', '=', 't.customerId')
            ->leftJoin('customerPets as cp', 'cp.id', '=', 't.petId')
            ->leftJoin('location as l', 'l.id', '=', 't.locationId')
            ->where('t.id', $request->transactionId)
            ->select(
                't.registrationNo',
                't.startDate',
                't.endDate',
                DB::raw("COALESCE(c.firstName, t.registrant, '') as customerName"),
                DB::raw("COALESCE(cp.petName, '') as petName"),
                DB::raw("COALESCE(l.locationName, '') as locationName")
            )
            ->first();

        $data = TransactionPetHotelPrepayment::where('transactionId', $request->transactionId)
            ->with(['user:id,firstName', 'paymentMethod:id,name'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id'            => $item->id,
                    'amount'        => $item->amount,
                    'paymentMethod' => optional($item->paymentMethod)->name,
                    'proofPath'     => $item->proofPath ? url('storage/' . $item->proofPath) : null,
                    'proofOriginalName' => $item->proofOriginalName,
                    'catatan'       => $item->catatan,
                    'recordedBy'    => optional($item->user)->firstName,
                    'recordedAt'    => $item->created_at->format('d-m-Y H:i'),
                ];
            });

        $totalPrepaid = TransactionPetHotelPrepayment::where('transactionId', $request->transactionId)->sum('amount');

        return response()->json([
            'list'         => $data,
            'totalPrepaid' => (float) $totalPrepaid,
            'transaction'  => $tran,
        ], 200);
    }

    public function addPrepayment(Request $request)
    {
        $access = $this->getUserAccessInfo($request);
        if (!$access['isAdminOrManager'] && $access['jobTitleId'] !== 1) {
            return $this->accessDenied('Hanya Kasir, Manager, atau Administrator yang dapat mencatat pembayaran awal.');
        }

        $validate = Validator::make($request->all(), [
            'transactionId'   => 'required|integer',
            'paymentMethodId' => 'required|integer',
            'amount'          => 'required|numeric|min:1',
            'proof'           => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'catatan'         => 'nullable|string',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $tran = TransactionPetHotel::find($request->transactionId);
        if (!$tran) {
            return responseInvalid(['Transaksi tidak ditemukan.']);
        }

        // Generate nomor nota DP — format: DP/PH/{locationId}/{tahun}/{bulan}/{urut 4 digit}
        $now   = Carbon::now();
        $tahun = $now->format('Y');
        $bulan = $now->format('m');
        $urut  = DB::table('transaction_pet_hotel_prepayments as p')
            ->join('transaction_pet_hotels as t', 't.id', 'p.transactionId')
            ->where('t.locationId', $tran->locationId)
            ->whereYear('p.created_at', $tahun)
            ->whereMonth('p.created_at', $bulan)
            ->count();
        $notaNumber = "DP/PH/{$tran->locationId}/{$tahun}/{$bulan}/" . str_pad($urut + 1, 4, '0', STR_PAD_LEFT);

        $proofPath = null;
        $proofOriginalName = null;

        if ($request->hasFile('proof')) {
            $file = $request->file('proof');
            $proofOriginalName = $file->getClientOriginalName();
            $fileName = 'dp_' . $request->transactionId . '_' . time() . '.' . $file->getClientOriginalExtension();
            $proofPath = $file->storeAs('Transaction/Pethotel/prepayment', $fileName, 'public');
        }

        TransactionPetHotelPrepayment::create([
            'transactionId'    => $request->transactionId,
            'nota_number'      => $notaNumber,
            'paymentMethodId'  => $request->paymentMethodId,
            'amount'           => $request->amount,
            'proofPath'        => $proofPath,
            'proofOriginalName' => $proofOriginalName,
            'catatan'          => $request->catatan,
            'userId'           => $request->user()->id,
        ]);

        $formatted = number_format($request->amount, 0, ',', '.');
        transactionPetHotelLog($request->transactionId, 'Pembayaran Awal / DP', "Rp {$formatted} diterima", $request->user()->id);

        return responseCreate();
    }

    public function prepaymentReceipt($id)
    {
        $prepayment = TransactionPetHotelPrepayment::with(['user:id,firstName', 'paymentMethod:id,name'])
            ->find($id);

        if (!$prepayment) {
            return response()->json(['message' => 'Data pembayaran tidak ditemukan.'], 404);
        }

        // Info transaksi
        $tran = DB::table('transaction_pet_hotels as t')
            ->leftJoin('customer as c', 'c.id', '=', 't.customerId')
            ->leftJoin('customerPets as cp', 'cp.id', '=', 't.petId')
            ->leftJoin('customerTelephones as ct', function ($join) {
                $join->on('ct.customerId', '=', 'c.id');
            })
            ->where('t.id', $prepayment->transactionId)
            ->select(
                't.registrationNo',
                't.startDate',
                't.endDate',
                DB::raw("COALESCE(c.firstName, t.registrant, '') as customerName"),
                DB::raw("COALESCE(cp.petName, '') as petName"),
                DB::raw("COALESCE(ct.phoneNumber, '-') as phoneNumber")
            )
            ->first();

        // Semua lokasi (sama dengan petshop)
        $formattedLocations = $this->getActiveLocationsForInvoice();

        $notaNumber = $prepayment->nota_number ?? ('DP-' . $prepayment->id);

        $data = [
            'locations'       => $formattedLocations,
            'nota_number'     => $notaNumber,
            'nota_date'       => Carbon::parse($prepayment->created_at)->format('d/m/Y'),
            'registration_no' => $tran->registrationNo ?? '-',
            'customer_name'   => $tran->customerName ?? '-',
            'phone_number'    => $tran->phoneNumber ?? '-',
            'pet_name'        => $tran->petName ?? '-',
            'start_date'      => $tran->startDate ?? '-',
            'end_date'        => $tran->endDate ?? '-',
            'amount'          => $prepayment->amount,
            'payment_method'  => optional($prepayment->paymentMethod)->name ?? '-',
            'catatan'         => $prepayment->catatan,
            'recorded_by'     => optional($prepayment->user)->firstName ?? '-',
            'recorded_at'     => Carbon::parse($prepayment->created_at)->format('d/m/Y H:i'),
        ];

        $namaFile = 'TandaTerima_DP_' . str_replace('/', '_', $notaNumber) . '.pdf';

        $pdf = Pdf::loadView('invoice.prepayment_dp_receipt', $data);
        return $pdf->download($namaFile);
    }

    public function extendStay(Request $request)
    {
        $access = $this->getUserAccessInfo($request);
        if (!$access['isAdminOrManager'] && $access['jobTitleId'] !== 1) {
            return $this->accessDenied('Hanya Kasir, Manager, atau Administrator yang dapat memperpanjang masa menginap.');
        }

        $validate = Validator::make($request->all(), [
            'transactionId' => 'required|integer',
            'newEndDate'    => 'required|date|after:today',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $tran = TransactionPetHotel::find($request->transactionId);
        if (!$tran) {
            return responseInvalid(['Transaksi tidak ditemukan.']);
        }

        $oldEndDate = $tran->endDate;

        TransactionPetHotel::where('id', $request->transactionId)->update([
            'endDate' => $request->newEndDate,
        ]);

        // Generate papan kerja untuk hari-hari tambahan (setelah oldEndDate s/d newEndDate)
        $extendFrom = Carbon::parse($oldEndDate)->addDay();
        $extendTo   = Carbon::parse($request->newEndDate);
        if ($extendFrom->lte($extendTo)) {
            $this->generatePapanKerjaForDateRange(
                $request->transactionId,
                $request->user()->id,
                $extendFrom,
                $extendTo
            );
        }

        transactionPetHotelLog(
            $request->transactionId,
            'Perpanjang Masa Menginap',
            "Tanggal keluar diubah dari {$oldEndDate} menjadi {$request->newEndDate}",
            $request->user()->id
        );

        return responseUpdate();
    }

    // ─── Fase 3: Check-Out & Pembayaran ─────────────────────────────────────────

    public function initiateCheckOut(Request $request)
    {
        $access = $this->getUserAccessInfo($request);
        if (!$access['isAdminOrManager'] && $access['jobTitleId'] !== 1) {
            return $this->accessDenied('Hanya Kasir, Manager, atau Administrator yang dapat menginisiasi check-out.');
        }

        $validate = Validator::make($request->all(), [
            'transactionId' => 'required|integer',
            'checkoutDate'  => 'nullable|date',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $tran = TransactionPetHotel::find($request->transactionId);
        if (!$tran || strtolower($tran->status) !== 'dalam perawatan') {
            return responseInvalid(['Transaksi tidak ditemukan atau status tidak sesuai.']);
        }

        if (TransactionPetHotelCheckout::where('transactionId', $request->transactionId)->exists()) {
            return responseInvalid(['Check-out sudah diinisiasi sebelumnya.']);
        }

        $checkoutDate = $request->checkoutDate ? Carbon::parse($request->checkoutDate) : Carbon::today();
        $startDate    = Carbon::parse($tran->startDate);
        $daysStayed   = max(1, $startDate->diffInDays($checkoutDate));

        // Kandang
        $cage = DB::table('transactionPetHotelTreatmentCages as tc')
            ->join('cages as c', 'c.id', 'tc.cageId')
            ->select('c.id as cageId', 'c.cageName as unitName')
            ->where('tc.transactionId', $request->transactionId)
            ->first();

        // Tarif per hari — dari service yang dipilih saat input treatment plan
        // sp.price disimpan sebagai string "50,000" → strip koma sebelum cast
        $pricePerDay = 0;
        if ($tran->stayServiceId) {
            $stayServicePrice = DB::table('servicesPrice')
                ->where('service_id', $tran->stayServiceId)
                ->where('location_id', $tran->locationId)
                ->value(DB::raw("CAST(REPLACE(price, ',', '') AS UNSIGNED)"));
            $pricePerDay = (float) ($stayServicePrice ?? 0);
        }

        $subtotalStay = $daysStayed * $pricePerDay;

        // Treatment awal — services
        // sp.price adalah string "35,000" → gunakan CAST(REPLACE(...)) agar MySQL hitung dengan benar
        $subtotalServices = (float) DB::table('transactionPetHotelTreatmentServices as tpcs')
            ->join('servicesPrice as sp', function ($j) use ($tran) {
                $j->on('sp.service_id', 'tpcs.serviceId')->where('sp.location_id', $tran->locationId);
            })
            ->where('tpcs.transactionId', $request->transactionId)
            ->sum(DB::raw("tpcs.quantity * CAST(REPLACE(sp.price, ',', '') AS UNSIGNED)"));

        // Treatment awal — products
        $subtotalProducts = (float) DB::table('transactionPetHotelTreatmentProducts as tp')
            ->join('products as p', 'p.id', 'tp.productId')
            ->where('tp.transactionId', $request->transactionId)
            ->sum(DB::raw('tp.quantity * p.price'));

        $subtotalTreatment = $subtotalServices + $subtotalProducts;

        // Treatment tambahan
        $subtotalAdditional = (float) TransactionPetHotelAdditionalTreatment::where('transactionId', $request->transactionId)
            ->get()
            ->sum(fn($i) => $i->price * $i->quantity);

        // DP / Pembayaran awal
        $totalPrepaid = (float) TransactionPetHotelPrepayment::where('transactionId', $request->transactionId)
            ->sum('amount');

        $subtotalBeforeDiscount = $subtotalStay + $subtotalTreatment + $subtotalAdditional;
        $grandTotal             = max(0, $subtotalBeforeDiscount - $totalPrepaid);

        DB::beginTransaction();
        try {
            TransactionPetHotelCheckout::create([
                'transactionId'          => $request->transactionId,
                'checkoutDate'           => $checkoutDate->toDateString(),
                'daysStayed'             => $daysStayed,
                'cageId'                 => $cage?->cageId,
                'pricePerDay'            => $pricePerDay,
                'subtotalStay'           => $subtotalStay,
                'subtotalTreatment'      => $subtotalTreatment,
                'subtotalAdditional'     => $subtotalAdditional,
                'totalPrepaid'           => $totalPrepaid,
                'subtotalBeforeDiscount' => $subtotalBeforeDiscount,
                'discountAmount'         => 0,
                'discountNote'           => null,
                'grandTotal'             => $grandTotal,
                'userId'                 => $request->user()->id,
            ]);

            statusTransactionPetHotel($request->transactionId, 'Proses Check-Out', $request->user()->id);
            transactionPetHotelLog($request->transactionId, 'Check-Out Diinisiasi', "Total tagihan: Rp " . number_format($grandTotal, 0, ',', '.'), $request->user()->id);

            DB::commit();
            return responseCreate();
        } catch (\Throwable $th) {
            DB::rollback();
            return responseInvalid([$th->getMessage()]);
        }
    }

    public function getCheckoutSummary(Request $request)
    {
        $access = $this->getUserAccessInfo($request);
        if (!$access['isAdminOrManager'] && $access['jobTitleId'] !== 1) {
            return $this->accessDenied('Anda tidak memiliki akses untuk melihat ringkasan checkout.');
        }

        $request->validate(['transactionId' => 'required|integer']);

        $checkout = TransactionPetHotelCheckout::where('transactionId', $request->transactionId)->first();
        if (!$checkout) {
            return responseInvalid(['Data checkout tidak ditemukan.']);
        }

        $tran = TransactionPetHotel::find($request->transactionId);

        $cage = DB::table('transactionPetHotelTreatmentCages as tc')
            ->join('cages as c', 'c.id', 'tc.cageId')
            ->select('c.cageName')
            ->where('tc.transactionId', $request->transactionId)
            ->first();

        // Nama service tarif menginap
        $stayServiceName = 'Tarif Menginap';
        if ($tran->stayServiceId) {
            $stayServiceName = DB::table('services')
                ->where('id', $tran->stayServiceId)
                ->value('fullName') ?? 'Tarif Menginap';
        }

        $services = DB::table('transactionPetHotelTreatmentServices as tpcs')
            ->join('services as s', 's.id', 'tpcs.serviceId')
            ->join('servicesPrice as sp', function ($j) use ($tran) {
                $j->on('sp.service_id', 'tpcs.serviceId')->where('sp.location_id', $tran->locationId);
            })
            ->select(
                'tpcs.id',
                's.id as serviceId',
                's.fullName as name',
                'tpcs.quantity',
                DB::raw("MIN(CAST(REPLACE(sp.price, ',', '') AS UNSIGNED)) as price"),
                DB::raw("tpcs.quantity * MIN(CAST(REPLACE(sp.price, ',', '') AS UNSIGNED)) as total")
            )
            ->where('tpcs.transactionId', $request->transactionId)
            ->groupBy('tpcs.id', 's.id', 's.fullName', 'tpcs.quantity')
            ->get();

        $products = DB::table('transactionPetHotelTreatmentProducts as tp')
            ->join('products as p', 'p.id', 'tp.productId')
            ->select('p.id as productId', 'p.fullName as name', 'tp.quantity', 'p.price', DB::raw('tp.quantity * p.price as total'))
            ->where('tp.transactionId', $request->transactionId)
            ->get();

        $additional = TransactionPetHotelAdditionalTreatment::where('transactionId', $request->transactionId)
            ->get()
            ->map(fn($i) => [
                'name'     => $i->itemName,
                'type'     => $i->type,
                'quantity' => $i->quantity,
                'price'    => (float) str_replace(',', '', $i->price),
                'total'    => (float) str_replace(',', '', $i->price) * $i->quantity,
                'catatan'  => $i->catatan,
            ]);

        $prepayments = TransactionPetHotelPrepayment::where('transactionId', $request->transactionId)
            ->with('paymentMethod:id,name')
            ->get()
            ->map(fn($p) => [
                'amount'        => $p->amount,
                'paymentMethod' => optional($p->paymentMethod)->name,
                'recordedAt'    => $p->created_at->format('d-m-Y'),
            ]);

        // Hitung ulang semua nilai numerik secara dinamis dari data aktual
        // agar tidak bergantung pada nilai yang tersimpan (yang mungkin salah saat initiate)
        $pricePerDay = 0;
        if ($tran->stayServiceId) {
            $pricePerDay = (float) DB::table('servicesPrice')
                ->where('service_id', $tran->stayServiceId)
                ->where('location_id', $tran->locationId)
                ->value(DB::raw("CAST(REPLACE(price, ',', '') AS UNSIGNED)")) ?? 0;
        }
        $subtotalStay       = $checkout->daysStayed * $pricePerDay;
        $subtotalServices   = $services->sum('total');
        $subtotalProducts   = $products->sum('total');
        $subtotalAdditional = $additional->sum('total');
        $totalPrepaid       = (float) TransactionPetHotelPrepayment::where('transactionId', $request->transactionId)->sum('amount');
        $subtotalBeforeDiscount = $subtotalStay + $subtotalServices + $subtotalProducts + $subtotalAdditional;
        $grandTotal         = max(0, $subtotalBeforeDiscount - $totalPrepaid - (float) $checkout->discountAmount);

        // Patch checkout dengan nilai yang benar (untuk referensi payment nantinya)
        $checkout->pricePerDay            = $pricePerDay;
        $checkout->subtotalStay           = $subtotalStay;
        $checkout->subtotalBeforeDiscount = $subtotalBeforeDiscount;
        $checkout->totalPrepaid           = $totalPrepaid;
        $checkout->grandTotal             = $grandTotal;

        return response()->json([
            'checkout'        => $checkout,
            'cageName'        => $cage?->cageName ?? '-',
            'stayServiceName' => $stayServiceName,
            'services'        => $services,
            'products'        => $products,
            'additional'      => $additional,
            'prepayments'     => $prepayments,
        ], 200);
    }

    public function updateCheckoutDiscount(Request $request)
    {
        $access = $this->getUserAccessInfo($request);
        if (!$access['isAdminOrManager'] && $access['jobTitleId'] !== 1) {
            return $this->accessDenied('Hanya Kasir, Manager, atau Administrator yang dapat mengubah diskon.');
        }

        $validate = Validator::make($request->all(), [
            'transactionId'  => 'required|integer',
            'discountAmount' => 'required|numeric|min:0',
            'discountNote'   => 'nullable|string',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $checkout = TransactionPetHotelCheckout::where('transactionId', $request->transactionId)->firstOrFail();
        $grandTotal = max(0, $checkout->subtotalBeforeDiscount - $checkout->totalPrepaid - (float) $request->discountAmount);

        $checkout->update([
            'discountAmount' => $request->discountAmount,
            'discountNote'   => $request->discountNote,
            'grandTotal'     => $grandTotal,
        ]);

        return responseUpdate();
    }

    public function checkoutPayment(Request $request)
    {
        $access = $this->getUserAccessInfo($request);
        if (!$access['isAdminOrManager'] && $access['jobTitleId'] !== 1) {
            return $this->accessDenied('Hanya Kasir, Manager, atau Administrator yang dapat memproses pembayaran.');
        }

        $validate = Validator::make($request->all(), [
            'transactionId'   => 'required|integer',
            'paymentMethodId' => 'required|integer',
            'amountPaid'      => 'required|numeric|min:0',
            'note'            => 'nullable|string',
            'proof'           => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $checkout = TransactionPetHotelCheckout::where('transactionId', $request->transactionId)->first();
        if (!$checkout) {
            return responseInvalid(['Data checkout tidak ditemukan.']);
        }

        $tran = TransactionPetHotel::find($request->transactionId);
        if (!$tran) {
            return responseInvalid(['Transaksi tidak ditemukan.']);
        }

        $now    = Carbon::now();
        $tahun  = $now->format('Y');
        $bulan  = $now->format('m');
        $urut   = DB::table('transaction_pet_hotel_payment_totals as tp')
            ->join('transaction_pet_hotels as tpc', 'tp.transactionId', '=', 'tpc.id')
            ->where('tpc.locationId', $tran->locationId)
            ->whereYear('tp.created_at', $tahun)
            ->whereMonth('tp.created_at', $bulan)
            ->count();
        $notaNumber = "INV/PH/{$tran->locationId}/{$tahun}/{$bulan}/" . str_pad($urut + 1, 4, '0', STR_PAD_LEFT);

        DB::beginTransaction();
        try {
            // Upload bukti pembayaran jika ada
            $proofOfPayment = null;
            $originalName   = null;
            if ($request->hasFile('proof')) {
                $file           = $request->file('proof');
                $originalName   = $file->getClientOriginalName();
                $proofOfPayment = $file->store('checkout-proofs', 'public');
            }

            $total = new transaction_pet_hotel_payment_total();
            $total->transactionId   = $request->transactionId;
            $total->paymentmethodId = $request->paymentMethodId;
            $total->amount          = $checkout->grandTotal;
            $total->amountPaid      = $request->amountPaid;
            $total->nota_number     = $notaNumber;
            $total->note            = $request->note ?? '';
            $total->proofOfPayment  = $proofOfPayment;
            $total->originalName    = $originalName;
            $total->userId          = $request->user()->id;
            $total->save();

            statusTransactionPetHotel($request->transactionId, 'Selesai', $request->user()->id);
            transactionPetHotelLog($request->transactionId, 'Pembayaran Check-Out Diterima', "Nota: {$notaNumber}", $request->user()->id);

            DB::commit();
            return responseCreate();
        } catch (\Throwable $th) {
            DB::rollback();
            return responseInvalid([$th->getMessage()]);
        }
    }

    public function checkoutInvoice(Request $request)
    {
        $request->validate(['transactionId' => 'required|integer']);

        $tran = TransactionPetHotel::find($request->transactionId);
        if (!$tran) return responseInvalid(['Transaksi tidak ditemukan.']);

        $checkout = TransactionPetHotelCheckout::where('transactionId', $tran->id)->first();
        if (!$checkout) return responseInvalid(['Data checkout tidak ditemukan.']);

        $payment = DB::table('transaction_pet_hotel_payment_totals')
            ->where('transactionId', $tran->id)
            ->orderByDesc('created_at')
            ->first();

        // ── Lokasi ──
        $formattedLocations = $this->getActiveLocationsForInvoice();

        // ── Customer & pet ──
        $customer = DB::table('customer as c')
            ->leftJoin('customerTelephones as ct', function ($j) {
                $j->on('c.id', 'ct.customerId')->where('ct.isDeleted', 0);
            })
            ->where('c.id', $tran->customerId)
            ->select('c.firstName', 'ct.phoneNumber', 'c.memberNo')
            ->first();

        $petName = DB::table('customerPets')->where('id', $tran->petId)->value('petName') ?? '-';

        $cage = DB::table('transactionPetHotelTreatmentCages as tc')
            ->join('cages as c', 'c.id', 'tc.cageId')
            ->where('tc.transactionId', $tran->id)
            ->value('c.cageName');

        // ── Stay service ──
        $stayServiceName = 'Tarif Menginap';
        if ($tran->stayServiceId) {
            $stayServiceName = DB::table('services')->where('id', $tran->stayServiceId)->value('fullName') ?? 'Tarif Menginap';
        }
        $pricePerDay = 0;
        if ($tran->stayServiceId) {
            $pricePerDay = (float) DB::table('servicesPrice')
                ->where('service_id', $tran->stayServiceId)
                ->where('location_id', $tran->locationId)
                ->value(DB::raw("CAST(REPLACE(price, ',', '') AS UNSIGNED)")) ?? 0;
        }
        $subtotalStay = $checkout->daysStayed * $pricePerDay;

        // ── Treatment services ──
        $services = DB::table('transactionPetHotelTreatmentServices as tpcs')
            ->join('services as s', 's.id', 'tpcs.serviceId')
            ->join('servicesPrice as sp', function ($j) use ($tran) {
                $j->on('sp.service_id', 'tpcs.serviceId')->where('sp.location_id', $tran->locationId);
            })
            ->select('s.fullName as name', 'tpcs.quantity',
                DB::raw("MIN(CAST(REPLACE(sp.price, ',', '') AS UNSIGNED)) as price"),
                DB::raw("tpcs.quantity * MIN(CAST(REPLACE(sp.price, ',', '') AS UNSIGNED)) as total"))
            ->where('tpcs.transactionId', $tran->id)
            ->groupBy('tpcs.id', 's.fullName', 'tpcs.quantity')
            ->get()->map(fn($r) => (array) $r)->toArray();

        // ── Treatment products ──
        $products = DB::table('transactionPetHotelTreatmentProducts as tp')
            ->join('products as p', 'p.id', 'tp.productId')
            ->select('p.fullName as name', 'tp.quantity', 'p.price',
                DB::raw('tp.quantity * p.price as total'))
            ->where('tp.transactionId', $tran->id)
            ->get()->map(fn($r) => (array) $r)->toArray();

        // ── Additional ──
        $additional = TransactionPetHotelAdditionalTreatment::where('transactionId', $tran->id)
            ->get()->map(fn($i) => [
                'name'     => $i->itemName,
                'quantity' => $i->quantity,
                'price'    => (float) str_replace(',', '', $i->price),
                'total'    => (float) str_replace(',', '', $i->price) * $i->quantity,
                'catatan'  => $i->catatan,
            ])->toArray();

        $subtotalServices   = array_sum(array_column($services, 'total'));
        $subtotalProducts   = array_sum(array_column($products, 'total'));
        $subtotalAdditional = array_sum(array_column($additional, 'total'));
        $subtotalBeforeDiscount = $subtotalStay + $subtotalServices + $subtotalProducts + $subtotalAdditional;
        $totalPrepaid = (float) TransactionPetHotelPrepayment::where('transactionId', $tran->id)->sum('amount');

        // Diskon promo (jika ada di payment record)
        $grandTotal  = (float) ($payment->amountPaid ?? $subtotalBeforeDiscount - $totalPrepaid);
        $totalDiscount = max(0, $subtotalBeforeDiscount - $totalPrepaid - $grandTotal);

        $paymentMethodName = DB::table('paymentmethod')->where('id', $payment->paymentMethodId ?? null)->value('name') ?? '-';

        $data = [
            'locations'              => $formattedLocations,
            'nota_date'              => Carbon::parse($payment->created_at ?? now())->format('d/m/Y H:i'),
            'no_nota'                => $payment->nota_number ?? '-',
            'member_no'              => $customer->memberNo ?? '-',
            'customer_name'          => $customer->firstName ?? '-',
            'phone_number'           => $customer->phoneNumber ?? '-',
            'pet_name'               => $petName,
            'cage_name'              => $cage ?? '-',
            'checkin_date'           => Carbon::parse($tran->startDate)->format('d/m/Y'),
            'checkout_date'          => Carbon::parse($checkout->checkoutDate)->format('d/m/Y'),
            'days_stayed'            => $checkout->daysStayed,
            'stay_service_name'      => $stayServiceName,
            'price_per_day'          => $pricePerDay,
            'subtotal_stay'          => $subtotalStay,
            'services'               => $services,
            'products'               => $products,
            'additional'             => $additional,
            'subtotal_before_discount' => $subtotalBeforeDiscount,
            'total_prepaid'          => $totalPrepaid,
            'total_discount'         => $totalDiscount,
            'grand_total'            => $grandTotal,
            'amount_paid'            => (float) ($payment->amountPaid ?? $grandTotal),
            'payment_method'         => $paymentMethodName,
            'note'                   => $payment->note ?? '',
        ];

        $namaFile = 'Invoice_PetHotel_' . ($payment->nota_number ? str_replace('/', '_', $payment->nota_number) : $tran->id) . '.pdf';
        $pdf = Pdf::loadView('invoice.invoice_pethotel', $data);
        return $pdf->download($namaFile);
    }

    // ─── Invoice Helper ──────────────────────────────────────────────────────────

    /**
     * Ambil daftar lokasi aktif untuk header invoice secara dinamis.
     * Cabang baru yang aktif (status=1, isDeleted=0) otomatis muncul tanpa perlu modifikasi kode.
     *
     * @return array  [ ['name'=>..., 'description'=>..., 'phone'=>...], ... ]
     */
    private function getActiveLocationsForInvoice(): array
    {
        $rows = DB::table('location')
            ->leftJoin('location_telephone', function ($join) {
                $join->on('location.codeLocation', '=', 'location_telephone.codeLocation')
                     ->where(function ($q) {
                         $q->where('location_telephone.usage', 'Utama')
                           ->orWhereNull('location_telephone.usage');
                     });
            })
            ->where('location.status', 1)
            ->where('location.isDeleted', 0)
            ->select(
                'location.codeLocation',
                'location.locationName',
                'location.description',
                'location_telephone.phoneNumber'
            )
            ->distinct()
            ->orderBy('location.id')
            ->get();

        // Deduplikasi per codeLocation — ambil satu nomor telepon per lokasi
        $groups = [];
        foreach ($rows as $row) {
            $key = $row->codeLocation;
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'name'        => $row->locationName,
                    'description' => $row->description ?? '',
                    'phone'       => $row->phoneNumber  ?? '',
                ];
            }
        }

        return array_values($groups);
    }

    // ─── Access Control ─────────────────────────────────────────────────────────

    private function getUserAccessInfo(Request $request): array
    {
        $user     = User::where('id', $request->user()->id)->first();
        $roleName = strtolower(
            DB::table('usersRoles')->where('id', $user->roleId)->value('roleName') ?? ''
        );

        return [
            'user'             => $user,
            'jobTitleId'       => (int) $user->jobTitleId,
            'roleName'         => $roleName,
            'isAdministrator'  => $roleName === 'administrator',
            'isAdminOrManager' => in_array($roleName, ['administrator', 'manager']),
        ];
    }

    private function accessDenied(string $message = 'Anda tidak memiliki akses untuk melakukan tindakan ini!')
    {
        return responseErrorValidation($message, $message);
    }

    // ─── Papan Kerja ────────────────────────────────────────────────────────────

    private function generatePapanKerja(int $transactionId, int $userId): void
    {
        $tran = DB::table('transaction_pet_hotels')
            ->select('startDate', 'endDate')
            ->where('id', $transactionId)
            ->first();

        $startDate = Carbon::parse($tran->startDate ?? now());
        $endDate   = Carbon::parse($tran->endDate   ?? $startDate);

        $this->generatePapanKerjaForDateRange($transactionId, $userId, $startDate, $endDate);
    }

    /**
     * Generate papan kerja rows untuk rentang tanggal tertentu.
     * Dipakai oleh generatePapanKerja (pertama kali) dan extendStay (perpanjangan).
     */
    private function generatePapanKerjaForDateRange(
        int $transactionId,
        int $userId,
        Carbon $fromDate,
        Carbon $toDate
    ): void {
        $harianActivities = [
            ['time' => '07:00', 'activity' => 'Makan Pagi',           'instructions' => ['Berikan porsi makanan sesuai anjuran', 'Pastikan air minum tersedia']],
            ['time' => '09:00', 'activity' => 'Bersihkan Kandang',     'instructions' => ['Bersihkan kotak pasir', 'Ganti alas kandang jika perlu']],
            ['time' => '12:00', 'activity' => 'Makan Siang',           'instructions' => ['Berikan porsi makanan sesuai anjuran']],
            ['time' => '15:00', 'activity' => 'Bersihkan Kotak Pasir', 'instructions' => ['Periksa dan bersihkan kotak pasir']],
            ['time' => '18:00', 'activity' => 'Makan Malam',           'instructions' => ['Berikan porsi makanan sesuai anjuran', 'Ganti air minum']],
        ];

        $vetnurseActivities = [
            ['time' => '08:00', 'activity' => 'Monitoring Kondisi Pagi',  'instructions' => ['Periksa nafsu makan', 'Catat kondisi umum hewan', 'Cek kebersihan area']],
            ['time' => '20:00', 'activity' => 'Monitoring Kondisi Malam', 'instructions' => ['Periksa kondisi sebelum tutup', 'Pastikan hewan nyaman']],
        ];

        $current = $fromDate->copy()->startOfDay();
        $last    = $toDate->copy()->startOfDay();

        while ($current->lte($last)) {
            $dateStr = $current->toDateString();

            foreach ($harianActivities as $item) {
                TransactionPetHotelPapanKerja::create([
                    'transactionId' => $transactionId,
                    'type'          => 'harian',
                    'scheduledDate' => $dateStr,
                    'time'          => $item['time'],
                    'activity'      => $item['activity'],
                    'instructions'  => $item['instructions'],
                    'userId'        => $userId,
                ]);
            }

            foreach ($vetnurseActivities as $item) {
                TransactionPetHotelPapanKerja::create([
                    'transactionId' => $transactionId,
                    'type'          => 'vetnurse',
                    'scheduledDate' => $dateStr,
                    'time'          => $item['time'],
                    'activity'      => $item['activity'],
                    'instructions'  => $item['instructions'],
                    'userId'        => $userId,
                ]);
            }

            $current->addDay();
        }
    }

    private function getPapanKerjaRows(int $transactionId, string $type): \Illuminate\Support\Collection
    {
        $cage = DB::table('transactionPetHotelTreatmentCages as tc')
            ->join('cages as c', 'c.id', 'tc.cageId')
            ->select('c.cageName as cageNo')
            ->where('tc.transactionId', $transactionId)
            ->first();

        $pet = DB::table('transaction_pet_hotels as t')
            ->join('customerPets as cp', 'cp.id', 't.petId')
            ->join('petCategory as pc', 'pc.id', 'cp.petCategoryId')
            ->select('cp.petName', 'pc.petCategoryName as petBreed')
            ->where('t.id', $transactionId)
            ->first();

        // ── Lookup petugas shift (Opsi 2: staffAbsents + usersLocation) ──────
        $locationId = DB::table('transaction_pet_hotels')
            ->where('id', $transactionId)
            ->value('locationId');

        // Job title yang relevan per tipe board
        $requiredJobTitles = $type === 'vetnurse'
            ? [4, 5, 17]   // Paramedis, Vetnurse, Dokter Hewan
            : [2, 4, 5];   // Helper, Paramedis, Vetnurse

        // User yang assigned ke lokasi ini dengan job title sesuai
        $locationUserIds = DB::table('usersLocation as ul')
            ->join('users as u', 'u.id', 'ul.usersId')
            ->where('ul.locationId', $locationId)
            ->where('ul.isDeleted', 0)
            ->whereIn('u.jobTitleId', $requiredJobTitles)
            ->pluck('ul.usersId')
            ->toArray();

        // Pre-load nama user
        $userNames = DB::table('users')
            ->whereIn('id', $locationUserIds)
            ->pluck('firstName', 'id');

        // Pre-load absensi: group by "workDate_shift" → [userId, ...]
        // Shift 1 = pagi (hadir sebelum 15:00), Shift 2 = siang/malam
        $attendanceMap = collect();
        if (!empty($locationUserIds)) {
            $attendanceMap = DB::table('staffAbsents')
                ->whereIn('userId', $locationUserIds)
                ->whereIn('shift', ['Shift 1', 'Shift 2'])
                ->where('isDeleted', 0)
                ->select('userId', 'shift', DB::raw("DATE(presentTime) as workDate"))
                ->get()
                ->groupBy(fn($r) => $r->workDate . '_' . $r->shift);
        }
        // ─────────────────────────────────────────────────────────────────────

        return TransactionPetHotelPapanKerja::where('transactionId', $transactionId)
            ->where('type', $type)
            ->orderBy('scheduledDate')
            ->orderBy('time')
            ->get()
            ->map(function ($row) use ($cage, $pet, $attendanceMap, $userNames) {
                // Tentukan shift dari jam aktivitas
                $shiftLabel   = ($row->time < '15:00:00') ? 'Shift 1' : 'Shift 2';
                $rawDate      = $row->scheduledDate
                    ? Carbon::parse($row->scheduledDate)->toDateString()
                    : null;
                $attendKey    = $rawDate . '_' . $shiftLabel;
                $assignedNames = [];

                if ($rawDate && $attendanceMap->has($attendKey)) {
                    $assignedNames = $attendanceMap->get($attendKey)
                        ->map(fn($r) => $userNames->get($r->userId))
                        ->filter()
                        ->unique()
                        ->values()
                        ->toArray();
                }

                return [
                    'id'              => $row->id,
                    'cageNo'          => $cage->cageNo ?? '-',
                    'petName'         => $pet->petName ?? '-',
                    'petBreed'        => $pet->petBreed ?? '-',
                    'petWeight'       => null,
                    'scheduledDate'   => $rawDate
                        ? Carbon::parse($rawDate)->format('d/m/Y')
                        : null,
                    'time'            => $row->time,
                    'activity'        => $row->activity,
                    'instructions'    => $row->instructions ?? [],
                    'isDone'          => $row->isDone,
                    'statusAktivitas' => $row->statusAktivitas,
                    'assignedStaff'   => $assignedNames,   // PIC shift dari absensi
                    'shiftLabel'      => $shiftLabel,
                    'temuan'          => $row->temuan ?? [],
                    'kondisiFeses'    => $row->kondisiFeses,
                    'catatan'         => $row->catatan,
                    'fotoUrl'         => $row->foto ? url('storage/' . $row->foto) : null,
                    'completedAt'     => $row->completedAt ? Carbon::parse($row->completedAt)->format('d/m/Y H:i') : null,
                    'completedBy'     => $row->completedBy ? optional(User::find($row->completedBy))->firstName : null,
                ];
            });
    }

    public function getPapanKerjaHarian(Request $request)
    {
        $access = $this->getUserAccessInfo($request);
        // 2=Helper, 4=Paramedis, 5=Vetnurse, 17=Dokter Hewan
        if (!$access['isAdminOrManager'] && !in_array($access['jobTitleId'], [2, 4, 5, 17])) {
            return $this->accessDenied('Anda tidak memiliki akses untuk melihat Papan Kerja Harian.');
        }

        $request->validate(['transactionId' => 'required|integer']);

        $rows = $this->getPapanKerjaRows((int) $request->transactionId, 'harian');

        return response()->json($rows, 200);
    }

    public function markPapanKerjaHarianDone(Request $request)
    {
        return $this->markPapanKerjaDone($request, 'harian');
    }

    public function getPapanKerjaVetnurse(Request $request)
    {
        $access = $this->getUserAccessInfo($request);
        // 4=Paramedis, 5=Vetnurse, 17=Dokter Hewan
        if (!$access['isAdminOrManager'] && !in_array($access['jobTitleId'], [4, 5, 17])) {
            return $this->accessDenied('Anda tidak memiliki akses untuk melihat Papan Kerja Vetnurse.');
        }

        $request->validate(['transactionId' => 'required|integer']);

        $rows = $this->getPapanKerjaRows((int) $request->transactionId, 'vetnurse');

        return response()->json($rows, 200);
    }

    public function markPapanKerjaVetnurseDone(Request $request)
    {
        return $this->markPapanKerjaDone($request, 'vetnurse');
    }

    private function markPapanKerjaDone(Request $request, string $type)
    {
        $access = $this->getUserAccessInfo($request);

        $allowedJobTitles = $type === 'vetnurse'
            ? [4, 5]       // Paramedis, Vetnurse
            : [2, 4, 5];   // Helper, Paramedis, Vetnurse

        if (!$access['isAdminOrManager'] && !in_array($access['jobTitleId'], $allowedJobTitles)) {
            $label = $type === 'vetnurse' ? 'Papan Kerja Vetnurse' : 'Papan Kerja Harian';
            return $this->accessDenied("Anda tidak memiliki akses untuk mengisi {$label}.");
        }


        $request->validate([
            'id'              => 'required|integer',
            'statusAktivitas' => 'required|in:berhasil,dilewati',
            'temuan'          => 'nullable|array',
            'kondisiFeses'    => 'nullable|string',
            'catatan'         => 'nullable|string',
            'foto'            => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
        ]);

        $row = TransactionPetHotelPapanKerja::find($request->id);

        if (!$row) {
            return responseInvalid(['Data aktivitas tidak ditemukan!']);
        }

        $fotoPath = $row->foto;
        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $name = 'papan_kerja_' . $row->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $fotoPath = $file->storeAs('Transaction/Pethotel/papan_kerja', $name, 'public');
        }

        $row->update([
            'isDone'          => true,
            'statusAktivitas' => $request->statusAktivitas,
            'temuan'          => $request->temuan ?? [],
            'kondisiFeses'    => $request->kondisiFeses,
            'catatan'         => $request->catatan,
            'foto'            => $fotoPath,
            'completedBy'     => $request->user()->id,
            'completedAt'     => now(),
        ]);

        return responseCreate();
    }
}
