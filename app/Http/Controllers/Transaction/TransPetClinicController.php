<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Customer\Customer;
use App\Models\Customer\CustomerPets;
use App\Models\Customer\CustomerTelephones;
use App\Models\ListBreathTransaction;
use App\Models\ListHeartTransaction;
use App\Models\ListSoundTransaction;
use App\Models\ListTemperatureTransaction;
use App\Models\ListVaginalTransaction;
use App\Models\ListWeightTransaction;
use App\Models\Products;
use App\Models\PromotionMaster;
use App\Models\Service;
use App\Models\Staff\UsersLocation;
use App\Models\transaction_pet_clinic_payment_based_sales;
use App\Models\transaction_pet_clinic_payment_bundle;
use App\Models\transaction_pet_clinic_payment_total;
use App\Models\transaction_pet_clinic_payments;
use App\Models\TransactionPetClinic;
use App\Models\TransactionPetClinicAdvice;
use App\Models\transactionPetClinicAnamnesis;
use App\Models\TransactionPetClinicCheckUpResult;
use App\Models\TransactionPetClinicDiagnose;
use App\Models\TransactionPetClinicRecipes;
use App\Models\TransactionPetClinicServices;
use App\Models\TransactionPetClinicTreatment;
use App\Models\TransactionPetClinicTreatmentTreatPlan;
use App\Models\TransactionPetClinicTreatmentService;
use App\Models\TransactionPetClinicTreatmentProduct;
use App\Models\TransactionPetClinicPapanKerjaHarian;
use App\Models\TransactionPetClinicPolicyAgreement;
use App\Models\TransactionPetClinicPrepayment;
use App\Models\TransactionPetClinicAdditionalTreatment;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Validator;
use DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TransPetClinicController extends Controller
{
    use \App\Http\Controllers\Transaction\Traits\PaymentVerificationTrait;
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

        $data = DB::table('transactionPetClinics as t')
            ->join('location as l', 'l.id', 't.locationId')
            ->join('customer as c', 'c.id', 't.customerId')
            ->join('customerPets as cp', 'cp.id', 't.PetId')
            ->leftjoin('customerGroups as cg', 'cg.id', 'c.customerGroupId')
            ->join('users as u', 'u.id', 't.doctorId')
            ->join('users as uc', 'uc.id', 't.userId')
            ->select(
                't.id',
                'l.id as locationId',
                't.registrationNo',
                'l.locationName',
                'c.firstName',
                'cp.petName',
                DB::raw("IFNULL(cg.customerGroup,'') as customerGroup"),
                't.typeOfCare',
                DB::raw("CASE WHEN t.startDate IS NOT NULL AND t.startDate != '' THEN DATE_FORMAT(t.startDate, '%d/%m/%Y') ELSE '' END as startDate"),
                DB::raw("CASE WHEN t.endDate IS NOT NULL AND t.endDate != '' THEN DATE_FORMAT(t.endDate, '%d/%m/%Y') ELSE '' END as endDate"),
                't.status',
                'u.firstName as picDoctor',
                'uc.firstName as createdBy',
                DB::raw("DATE_FORMAT(t.created_at, '%d/%m/%Y %H:%i') as createdAt"),
                DB::raw('CASE WHEN ' . $statusDoc . '=1 and u.id=' . $request->user()->id . ' and t.status="Cek Kondisi Pet" THEN 1 ELSE 0 END as isPetCheck')
            )
            ->where('t.isDeleted', '=', 0);

        if ($request->status == 'ongoing') {
            $data = $data->whereNotIn('t.status', ['Selesai', 'Batal']);
        } elseif ($request->status == 'finished') {
            $data = $data->whereIn('t.status', ['Selesai', 'Batal']);
        }

        // Filter status spesifik (dari dropdown filter UI)
        if ($request->statusFilter) {
            $data = $data->where('t.status', $request->statusFilter);
        }

        // Filter rentang tanggal mulai
        if ($request->startDateFrom) {
            $data = $data->where('t.startDate', '>=', $request->startDateFrom);
        }
        if ($request->startDateTo) {
            $data = $data->where('t.startDate', '<=', $request->startDateTo);
        }

        $reqUser   = $request->user();
        $isAdminOrMgr = in_array($reqUser->roleId, [1, 2]);
        $isKasir      = ($reqUser->jobTitleId == 1);

        if ($isAdminOrMgr) {
            // Admin / Manager: bisa filter berdasarkan locationId dari request
            if ($request->locationId) {
                $data = $data->whereIn('l.id', $request->locationId);
            }
        } elseif ($isKasir) {
            // Kasir: filter berdasarkan lokasi dari accessControlSchedulesMaster
            $kasirLocations = DB::table('accessControlSchedulesMaster')
                ->where('usersId', $reqUser->id)
                ->where('isDeleted', 0)
                ->pluck('locationId')
                ->unique()
                ->values()
                ->toArray();
            if (!empty($kasirLocations)) {
                $data = $data->whereIn('l.id', $kasirLocations);
            }
            // Jika belum di-assign, tampilkan semua (fallback)
        } else {
            // Role lain (dokter, vetnurse, dll): tampilkan hanya lokasi yang di-assign
            $locations = \App\Models\Staff\UsersLocation::where('usersId', $reqUser->id)
                ->pluck('locationId')
                ->toArray();
            if (!empty($locations)) {
                $data = $data->whereIn('l.id', $locations);
            }
        }

        if ($request->customerGroupId) {

            $data = $data->whereIn('cg.id', $request->customerGroupId);
        }

        if ($request->typeOfCare) {

            $data = $data->where('t.typeOfCare', '=', $request->typeOfCare);
        }

        if ($request->search) {
            $data = $data->where(function ($q) use ($request) {
                $q->where('t.registrationNo', 'like', '%' . $request->search . '%')
                  ->orWhere('c.firstName', 'like', '%' . $request->search . '%')
                  ->orWhere('c.lastName', 'like', '%' . $request->search . '%')
                  ->orWhere('cp.petName', 'like', '%' . $request->search . '%')
                  ->orWhere('u.firstName', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->orderValue) {
            $data = $data->orderBy($request->orderColumn, $request->orderValue);
        }

        $data = $data->orderBy('t.updated_at', 'desc');

        if (!$itemPerPage) {
            return responseIndex(0, []);
        }
        $offset = ($page - 1) * $itemPerPage;

        $count_data = $data->count();
        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $data = $data->limit($itemPerPage)->offset(0)->get();
        } else {
            $data = $data->limit($itemPerPage)->offset($offset)->get();
        }

        $totalPaging = $count_data / $itemPerPage;

        return responseIndex(ceil($totalPaging), $data);
    }

    /**
     * GET ringkasan statistik transaksi pet clinic untuk dashboard mini.
     */
    public function getStats(Request $request)
    {
        $user      = $request->user();
        $finishedStatus = ['Selesai', 'Batal'];

        // Base query dengan join wajib
        $base = DB::table('transactionPetClinics as t')
            ->join('location as l', 'l.id', 't.locationId')
            ->where('t.isDeleted', 0);

        // Filter lokasi berdasarkan role
        $isAdmin  = in_array($user->roleId, [1, 2]);
        $isKasir  = ($user->jobTitleId == 1);

        if ($isAdmin) {
            // Administrator / Manager: lihat semua lokasi (tidak difilter)
        } elseif ($isKasir) {
            // Kasir: lokasi didapat dari accessControlSchedulesMaster (di-assign saat login/jadwal)
            $locationIds = DB::table('accessControlSchedulesMaster')
                ->where('usersId', $user->id)
                ->where('isDeleted', 0)
                ->pluck('locationId')
                ->unique()
                ->values()
                ->toArray();
            if (!empty($locationIds)) {
                $base = $base->whereIn('l.id', $locationIds);
            }
            // Jika kasir belum di-assign ke lokasi manapun, tampilkan semua (fallback)
        } else {
            // Role lain (dokter, vetnurse, paramedis, dll): filter ke lokasi yang di-assign
            $locationIds = \App\Models\Staff\UsersLocation::where('usersId', $user->id)
                ->pluck('locationId')
                ->toArray();
            if (!empty($locationIds)) {
                $base = $base->whereIn('l.id', $locationIds);
            }
        }

        $activeBase   = (clone $base)->whereNotIn('t.status', $finishedStatus);
        $finishedBase = (clone $base)->whereIn('t.status', $finishedStatus);

        return response()->json([
            // Jumlah aktif per tipe
            'rawatJalan'        => (clone $activeBase)->where('t.typeOfCare', 1)->count(),
            'rawatInap'         => (clone $activeBase)->where('t.typeOfCare', 2)->count(),
            // Selesai hari ini
            'finishedToday'     => (clone $finishedBase)->whereDate('t.updated_at', \Carbon\Carbon::today())->count(),
            // Breakdown status yang perlu perhatian
            'menungguDokter'    => (clone $activeBase)->where('t.status', 'Menunggu Dokter')->count(),
            'dalamPerawatan'    => (clone $activeBase)->where('t.status', 'Dalam Perawatan')->count(),
            'prosesPembayaran'  => (clone $activeBase)->where('t.status', 'Proses Pembayaran')->count(),
            'menungguBayar'     => (clone $activeBase)->where('t.status', 'Menunggu Pembayaran Berikutnya')->count(),
        ]);
    }

    private function Search($request)
    {
        $temp_column = null;

        $data = DB::table('transactionPetClinics as t')
            ->select(
                't.registrationNo'
            )
            ->where('t.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('t.registrationNo', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 't.registrationNo';
        }
        //------------------------

        $data = DB::table('transactionPetClinics as t')
            ->join('customer as c', 'c.id', 't.customerId')
            ->select(
                'c.firstName'
            )
            ->where('t.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('c.firstName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'c.firstName';
        }
        //------------------------

        $data = DB::table('transactionPetClinics as t')
            ->join('customer as c', 'c.id', 't.customerId')
            ->join('users as u', 'u.id', 't.doctorId')
            ->select(
                'u.firstName',
            )
            ->where('t.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('u.firstName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'u.firstName';
        }
        //------------------------

        return $temp_column;
    }

    public function create(Request $request)
    {

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
                'typeOfCare' => 'required|int',
                'doctorId' => 'required|int',
                'note' => 'required|string',
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return responseInvalid($errors);
            }
        } else {
            $rules = [
                'isNewCustomer' => 'required|bool',
                'locationId' => 'required|integer',
                'customerId' => 'nullable|integer',
                'registrant' => 'nullable|string',
                'petId' => 'nullable|integer',
                'typeOfCare' => 'required|int',
                'doctorId' => 'required|int',
                'note' => 'required|string',
            ];
            // If adding a new pet for existing customer, validate pet fields
            if ($request->isNewPet == true) {
                $rules['petName']     = 'required|string';
                $rules['petCategory'] = 'required|integer';
                $rules['petGender']   = 'required|string|in:J,B';
                $rules['isSterile']   = 'required|bool';
            }
            $validate = Validator::make($request->all(), $rules);

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
                return responseInvalid(['Doctor is Not Found']);
            }

            $trx = TransactionPetClinic::where('locationId', $request->locationId)->count();

            $regisNo = 'RPC.TRX.' . $request->locationId . '.' . str_pad($trx + 1, 8, 0, STR_PAD_LEFT);

            // Resolve bookingId via queueId (nullable — tidak wajib)
            $bookingId = null;
            if ($request->filled('queueId')) {
                $bookingId = DB::table('queues')
                    ->where('id', $request->queueId)
                    ->where('isDeleted', 0)
                    ->value('bookingId');
            }

            $tran = TransactionPetClinic::create([
                'bookingId' => $bookingId,
                'registrationNo' => $regisNo,
                'status' => 'Menunggu Dokter',
                'isNewCustomer' => $request->isNewCustomer,
                'isNewPet' => $request->isNewPet,
                'locationId' => $request->locationId,
                'customerId' => $cust->id,
                'petId' => $pet->id,
                'registrant' => $request->registrant,
                'typeOfCare' => $request->typeOfCare,
                'startDate' => $request->startDate,
                'endDate' => $request->endDate,
                'doctorId' => $request->doctorId,
                'note' => $request->note,
                'userId' => $request->user()->id,
            ]);

            transactionPetClinicLog($tran->id, 'New Transaction', '', $request->user()->id);

            $petName = DB::table('customerPets')->where('id', $tran->petId)->value('petName') ?? 'Pet';
            sendNotificationToStaffAtLocation(
                $request->locationId,
                [17], // Dokter Hewan
                'petclinic',
                "Transaksi baru: {$petName} menunggu pemeriksaan dokter.",
                'info'
            );

            DB::commit();
            return responseCreate();
        } catch (Exception $th) {
            DB::rollback();
            return responseInvalid([$th->getMessage()]);
        }
    }

    public function detail(Request $request)
    {
        $detail = DB::table('transactionPetClinics as t')
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
                't.typeOfCare',
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

        $log = DB::table('transaction_pet_clinic_logs as tl')
            ->join('transactionPetClinics as t', 't.id', 'tl.transactionId')
            ->join('users as u', 'u.id', 'tl.userId')
            ->select(
                'tl.id',
                'tl.activity',
                'tl.remark',
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(tl.created_at, '%d-%m-%Y %H:%m:%s') as createdAt")
            )
            ->where('t.id', '=', $request->id)
            ->orderBy('tl.id', 'desc')
            ->get();

        $paymentLog = DB::table('transaction_pet_clinic_payment_totals as tpt')
            ->join('paymentmethod as pm', 'pm.id', 'tpt.paymentMethodId')
            ->join('users as u', 'u.id', 'tpt.userId')
            ->leftJoin('users as uu', 'uu.id', 'tpt.uploadedBy')
            ->leftJoin('users as cu', 'cu.id', 'tpt.confirmedBy')
            ->select(
                'tpt.id',
                'tpt.amount',
                'tpt.nota_number as notaNumber',
                'pm.name as paymentMethod',
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(tpt.created_at, '%d-%m-%Y %H:%i:%s') as date"),
                'tpt.isPayed',
                'tpt.proofOfPayment',
                'tpt.uploadedBy',
                'tpt.confirmedBy',
                'tpt.verificationStatus',
                'tpt.verificationNote',
                'tpt.verifiedAt',
                'uu.firstName as uploadedByName',
                'cu.firstName as confirmedByName'
            )
            ->where('tpt.transactionId', '=', $request->id)
            ->orderBy('tpt.id', 'desc')
            ->get();

        $data = ['detail' => $detail, 'transactionLogs' => $log, 'paymentLogs' => $paymentLog];

        return response()->json($data, 200);
    }

    public function update(Request $request)
    {
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
                'typeOfCare' => 'required|int',
                'doctorId' => 'required|int',
                'notes' => 'nullable|string',
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return responseInvalid($errors);
            }
        } else {
            $updateRules = [
                'isNewCustomer' => 'required|bool',
                'locationId' => 'required|integer',
                'customerId' => 'nullable|integer',
                'registrant' => 'nullable|string',
                'petId' => 'nullable|integer',
                'typeOfCare' => 'required|int',
                'doctorId' => 'required|int',
                'notes' => 'nullable|string',
            ];
            if ($request->isNewPet == true) {
                $updateRules['petName']     = 'required|string';
                $updateRules['petCategory'] = 'required|integer';
                $updateRules['petGender']   = 'required|string|in:J,B';
                $updateRules['isSterile']   = 'required|bool';
            }
            $validate = Validator::make($request->all(), $updateRules);

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

            $oldTransaction = TransactionPetClinic::find($request->id);

            $transaction = TransactionPetClinic::updateOrCreate(
                ['id' => $request->id],
                [
                    'registrationNo' => $request->registrationNo,
                    'isNewCustomer' => $request->isNewCustomer,
                    'isNewPet' => $request->isNewPet,
                    'locationId' => $request->locationId,
                    'customerId' => $request->customerId,
                    'petId' => $request->petId,
                    'registrant' => $request->registrant,
                    'serviceCategory' => $request->serviceCategory,
                    'startDate' => $request->startDate,
                    'endDate' => $request->endDate,
                    'doctorId' => $request->doctorId,
                    'note' => $request->note,
                    'userUpdatedId' => $request->user()->id,
                ]
            );

            if ($oldTransaction) {
                $fieldNames = [
                    'registrationNo' => 'Nomor Registrasi',
                    'locationId' => 'Lokasi',
                    'customerId' => 'ID Pelanggan',
                    'typeOfCare' => 'Tipe Penanganan',
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

                            transactionPetClinicLog($request->id, 'Update Transaction', "Data '{$customName}' telah diubah menjadi {$doctor->firstName}", $request->user()->id);
                        } else {
                            transactionPetClinicLog($request->id, 'Update Transaction', "Data '{$customName}' telah diubah menjadi {$newValue}", $request->user()->id);
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

        $existingTransactions = TransactionPetClinic::whereIn('id', $request->id)->pluck('id')->toArray();
        $missingTransactions = array_diff($request->id, $existingTransactions);

        if (!empty($missingTransactions)) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['There is any Data not found!'],
            ], 422);
        }

        TransactionPetClinic::whereIn('id', $request->id)->update([
            'DeletedBy' => $request->user()->id,
            'isDeleted' => true,
            'DeletedAt' => Carbon::now()
        ]);

        foreach ($request->id as $va) {
            transactionPetClinicLog($va, 'Transaction Deleted', '', $request->user()->id);
        }

        return responseDelete();
    }

    public function export(Request $request)
    {

        $data1 = DB::table('transactionPetClinics as t')
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
            ->where('t.isDeleted', '=', 0)
            ->where('t.typeOfCare', '=', 1);

        if (!$request->user()->roleId == 1 || !$request->user()->roleId == 2) {
            $locations = UsersLocation::select('id')->where('usersId', $request->user()->id)->get()->pluck('id')->toArray();
            $data1 = $data1->whereIn('l.id', $locations);
        } else {

            if ($request->locationId) {

                $data1 = $data1->whereIn('l.id', $request->locationId);
            }
        }

        if ($request->customerGroupId) {

            $data1 = $data1->whereIn('cg.id', $request->customerGroupId);
        }

        $data1 = $data1->orderBy('t.updated_at', 'desc')->get();

        $data2 = DB::table('transactionPetClinics as t')
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
            ->where('t.isDeleted', '=', 0)
            ->where('t.typeOfCare', '=', 2);

        if (!$request->user()->roleId == 1 || !$request->user()->roleId == 2) {
            $locations = UsersLocation::select('id')->where('usersId', $request->user()->id)->get()->pluck('id')->toArray();
            $data2 = $data2->whereIn('l.id', $locations);
        } else {

            if ($request->locationId) {

                $data2 = $data2->whereIn('l.id', $request->locationId);
            }
        }

        if ($request->customerGroupId) {

            $data2 = $data2->whereIn('cg.id', $request->customerGroupId);
        }

        $data2 = $data2->orderBy('t.updated_at', 'desc')->get();

        $data3 = DB::table('transactionPetClinics as t')
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
                DB::raw("CASE WHEN t.typeOfCare = 1 then 'Rawat Jalan' else 'Rawat Inap' end as typeOfCare"),
                't.status',
                'u.firstName as picDoctor',
                'uc.firstName as createdBy',
                DB::raw("DATE_FORMAT(t.created_at, '%d-%m-%Y %H:%m:%s') as createdAt")
            )
            ->where('t.isDeleted', '=', 0);

        if (!$request->user()->roleId == 1 || !$request->user()->roleId == 2) {
            $locations = UsersLocation::select('id')->where('usersId', $request->user()->id)->get()->pluck('id')->toArray();
            $data3 = $data3->whereIn('l.id', $locations);
        } else {

            if ($request->locationId) {

                $data3 = $data3->whereIn('l.id', $request->locationId);
            }
        }

        $data3 = $data3->whereIn('t.status', ['Selesai', 'Batal']);

        if ($request->customerGroupId) {

            $data3 = $data3->whereIn('cg.id', $request->customerGroupId);
        }

        $data3 = $data3->orderBy('t.updated_at', 'desc')->get();

        $spreadsheet = IOFactory::load(public_path() . '/template/transaction/' . 'Template_Export_Transaction_Pet_Clinic.xlsx');

        $sheet = $spreadsheet->getSheet(0);
        $row = 2;
        foreach ($data1 as $item) {

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

        $sheet = $spreadsheet->getSheet(1);
        $row = 2;
        foreach ($data2 as $item) {

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

        $sheet = $spreadsheet->getSheet(2);
        $row = 2;
        foreach ($data3 as $item) {

            $sheet->setCellValue("A{$row}", $row - 1);
            $sheet->setCellValue("B{$row}", $item->registrationNo);
            $sheet->setCellValue("C{$row}", $item->locationName);
            $sheet->setCellValue("D{$row}", $item->firstName);
            $sheet->setCellValue("E{$row}", $item->customerGroup);
            $sheet->setCellValue("F{$row}", $item->typeOfCare);
            $sheet->setCellValue("G{$row}", $item->startDate);
            $sheet->setCellValue("H{$row}", $item->endDate);
            $sheet->setCellValue("I{$row}", $item->status);
            $sheet->setCellValue("J{$row}", $item->picDoctor);
            $sheet->setCellValue("K{$row}", $item->createdBy);
            $sheet->setCellValue("L{$row}", $item->createdAt);

            $row++;
        }

        $fileName = 'Export Transaksi Pet Clinic.xlsx';

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

    public function orderNumber(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return responseInvalid($errors);
        }

        $trx = TransactionPetClinic::find($request->id);

        $loc = TransactionPetClinic::where('locationId', $trx->locationId)->count();

        $date = Carbon::now()->format('d');
        $month = Carbon::now()->format('m');
        $year = Carbon::now()->format('Y');

        $regisNo = str_pad($loc + 1, 3, 0, STR_PAD_LEFT) . '/LPIK-RIS-RPC-PC/' . $trx->locationId . '/' . $date . '/' . $month . '/' . $year;

        return response()->json($regisNo, 200);
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

        $tran = TransactionPetClinic::where([['id', '=', $request->transactionId]])->first();

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
            return responseErrorValidation('Can not accept transaction because the doctor is different branch!', 'Can not accept transaction because the doctor is different branch!');
        }

        $doctor = User::where([['id', '=', $request->user()->id]])->first();

        $petName = DB::table('customerPets')->where('id', $tran->petId)->value('petName') ?? 'Pet';

        if ($request->status == 1) {

            statusTransactionPetClinic($request->transactionId, 'Cek Kondisi Pet', $request->user()->id);

            transactionPetClinicLog($request->transactionId, 'Pemeriksaan pasien oleh ' . $doctor->firstName, '', $request->user()->id);

            sendNotificationToStaffAtLocation(
                $tran->locationId,
                [1, 4, 5], // Kasir, Paramedis, Vetnurse
                'petclinic',
                "Dr. {$doctor->firstName} menerima pasien {$petName} — sedang diperiksa.",
                'success'
            );
        } else {

            $validate = Validator::make($request->all(), [
                'reason' => 'required|string',
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return responseInvalid($errors);
            }

            statusTransactionPetClinic($request->transactionId, 'Ditolak Dokter', $request->user()->id);

            transactionPetClinicLog($request->transactionId, 'Pasien Ditolak oleh ' . $doctor->firstName, $request->reason, $request->user()->id);

            sendNotificationToStaffAtLocation(
                $tran->locationId,
                [1], // Kasir
                'petclinic',
                "Pasien {$petName} ditolak oleh Dr. {$doctor->firstName} — perlu tindak lanjut.",
                'warning'
            );
        }

        return responseCreate();
    }

    public function reassignDoctor(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'transactionId' => 'required|integer',
            'doctorId' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return responseInvalid($errors);
        }

        $user = User::where([['id', '=', $request->user()->id]])->first();

        statusTransactionPetClinic($request->transactionId, 'Menunggu Dokter', $request->user()->id);

        transactionPetClinicLog($request->transactionId, 'Menunggu konfirmasi dokter', 'Dokter dipindahkan oleh ' . $user->firstName, $request->user()->id);

        return responseCreate();
    }

    public function getCheckCondition(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $id = $request->id;

        $anamnesis = DB::table('transactionPetClinicAnamnesis')
            ->where('transactionPetClinicId', $id)
            ->where('isDeleted', 0)
            ->first();

        $checkUpResult = DB::table('transactionPetClinicCheckUpResults')
            ->where('transactionPetClinicId', $id)
            ->where('isDeleted', 0)
            ->first();

        $diagnose = DB::table('transactionPetClinicDiagnoses')
            ->where('transactionPetClinicId', $id)
            ->where('isDeleted', 0)
            ->first();

        $treatment = DB::table('transactionPetClinicTreatments')
            ->where('transactionPetClinicId', $id)
            ->where('isDeleted', 0)
            ->first();

        $advice = DB::table('transactionPetClinicAdvice')
            ->where('transactionPetClinicId', $id)
            ->where('isDeleted', 0)
            ->first();

        return response()->json([
            'anamnesis'     => $anamnesis,
            'checkUpResult' => $checkUpResult,
            'diagnose'      => $diagnose,
            'treatment'     => $treatment,
            'advice'        => $advice,
        ], 200);
    }

    public function loadDataPetCheck(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return responseInvalid($errors);
        }

        $trx = TransactionPetClinic::find($request->id);

        if (!$trx) {
            return responseInvalid(['Transaction is not found!']);
        }

        $cust = Customer::find($trx->customerId);

        if (!$cust) {
            return responseInvalid(['Customer is not found!']);
        }

        $phone = CustomerTelephones::where('customerId', '=', $trx->customerId)
            ->where('usage', '=', 'Utama')
            ->first();

        $pet = CustomerPets::join('petCategory', 'customerPets.petCategoryId', '=', 'petCategory.id')
            ->where('customerPets.id', $trx->petId)
            ->select('customerPets.petName', 'petCategory.petCategoryName as petCategory')
            ->first();

        $phoneNumber = '';

        if ($phone) {
            $phoneNumber = $phone->phoneNumber;
        }

        return response()->json([
            'ownerName' => $cust->firstName,
            'phoneNumber' => $phoneNumber,
            'type' => $pet->petCategory,
            'petName' => $pet->petName,
        ], 200);
    }

    public function createPetCheck(Request $request)
    {
        $messages = [
            // Messages for 'required' rule
            'required' => 'Kolom :attribute wajib diisi.',

            // Messages for specific attribute rules
            'transactionPetClinicId.integer' => 'ID Transaksi Klinik Hewan harus berupa bilangan bulat.',

            'petCheckRegistrationNo.string' => 'Nomor Registrasi Pemeriksaan Hewan harus berupa teks.',

            'isAnthelmintic.boolean' => 'Pilihan Obat Cacing harus berupa nilai benar atau salah (true/false).',
            'anthelminticDate.date' => 'Tanggal Obat Cacing harus berupa format tanggal yang valid.',
            'anthelminticBrand.string' => 'Merek Obat Cacing harus berupa teks.',

            'isVaccination.boolean' => 'Pilihan Vaksinasi harus berupa nilai benar atau salah (true/false).',
            'vaccinationDate.date' => 'Tanggal Vaksinasi harus berupa format tanggal yang valid.',
            'vaccinationBrand.string' => 'Merek Vaksinasi harus berupa teks.',

            'isFleaMedicine.boolean' => 'Pilihan Obat Kutu harus berupa nilai benar atau salah (true/false).',
            'fleaMedicineDate.date' => 'Tanggal Obat Kutu harus berupa format tanggal yang valid.',
            'fleaMedicineBrand.string' => 'Merek Obat Kutu harus berupa teks.',

            'previousAction.string' => 'Tindakan Sebelumnya harus berupa teks.',
            'othersCompalints.string' => 'Keluhan Lainnya harus berupa teks.',

            'weight.numeric' => 'Berat harus berupa angka.',
            'weightCategory.integer' => 'Kategori Berat harus berupa bilangan bulat.',

            'temperature.numeric' => 'Suhu harus berupa angka.',
            'temperatureBottom.numeric' => 'Suhu Bawah harus berupa angka.',
            'temperatureTop.numeric' => 'Suhu Atas harus berupa angka.',
            'temperatureCategory.integer' => 'Kategori Suhu harus berupa bilangan bulat.',

            'isLice.boolean' => 'Pilihan Kutu Rambut harus berupa nilai benar atau salah (true/false).',
            'noteLice.string' => 'Catatan Kutu Rambut harus berupa teks.',

            'isFlea.boolean' => 'Pilihan Kutu harus berupa nilai benar atau salah (true/false).',
            'noteFlea.string' => 'Catatan Kutu harus berupa teks.',

            'isCaplak.boolean' => 'Pilihan Caplak harus berupa nilai benar atau salah (true/false).',
            'noteCaplak.string' => 'Catatan Caplak harus berupa teks.',

            'isTungau.boolean' => 'Pilihan Tungau harus berupa nilai benar atau salah (true/false).',
            'noteTungau.string' => 'Catatan Tungau harus berupa teks.',

            'ectoParasitCategory.integer' => 'Kategori Ektoparasit harus berupa bilangan bulat.',

            'isNematoda.boolean' => 'Pilihan Nematoda harus berupa nilai benar atau salah (true/false).',
            'noteNematoda.string' => 'Catatan Nematoda harus berupa teks.',

            'isTermatoda.boolean' => 'Pilihan Trematoda harus berupa nilai benar atau salah (true/false).',
            'noteTermatoda.string' => 'Catatan Trematoda harus berupa teks.',

            'isCestode.boolean' => 'Pilihan Cestode harus berupa nilai benar atau salah (true/false).',
            'noteCestode.string' => 'Catatan Cestode harus berupa teks.',

            'isFungiFound.boolean' => 'Pilihan Ditemukan Jamur harus berupa nilai benar atau salah (true/false).',

            'konjung.string' => 'Konjungtiva harus berupa teks.',
            'ginggiva.string' => 'Gingiva harus berupa teks.',
            'ear.string' => 'Telinga harus berupa teks.',
            'tongue.string' => 'Lidah harus berupa teks.',
            'nose.string' => 'Hidung harus berupa teks.',
            'CRT.string' => 'Capillary Refill Time (CRT) harus berupa teks.',

            'genitals.string' => 'Alat Kelamin harus berupa teks.',

            'neurologicalFindings.string' => 'Temuan Neurologis harus berupa teks.',
            'lokomosiFindings.string' => 'Temuan Lokomosi harus berupa teks.',

            'isSnot.boolean' => 'Pilihan Ingus harus berupa nilai benar atau salah (true/false).',
            'noteSnot.string' => 'Catatan Ingus harus berupa teks.',

            'breathType.integer' => 'Jenis Napas harus berupa bilangan bulat.',
            'breathSoundType.integer' => 'Jenis Suara Napas harus berupa bilangan bulat.',
            'breathSoundNote.string' => 'Catatan Suara Napas harus berupa teks.',
            'othersFoundBreath.string' => 'Temuan Pernapasan Lainnya harus berupa teks.',

            'pulsus.integer' => 'Pulsus harus berupa bilangan bulat.',
            'heartSound.integer' => 'Suara Jantung harus berupa bilangan bulat.',
            'othersFoundHeart.string' => 'Temuan Jantung Lainnya harus berupa teks.',

            'othersFoundSkin.string' => 'Temuan Kulit Lainnya harus berupa teks.',
            'othersFoundHair.string' => 'Temuan Rambut Lainnya harus berupa teks.',

            'maleTesticles.integer' => 'Testis Jantan harus berupa bilangan bulat.',
            'othersMaleTesticles.string' => 'Catatan Testis Jantan Lainnya harus berupa teks.',
            'penisCondition.string' => 'Kondisi Penis harus berupa teks.',
            'vaginalDischargeType.integer' => 'Jenis Keluaran Vagina harus berupa bilangan bulat.',
            'urinationType.integer' => 'Jenis Urinasi harus berupa bilangan bulat.',
            'othersUrination.string' => 'Catatan Urinasi Lainnya harus berupa teks.',
            'othersFoundUrogenital.string' => 'Temuan Urogenital Lainnya harus berupa teks.',

            'abnormalitasCavumOris.string' => 'Abnormalitas Rongga Mulut harus berupa teks.',
            'intestinalPeristalsis.string' => 'Peristalsis Usus harus berupa teks.',
            'perkusiAbdomen.string' => 'Perkusi Abdomen harus berupa teks.',
            'rektumKloaka.string' => 'Rektum/Kloaka harus berupa teks.',
            'othersCharacterRektumKloaka.string' => 'Karakter Rektum/Kloaka Lainnya harus berupa teks.',

            'fecesForm.string' => 'Bentuk Feses harus berupa teks.',
            'fecesColor.string' => 'Warna Feses harus berupa teks.',
            'fecesWithCharacter.string' => 'Karakteristik Feses harus berupa teks.',
            'othersFoundDigesti.string' => 'Temuan Pencernaan Lainnya harus berupa teks.',

            'reflectPupil.string' => 'Refleks Pupil harus berupa teks.',
            'eyeBallCondition.string' => 'Kondisi Bola Mata harus berupa teks.',
            'othersFoundVision.string' => 'Temuan Penglihatan Lainnya harus berupa teks.',

            'earlobe.string' => 'Daun Telinga harus berupa teks.',
            'earwax.integer' => 'Kotoran Telinga harus berupa bilangan bulat.',
            'earwaxCharacter.string' => 'Karakteristik Kotoran Telinga harus berupa teks.',
            'othersFoundEar.string' => 'Temuan Telinga Lainnya harus berupa teks.',

            'isInpatient.integer' => 'Pilihan Rawat Inap harus berupa bilangan bulat.',
            'noteInpatient.string' => 'Catatan Rawat Inap harus berupa teks.',

            'isTherapeuticFeed.integer' => 'Pilihan Pakan Terapeutik harus berupa bilangan bulat.',
            'noteTherapeuticFeed.string' => 'Catatan Pakan Terapeutik harus berupa teks.',

            'imuneBooster.string' => 'Peningkat Imun harus berupa teks.',
            'suplement.string' => 'Suplemen harus berupa teks.',
            'desinfeksi.string' => 'Desinfeksi harus berupa teks.',
            'care.string' => 'Perawatan harus berupa teks.',

            'isGrooming.integer' => 'Pilihan Grooming harus berupa bilangan bulat.',
            'noteGrooming.string' => 'Catatan Grooming harus berupa teks.',

            'othersNoteAdvice.string' => 'Catatan Saran Lainnya harus berupa teks.',
            'nextControlCheckup.date' => 'Tanggal Kontrol Berikutnya harus berupa format tanggal yang valid.',

            'diagnoseDisease.string' => 'Diagnosis Penyakit harus berupa teks.',
            'prognoseDisease.string' => 'Prognosis Penyakit harus berupa teks.',
            'diseaseProgressOverview.string' => 'Gambaran Kemajuan Penyakit harus berupa teks.',

            'isMicroscope.boolean' => 'Pilihan Mikroskop harus berupa nilai benar atau salah (true/false).',
            'noteMicroscope.string' => 'Catatan Mikroskop harus berupa teks.',

            'isEye.boolean' => 'Pilihan Mata harus berupa nilai benar atau salah (true/false).',
            'noteEye.string' => 'Catatan Mata harus berupa teks.',

            'isTeskit.boolean' => 'Pilihan Tes Kit harus berupa nilai benar atau salah (true/false).',
            'noteTeskit.string' => 'Catatan Tes Kit harus berupa teks.',

            'isUltrasonografi.boolean' => 'Pilihan Ultrasonografi harus berupa nilai benar atau salah (true/false).',
            'noteUltrasonografi.string' => 'Catatan Ultrasonografi harus berupa teks.',

            'isRontgen.boolean' => 'Pilihan Rontgen harus berupa nilai benar atau salah (true/false).',
            'noteRontgen.string' => 'Catatan Rontgen harus berupa teks.',

            'isBloodReview.boolean' => 'Pilihan Tinjauan Darah harus berupa nilai benar atau salah (true/false).',
            'noteBloodReview.string' => 'Catatan Tinjauan Darah harus berupa teks.',

            'isSitologi.boolean' => 'Pilihan Sitologi harus berupa nilai benar atau salah (true/false).',
            'noteSitologi.string' => 'Catatan Sitologi harus berupa teks.',

            'isVaginalSmear.boolean' => 'Pilihan Vaginal Smear harus berupa nilai benar atau salah (true/false).',
            'noteVaginalSmear.string' => 'Catatan Vaginal Smear harus berupa teks.',

            'isBloodLab.boolean' => 'Pilihan Lab Darah harus berupa nilai benar atau salah (true/false).',
            'noteBloodLab.string' => 'Catatan Lab Darah harus berupa teks.',

            'isSurgery.integer' => 'Pilihan Operasi harus berupa bilangan bulat.',
            'noteSurgery.string' => 'Catatan Operasi harus berupa teks.',

            'infusion.string' => 'Infus harus berupa teks.',
            'fisioteraphy.string' => 'Fisioterapi harus berupa teks.',
            'injectionMedicine.string' => 'Obat Suntik harus berupa teks.',
            'oralMedicine.string' => 'Obat Oral harus berupa teks.',
            'tropicalMedicine.string' => 'Obat Topikal harus berupa teks.',
            'vaccination.string' => 'Vaksinasi harus berupa teks.',
            'othersTreatment.string' => 'Pengobatan Lainnya harus berupa teks.',

        ];

        $validate = Validator::make($request->all(), [
            'transactionPetClinicId' => 'required|integer',
            'petCheckRegistrationNo' => 'required|string',

            'isAnthelmintic' => 'required|boolean',
            'anthelminticDate' => 'nullable|date',
            'anthelminticBrand' => 'nullable|string',

            'isVaccination' => 'required|boolean',
            'vaccinationDate' => 'nullable|date',
            'vaccinationBrand' => 'nullable|string',

            'isFleaMedicine' => 'required|boolean',
            'fleaMedicineDate' => 'nullable|date',
            'fleaMedicineBrand' => 'nullable|string',

            'previousAction' => 'nullable|string',
            'othersCompalints' => 'nullable|string',

            'weight' => 'required|numeric',
            'weightCategory' => 'required|integer',

            'temperature' => 'required|numeric',
            'temperatureBottom' => 'required|numeric',
            'temperatureTop' => 'required|numeric',
            'temperatureCategory' => 'required|integer',

            'isLice' => 'nullable|boolean',
            'noteLice' => 'nullable|string',

            'isFlea' => 'nullable|boolean',
            'noteFlea' => 'nullable|string',

            'isCaplak' => 'nullable|boolean',
            'noteCaplak' => 'nullable|string',

            'isTungau' => 'nullable|boolean',
            'noteTungau' => 'nullable|string',

            'ectoParasitCategory' => 'nullable|integer',

            'isNematoda' => 'nullable|boolean',
            'noteNematoda' => 'nullable|string',
            //endoparasit
            'isTermatoda' => 'nullable|boolean',
            'noteTermatoda' => 'nullable|string',

            'isCestode' => 'nullable|boolean',
            'noteCestode' => 'nullable|string',

            'isFungiFound' => 'nullable|boolean',

            'konjung' => 'nullable|string',
            'ginggiva' => 'nullable|string',
            'ear' => 'nullable|string',
            'tongue' => 'nullable|string',
            'nose' => 'nullable|string',
            'CRT' => 'nullable|string',

            //kelamin
            'genitals' => 'nullable|string',

            'neurologicalFindings' => 'nullable|string',
            'lokomosiFindings' => 'nullable|string',

            //ingus
            'isSnot' => 'nullable|boolean',
            'noteSnot' => 'nullable|string',

            'breathType' => 'nullable|integer',
            'breathSoundType' => 'nullable|integer',
            'breathSoundNote' => 'nullable|string',
            'othersFoundBreath' => 'nullable|string',

            'pulsus' => 'nullable|integer',
            'heartSound' => 'nullable|integer',
            'othersFoundHeart' => 'nullable|string',

            'othersFoundSkin' => 'nullable|string',
            'othersFoundHair' => 'nullable|string',

            //urogenital
            'maleTesticles' => 'nullable|integer',
            'othersMaleTesticles' => 'nullable|string',
            'penisCondition' => 'nullable|string',
            'vaginalDischargeType' => 'nullable|integer',
            'urinationType' => 'nullable|integer',
            'othersUrination' => 'nullable|string',
            'othersFoundUrogenital' => 'nullable|string',

            'abnormalitasCavumOris' => 'nullable|string',
            'intestinalPeristalsis' => 'nullable|string',
            'perkusiAbdomen' => 'nullable|string',
            'rektumKloaka' => 'nullable|string',
            'othersCharacterRektumKloaka' => 'nullable|string',

            'fecesForm' => 'nullable|string',
            'fecesColor' => 'nullable|string',
            'fecesWithCharacter' => 'nullable|string',
            'othersFoundDigesti' => 'nullable|string',

            'reflectPupil' => 'nullable|string',
            'eyeBallCondition' => 'nullable|string',
            'othersFoundVision' => 'nullable|string',

            'earlobe' => 'nullable|string',
            'earwax' => 'nullable|integer',
            'earwaxCharacter' => 'nullable|string',
            'othersFoundEar' => 'nullable|string',

            'isInpatient' => 'required|integer',
            'noteInpatient' => 'nullable|string',

            'isTherapeuticFeed' => 'required|integer',
            'noteTherapeuticFeed' => 'nullable|string',

            'imuneBooster' => 'nullable|string',
            'suplement' => 'nullable|string',
            'desinfeksi' => 'nullable|string',
            'care' => 'nullable|string',

            'isGrooming' => 'required|integer',
            'noteGrooming' => 'nullable|string',

            'othersNoteAdvice' => 'nullable|string',
            'nextControlCheckup' => 'nullable|date',

            'diagnoseDisease' => 'nullable|string',
            'prognoseDisease' => 'nullable|string',
            'diseaseProgressOverview' => 'nullable|string',

            'isMicroscope' => 'required|boolean',
            'noteMicroscope' => 'nullable|string',

            'isEye' => 'required|boolean',
            'noteEye' => 'nullable|string',

            'isTeskit' => 'required|boolean',
            'noteTeskit' => 'nullable|string',

            'isUltrasonografi' => 'required|boolean',
            'noteUltrasonografi' => 'nullable|string',

            'isRontgen' => 'required|boolean',
            'noteRontgen' => 'nullable|string',

            'isBloodReview' => 'required|boolean',
            'noteBloodReview' => 'nullable|string',

            'isSitologi' => 'required|boolean',
            'noteSitologi' => 'nullable|string',

            'isVaginalSmear' => 'required|boolean',
            'noteVaginalSmear' => 'nullable|string',

            'isBloodLab' => 'required|boolean',
            'noteBloodLab' => 'nullable|string',

            'isSurgery' => 'required|integer',
            'noteSurgery' => 'nullable|string',

            'infusion' => 'nullable|string',
            'fisioteraphy' => 'nullable|string',
            'injectionMedicine' => 'nullable|string',
            'oralMedicine' => 'nullable|string',
            'tropicalMedicine' => 'nullable|string',
            'vaccination' => 'nullable|string',
            'othersTreatment' => 'nullable|string',
        ], $messages);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        DB::beginTransaction();
        try {
            transactionPetClinicAnamnesis::create([
                'transactionPetClinicId' => $request->transactionPetClinicId,
                'petCheckRegistrationNo' => $request->petCheckRegistrationNo,
                'isAnthelmintic' => $request->isAnthelmintic,
                'anthelminticDate' => $request->anthelminticDate,
                'anthelminticBrand' => $request->anthelminticBrand,
                'isVaccination' => $request->isVaccination,
                'vaccinationDate' => $request->vaccinationDate,
                'vaccinationBrand' => $request->vaccinationBrand,
                'isFleaMedicine' => $request->isFleaMedicine,
                'fleaMedicineDate' => $request->fleaMedicineDate,
                'fleaMedicineBrand' => $request->fleaMedicineBrand,
                'previousAction' => $request->previousAction,
                'othersCompalints' => $request->othersCompalints,
                'userId' => $request->user()->id,
                'userUpdateId' => $request->user()->id, // Jika `userUpdateId` sama dengan `userId`, kamu bisa sesuaikan
            ]);

            TransactionPetClinicCheckUpResult::create([
                'transactionPetClinicId' => $request->transactionPetClinicId,
                'weight' => $request->weight,
                'weightCategory' => $request->weightCategory,
                'temperature' => $request->temperature,
                'temperatureBottom' => $request->temperatureBottom,
                'temperatureTop' => $request->temperatureTop,
                'temperatureCategory' => $request->temperatureCategory,
                'isLice' => $request->isLice,
                'noteLice' => $request->noteLice,
                'isFlea' => $request->isFlea,
                'noteFlea' => $request->noteFlea,
                'isCaplak' => $request->isCaplak,
                'noteCaplak' => $request->noteCaplak,
                'isTungau' => $request->isTungau,
                'noteTungau' => $request->noteTungau,
                'ectoParasitCategory' => $request->ectoParasitCategory,
                'isNematoda' => $request->isNematoda,
                'noteNematoda' => $request->noteNematoda,
                'isTermatoda' => $request->isTermatoda,
                'noteTermatoda' => $request->noteTermatoda,
                'isCestode' => $request->isCestode,
                'noteCestode' => $request->noteCestode,
                'isFungiFound' => $request->isFungiFound,
                'konjung' => $request->konjung,
                'ginggiva' => $request->ginggiva,
                'ear' => $request->ear,
                'tongue' => $request->tongue,
                'nose' => $request->nose,
                'CRT' => $request->CRT,
                'genitals' => $request->genitals,
                'neurologicalFindings' => $request->neurologicalFindings,
                'lokomosiFindings' => $request->lokomosiFindings,
                'isSnot' => $request->isSnot,
                'noteSnot' => $request->noteSnot,
                'breathType' => $request->breathType,
                'breathSoundType' => $request->breathSoundType,
                'breathSoundNote' => $request->breathSoundNote,
                'othersFoundBreath' => $request->othersFoundBreath,
                'pulsus' => $request->pulsus,
                'heartSound' => $request->heartSound,
                'othersFoundHeart' => $request->othersFoundHeart,
                'othersFoundSkin' => $request->othersFoundSkin,
                'othersFoundHair' => $request->othersFoundHair,
                'maleTesticles' => $request->maleTesticles,
                'othersMaleTesticles' => $request->othersMaleTesticles,
                'penisCondition' => $request->penisCondition,
                'vaginalDischargeType' => $request->vaginalDischargeType,
                'urinationType' => $request->urinationType,
                'othersUrination' => $request->othersUrination,
                'othersFoundUrogenital' => $request->othersFoundUrogenital,
                'abnormalitasCavumOris' => $request->abnormalitasCavumOris,
                'intestinalPeristalsis' => $request->intestinalPeristalsis,
                'perkusiAbdomen' => $request->perkusiAbdomen,
                'rektumKloaka' => $request->rektumKloaka,
                'othersCharacterRektumKloaka' => $request->othersCharacterRektumKloaka,
                'fecesForm' => $request->fecesForm,
                'fecesColor' => $request->fecesColor,
                'fecesWithCharacter' => $request->fecesWithCharacter,
                'othersFoundDigesti' => $request->othersFoundDigesti,
                'reflectPupil' => $request->reflectPupil,
                'eyeBallCondition' => $request->eyeBallCondition,
                'othersFoundVision' => $request->othersFoundVision,
                'earlobe' => $request->earlobe,
                'earwax' => $request->earwax,
                'earwaxCharacter' => $request->earwaxCharacter,
                'othersFoundEar' => $request->othersFoundEar,
                'userId' => $request->user()->id,
                'userUpdateId' => $request->user()->id, // Jika userUpdateId sama dengan userId, bisa disesuaikan
            ]);

            TransactionPetClinicDiagnose::create([
                'transactionPetClinicId' => $request->transactionPetClinicId,
                'diagnoseDisease' => $request->diagnoseDisease,
                'prognoseDisease' => $request->prognoseDisease,
                'diseaseProgressOverview' => $request->diseaseProgressOverview,
                'isMicroscope' => $request->isMicroscope,
                'noteMicroscope' => $request->noteMicroscope,
                'isEye' => $request->isEye,
                'noteEye' => $request->noteEye,
                'isTeskit' => $request->isTeskit,
                'noteTeskit' => $request->noteTeskit,
                'isUltrasonografi' => $request->isUltrasonografi,
                'noteUltrasonografi' => $request->noteUltrasonografi,
                'isRontgen' => $request->isRontgen,
                'noteRontgen' => $request->noteRontgen,
                'isBloodReview' => $request->isBloodReview,
                'noteBloodReview' => $request->noteBloodReview,
                'isSitologi' => $request->isSitologi,
                'noteSitologi' => $request->noteSitologi,
                'isVaginalSmear' => $request->isVaginalSmear,
                'noteVaginalSmear' => $request->noteVaginalSmear,
                'isBloodLab' => $request->isBloodLab,
                'noteBloodLab' => $request->noteBloodLab,
                'userId' => $request->user()->id,
                'userUpdateId' => $request->user()->id, // Jika `userUpdateId` sama dengan `userId`, bisa disesuaikan
            ]);

            TransactionPetClinicTreatment::create([
                'transactionPetClinicId' => $request->transactionPetClinicId,
                'isSurgery' => $request->isSurgery,
                'noteSurgery' => $request->noteSurgery,
                'infusion' => $request->infusion,
                'fisioteraphy' => $request->fisioteraphy,
                'injectionMedicine' => $request->injectionMedicine,
                'oralMedicine' => $request->oralMedicine,
                'tropicalMedicine' => $request->tropicalMedicine,
                'vaccination' => $request->vaccination,
                'othersTreatment' => $request->othersTreatment,
                'userId' => $request->user()->id,
                'userUpdateId' => $request->user()->id, // Jika `userUpdateId` sama dengan `userId`, bisa disesuaikan
            ]);

            TransactionPetClinicAdvice::create([
                'transactionPetClinicId' => $request->transactionPetClinicId,
                'inpatient' => $request->isInpatient,
                'noteInpatient' => $request->noteInpatient,
                'therapeuticFeed' => $request->isTherapeuticFeed,
                'noteTherapeuticFeed' => $request->noteTherapeuticFeed,
                'imuneBooster' => $request->imuneBooster,
                'suplement' => $request->suplement,
                'desinfeksi' => $request->desinfeksi,
                'care' => $request->care,
                'grooming' => $request->isGrooming,
                'noteGrooming' => $request->noteGrooming,
                'othersNoteAdvice' => $request->othersNoteAdvice,
                'nextControlCheckup' => $request->nextControlCheckup,
                'userId' => $request->user()->id,
                'userUpdateId' => $request->user()->id, // Jika `userUpdateId` sama dengan `userId`, bisa disesuaikan
            ]);

            if ($request->isInpatient == 1) {
                $status = 'Proses Rawat Inap';
                $typeOfCare = 2; // Rawat Inap
            } else {
                $status = 'Input Service dan Obat';
                $typeOfCare = 1; // Rawat Jalan
            }

            TransactionPetClinic::updateOrCreate(
                ['id' => $request->transactionPetClinicId],
                [
                    'status' => $status,
                    'typeOfCare' => $typeOfCare,
                    'userUpdatedId' => $request->user()->id,
                ]
            );

            transactionPetClinicLog($request->transactionPetClinicId, 'Cek kondisi vet sudah selesai', '', $request->user()->id);

            DB::commit();
            return responseCreate();
        } catch (Exception $th) {

            return responseInvalid([$th->getMessage()]);
        }
    }

    public function serviceandrecipe(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'transactionPetClinicId' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return responseInvalid($errors);
        }

        if (is_string($request->services)) {
            $dataServices = json_decode($request->services, true);
        } elseif (is_array($request->services)) {
            $dataServices = $request->services;
        }

        foreach ($dataServices as $val) {
            $find = Service::find($val['serviceId']);
            if (!$find) {
                return responseInvalid(['Service not found!']);
            }
        }

        // $ResultRecipe = json_decode($request->recipes, true);

        if (is_string($request->recipes)) {
            $ResultRecipe = json_decode($request->recipes, true);
        } elseif (is_array($request->recipes)) {
            $ResultRecipe = $request->recipes;
        }

        foreach ($ResultRecipe as $val) {
            $find = Products::find($val['productId']);
            if (!$find) {
                return responseInvalid(['Product not found!']);
            }
        }

        DB::beginTransaction();
        try {
            // Add services
            foreach ($dataServices as $val) {
                TransactionPetClinicServices::create([
                    'transactionPetClinicId' => $request->transactionPetClinicId,
                    'serviceId' => $val['serviceId'],
                    'quantity' => $val['quantity'],
                    'userId' => $request->user()->id,
                    'userUpdateId' => $request->user()->id,
                ]);
            }

            // Add recipes
            foreach ($ResultRecipe as $val) {
                TransactionPetClinicRecipes::create([
                    'transactionPetClinicId' => $request->transactionPetClinicId,
                    'productId' => $val['productId'],
                    'dosage' => $val['dosage'],
                    'unit' => $val['unit'],
                    'frequency' => $val['frequency'],
                    'duration' => $val['duration'],
                    'giveMedicine' => $val['giveMedicine'],
                    'notes' => $val['notes'],
                    'userId' => $request->user()->id,
                    'userUpdateId' => $request->user()->id,
                ]);
            }

            statusTransactionPetClinic($request->transactionPetClinicId, 'Proses Pembayaran', $request->user()->id);

            transactionPetClinicLog($request->transactionPetClinicId, 'Input Layanan dan Resep Sudah Selesai', '', $request->user()->id);

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
            'transactionPetClinicId' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return responseInvalid($errors);
        }

        $trans = TransactionPetClinic::find($request->transactionPetClinicId);

        $phone = CustomerTelephones::where('customerId', '=', $trans->customerId)
            ->where('usage', '=', 'Utama')
            ->first();

        $cust = Customer::find($trans->customerId);

        $locId      = (int) $trans->locationId;
        $custGroupId = $cust ? (int)($cust->customerGroupId ?? 0) : 0;

        // Subquery: ambil harga berdasarkan group customer + lokasi (hindari duplikat antar group).
        // Fallback ke group manapun di lokasi yang sama jika group customer tidak ditemukan.
        // REPLACE titik DAN koma agar handle format "50.000" maupun "50,000".
        $spSub = DB::raw("(
            SELECT service_id,
                   COALESCE(
                       (SELECT TRIM(REPLACE(REPLACE(sp_g.price, '.', ''), ',', '')) + 0
                        FROM servicesPrice sp_g
                        WHERE sp_g.service_id  = sp_base.service_id
                          AND sp_g.location_id = {$locId}
                          AND sp_g.customer_group_id = {$custGroupId}
                        LIMIT 1),
                       (SELECT TRIM(REPLACE(REPLACE(sp_f.price, '.', ''), ',', '')) + 0
                        FROM servicesPrice sp_f
                        WHERE sp_f.service_id  = sp_base.service_id
                          AND sp_f.location_id = {$locId}
                        LIMIT 1),
                       0
                   ) AS price
            FROM servicesPrice sp_base
            WHERE sp_base.location_id = {$locId}
            GROUP BY sp_base.service_id
        ) as sp");

        $dataServices = TransactionPetClinicServices::from('transaction_pet_clinic_services as tpcs')
            ->join('services as s', 's.id', '=', 'tpcs.serviceId')
            ->leftJoin($spSub, 's.id', '=', 'sp.service_id')
            ->select(
                's.id as serviceId',
                's.fullName as serviceName',
                DB::raw("TRIM(tpcs.quantity)+0 as quantity"),
                DB::raw("COALESCE(sp.price, 0) as basedPrice"),
            )
            ->where('tpcs.transactionPetClinicId', '=', $request->transactionPetClinicId)
            ->get();

        // Untuk recipes: productLocations hanya untuk filter ketersediaan di lokasi ini,
        // harga diambil dari p.price — pakai distinct agar tidak duplikat
        $dataRecipes = TransactionPetClinicRecipes::from('transaction_pet_clinic_recipes as rc')
            ->join('products as p', 'p.id', '=', 'rc.productId')
            ->join('productLocations as pl', 'p.id', '=', 'pl.productId')
            ->select(
                'p.id as productId',
                'p.fullName as productName',
                DB::raw("TRIM(rc.dosage)+0 AS dosage"),
                DB::raw("TRIM(rc.unit) AS unit"),
                DB::raw("TRIM(rc.frequency)+0 AS frequency"),
                DB::raw("TRIM(rc.duration)+0 AS duration"),
                'rc.giveMedicine',
                'rc.notes',
                DB::raw("p.price+0 as basedPrice")
            )
            ->where('rc.transactionPetClinicId', '=', $request->transactionPetClinicId)
            ->where('pl.locationId', '=', $trans->locationId)
            ->distinct()
            ->get();

        $data = [
            'services' => $dataServices,
            'recipes' => $dataRecipes,
        ];

        return response()->json([
            'customerName' => $cust ? $cust->firstName : '',
            'phoneNumber' => $phone ? $phone->phoneNumber : '',
            'arrivalTime' => $trans->created_at->locale('id')->translatedFormat('l, j F Y H:i'),
            'data' => $data,
        ]);
    }

    public function checkPromo(Request $request)
    {
        // 1. Validasi
        $validate = Validator::make($request->all(), [
            'transactionPetClinicId' => 'required|integer',
        ]);

        if ($validate->fails()) return responseInvalid($validate->errors()->all());

        $trans = TransactionPetClinic::find($request->transactionPetClinicId);
        if (!$trans) return responseInvalid(['Transaction not found!']);

        $custGroup = $trans->customerId ? (Customer::find($trans->customerId)?->customerGroupId ?? "") : "";

        // 2. Ekstraksi Data (Hindari Loop yang berulang)
        $dataRecipes = collect($this->ensureIsArray($request->recipes));
        $dataServices = collect($this->ensureIsArray($request->services));
        $dataProducts = collect($this->ensureIsArray($request->products));

        // Gabungkan semua ID produk dari recipes & products
        $productIds = $dataRecipes->pluck('productId')->merge($dataProducts->pluck('productId'))->filter()->unique()->toArray();
        $serviceIds = $dataServices->pluck('serviceId')->filter()->unique()->toArray();

        // Hitung total transaksi untuk based sales
        $totalTransaction = $dataRecipes->sum('priceOverall') + $dataServices->sum('priceOverall') + $dataProducts->sum('priceOverall');

        $now = Carbon::now();
        $locId = $trans->locationId;

        // --- 3. FREE ITEMS ---
        $tempFree = [];
        if (!empty($productIds)) {
            $tempFree = DB::table('promotionMasters as pm')
                ->leftJoin('promotionCustomerGroups as pcg', 'pm.id', 'pcg.promoMasterId')
                ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
                ->join('promotionFreeItems as fi', 'pm.id', 'fi.promoMasterId')
                ->join('products as pbuy', 'pbuy.id', 'fi.productBuyId')
                ->join('products as pfree', 'pfree.id', 'fi.productFreeId')
                ->whereIn('fi.productBuyId', $productIds) // Ambil semua promo sekaligus
                ->where('pl.locationId', $locId)
                ->where(function ($q) use ($custGroup) {
                    $q->whereNull('pcg.customerGroupId')->orWhere('pcg.customerGroupId', $custGroup);
                })
                ->where('pm.startDate', '<=', $now)->where('pm.endDate', '>=', $now)
                ->where('pm.status', 1)
                ->select('pm.id', 'pm.name', DB::raw("CONCAT('Pembelian ', fi.quantityBuyItem, ' ', pbuy.fullName, ' gratis ', fi.quantityFreeItem, ' ', pfree.fullName) as note"))
                ->distinct()
                ->get()->toArray();
        }

        // --- 4. DISCOUNTS ---
        $tempDiscount = [];

        // Discount Products
        if (!empty($productIds)) {
            $discountProds = DB::table('promotionMasters as pm')
                ->leftJoin('promotionCustomerGroups as pcg', 'pm.id', 'pcg.promoMasterId')
                ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
                ->join('promotion_discount_products as pd', 'pm.id', 'pd.promoMasterId')
                ->join('products as p', 'p.id', 'pd.productId')
                ->whereIn('pd.productId', $productIds)
                ->where('pl.locationId', $locId)
                ->where(function ($q) use ($custGroup) {
                    $q->whereNull('pcg.customerGroupId')->orWhere('pcg.customerGroupId', $custGroup);
                })
                ->where('pm.startDate', '<=', $now)->where('pm.endDate', '>=', $now)
                ->where('pm.status', 1)
                ->select('pm.id', 'pm.name', DB::raw("CONCAT('Pembelian Produk ', p.fullName, CASE WHEN pd.discountType = 'percent' THEN CONCAT(' diskon ', pd.percent, '%') WHEN pd.discountType = 'amount' THEN CONCAT(' diskon Rp ', pd.amount) ELSE '' END) as note"))
                ->distinct()->get()->toArray();
            $tempDiscount = array_merge($tempDiscount, $discountProds);
        }

        // Discount Services
        if (!empty($serviceIds)) {
            $discountServs = DB::table('promotionMasters as pm')
                ->leftJoin('promotionCustomerGroups as pcg', 'pm.id', 'pcg.promoMasterId')
                ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
                ->join('promotion_discount_services as pd', 'pm.id', 'pd.promoMasterId')
                ->join('services as p', 'p.id', 'pd.serviceId') // [FIX BUG] Menggunakan services, bukan products
                ->whereIn('pd.serviceId', $serviceIds)
                ->where('pl.locationId', $locId)
                ->where(function ($q) use ($custGroup) {
                    $q->whereNull('pcg.customerGroupId')->orWhere('pcg.customerGroupId', $custGroup);
                })
                ->where('pm.startDate', '<=', $now)->where('pm.endDate', '>=', $now)
                ->where('pm.status', 1)
                ->select('pm.id', 'pm.name', DB::raw("CONCAT('Pembelian Layanan ', p.fullName, CASE WHEN pd.discountType = 'percent' THEN CONCAT(' diskon ', pd.percent, '%') WHEN pd.discountType = 'amount' THEN CONCAT(' diskon Rp ', pd.amount) ELSE '' END) as note"))
                ->distinct()->get()->toArray();
            $tempDiscount = array_merge($tempDiscount, $discountServs);
        }

        // --- 5. BUNDLES ---
        $resultBundle = [];
        $bundleIds = collect();

        // Cari ID bundle yang terkait dengan produk
        if (!empty($productIds)) {
            $bundleIds = $bundleIds->merge(DB::table('promotionMasters as pm')
                ->leftJoin('promotionCustomerGroups as pcg', 'pm.id', 'pcg.promoMasterId')
                ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
                ->join('promotionBundles as pb', 'pm.id', 'pb.promoMasterId')
                ->join('promotion_bundle_detail_products as pbd', 'pb.id', 'pbd.promoBundleId')
                ->whereIn('pbd.productId', $productIds)
                ->where('pl.locationId', $locId)
                ->where(function ($q) use ($custGroup) {
                    $q->whereNull('pcg.customerGroupId')->orWhere('pcg.customerGroupId', $custGroup);
                })
                ->where('pm.startDate', '<=', $now)->where('pm.endDate', '>=', $now)
                ->where('pm.status', 1)->pluck('pbd.promoBundleId'));
        }

        // Cari ID bundle yang terkait dengan layanan
        if (!empty($serviceIds)) {
            $bundleIds = $bundleIds->merge(DB::table('promotionMasters as pm')
                ->leftJoin('promotionCustomerGroups as pcg', 'pm.id', 'pcg.promoMasterId')
                ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
                ->join('promotionBundles as pb', 'pm.id', 'pb.promoMasterId')
                ->join('promotion_bundle_detail_services as pbd', 'pb.id', 'pbd.promoBundleId')
                ->whereIn('pbd.serviceId', $serviceIds)
                ->where('pl.locationId', $locId)
                ->where(function ($q) use ($custGroup) {
                    $q->whereNull('pcg.customerGroupId')->orWhere('pcg.customerGroupId', $custGroup);
                })
                ->where('pm.startDate', '<=', $now)->where('pm.endDate', '>=', $now)
                ->where('pm.status', 1)->pluck('pbd.promoBundleId'));
        }

        $bundleIds = $bundleIds->unique()->toArray();

        if (!empty($bundleIds)) {
            // Ambil detail item dalam bundle (Products)
            $bundleProds = DB::table('promotion_bundle_detail_products as b')
                ->join('products as p', 'p.id', 'b.productId')
                ->join('promotionBundles as pb', 'pb.id', 'b.promoBundleId')
                ->join('promotionMasters as m', 'pb.promoMasterId', 'm.id')
                ->whereIn('b.promoBundleId', $bundleIds)
                ->select('pb.id', 'b.promoBundleId', 'p.fullName', 'b.quantity', 'pb.price', 'm.name')->get();

            // Ambil detail item dalam bundle (Services)
            $bundleServs = DB::table('promotion_bundle_detail_services as b')
                ->join('services as p', 'p.id', 'b.serviceId')
                ->join('promotionBundles as pb', 'pb.id', 'b.promoBundleId')
                ->join('promotionMasters as m', 'pb.promoMasterId', 'm.id')
                ->whereIn('b.promoBundleId', $bundleIds)
                ->select('pb.id', 'b.promoBundleId', 'p.fullName', 'b.quantity', 'pb.price', 'm.name')->get();

            // Gabungkan detail dan proses pembuat kalimat
            $allBundleDetails = collect($bundleProds)->merge($bundleServs)->groupBy('promoBundleId');

            foreach ($allBundleDetails as $promoBundleId => $items) {
                $kalimat = 'paket bundling ';
                $itemsCount = $items->count();

                foreach ($items->values() as $i => $item) {
                    if ($itemsCount == 1) {
                        $kalimat .= $item->quantity . ' ' . $item->fullName;
                    } else {
                        if ($i == $itemsCount - 1) {
                            $kalimat .= 'dan ' . $item->quantity . ' ' . $item->fullName;
                        } else {
                            $kalimat .= $item->quantity . ' ' . $item->fullName . ', ';
                        }
                    }
                }

                $firstItem = $items->first();
                $kalimat .= ' sebesar Rp ' . $firstItem->price;

                $resultBundle[] = [
                    'id' => $firstItem->id, // Menggunakan pb.id sesuai kode asli
                    'note' => $kalimat,
                    'name' => $firstItem->name
                ];
            }
        }

        // --- 6. BASED SALES ---
        $resultBasedSales = [];
        if ($totalTransaction > 0) {
            $findBasedSales = DB::table('promotionMasters as pm')
                ->leftJoin('promotionCustomerGroups as pcg', 'pm.id', 'pcg.promoMasterId')
                ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
                ->join('promotionBasedSales as bs', 'pm.id', 'bs.promoMasterId')
                ->where('pl.locationId', $locId)->where('pcg.customerGroupId', $custGroup)
                ->where('bs.minPurchase', '<=', $totalTransaction)
                ->where('bs.maxPurchase', '>=', $totalTransaction)
                ->where('pm.startDate', '<=', $now)->where('pm.endDate', '>=', $now)
                ->where('pm.status', 1)
                ->select('pm.id', 'pm.name', 'bs.percentOrAmount', 'bs.percent', 'bs.amount', 'bs.minPurchase')
                ->get();

            foreach ($findBasedSales as $sale) {
                $text = $sale->percentOrAmount == 'percent'
                    ? "Diskon {$sale->percent} % setiap pembelian minimal Rp {$sale->minPurchase}"
                    : "Potongan harga sebesar Rp {$sale->amount} setiap pembelian minimal Rp {$sale->minPurchase}";

                $resultBasedSales[] = [
                    'id' => $sale->id,
                    'note' => $text,
                    'name' => $sale->name
                ];
            }
        }

        // 7. Output Result
        return response()->json([
            'freeItem' => array_values($tempFree),
            'discount' => array_values($tempDiscount),
            'bundles' => $resultBundle,
            'basedSales' => $resultBasedSales,
        ]);
    }

    protected function ensureIsArray($data): array
    {
        if (is_array($data)) {
            return $data;
        }

        if (is_string($data)) {
            $decoded = json_decode($data, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    // Unified endpoint: returns available promos + calculated purchases in one call.
    // Replaces /checkpromo and /discount.
    public function calculate(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'transactionPetClinicId' => 'required|integer',
        ]);
        if ($validate->fails()) return responseInvalid($validate->errors()->all());

        $trans = TransactionPetClinic::find($request->transactionPetClinicId);
        if (!$trans) return responseInvalid(['Transaction not found!']);

        $locId = $trans->locationId;
        $now   = Carbon::now();

        $services = collect($this->ensureIsArray($request->services));
        $recipes  = collect($this->ensureIsArray($request->recipes));
        $products = collect($this->ensureIsArray($request->products));

        $productIds       = $recipes->pluck('productId')->merge($products->pluck('productId'))->filter()->unique()->toArray();
        $serviceIds       = $services->pluck('serviceId')->filter()->unique()->toArray();
        $totalTransaction = $services->sum('priceOverall') + $recipes->sum('priceOverall') + $products->sum('priceOverall');

        $rawSelected    = is_array($request->selectedPromos) ? $request->selectedPromos : $this->ensureIsArray($request->selectedPromos ?? '{}');
        $selFreeItems   = $rawSelected['freeItems']   ?? [];
        $selDiscounts   = $rawSelected['discounts']   ?? [];
        $selBundles     = $rawSelected['bundles']     ?? [];
        $selBasedSaleId = $rawSelected['basedSaleId'] ?? null;

        // 1. Available promos (no customer group filter)
        $availablePromos = $this->fetchAvailablePromos($productIds, $serviceIds, $locId, $now, $totalTransaction);

        // 2. Apply selected promos → purchases
        $allPromoIds = array_unique(array_merge($selFreeItems, $selDiscounts, $selBundles));
        $allPromos   = !empty($allPromoIds) ? $this->getLookupPromos($allPromoIds, $locId) : [
            'freeItems'     => collect(),
            'svcDiscounts'  => collect(),
            'prodDiscounts' => collect(),
            'bundles'       => collect(),
            'bundleDetails' => collect(),
        ];

        $fetchProductIds = $recipes->pluck('productId')->merge($products->pluck('productId'))->filter()->unique()->toArray();
        $fetchServiceIds = $services->pluck('serviceId')->filter()->unique()->toArray();
        $productNames    = !empty($fetchProductIds) ? DB::table('products')->whereIn('id', $fetchProductIds)->pluck('fullName', 'id') : collect();
        $serviceNames    = !empty($fetchServiceIds) ? DB::table('services')->whereIn('id', $fetchServiceIds)->pluck('fullName', 'id') : collect();

        $allPurchaseItems = $services->map(fn($i) => array_merge((array)$i, ['_type' => 'service']))
            ->concat($recipes->map(fn($i) => array_merge((array)$i, [
                '_type'    => 'product',
                'quantity' => ($i['dosage'] ?? 0) * ($i['frequency'] ?? 0) * ($i['duration'] ?? 0),
            ])))
            ->concat($products->map(fn($i) => array_merge((array)$i, ['_type' => 'product'])));

        $results       = [];
        $promoNotes    = [];
        $subtotal      = 0;
        $totalDiscount = 0;

        // ── PHASE 1: Proses bundle SEBELUM loop item ───────────────────────
        // Bundle merupakan paket — prosesnya satu kali, lalu tandai semua item
        // yang termasuk bundle agar di-skip di loop utama.
        $bundleConsumedProductIds = [];
        $bundleConsumedServiceIds = [];

        foreach ($selBundles as $bId) {
            // Gunakan explicit cast agar type mismatch (string vs int dari PDO) tidak masalah
            $promo = $allPromos['bundles']->first(fn($b) => (int)($b->promoId ?? 0) === (int)$bId);
            if (!$promo) continue;

            $bundleId = (int) $promo->bundleId;

            // Direct DB query — lebih reliable daripada collection lookup
            $includedProducts = DB::table('promotion_bundle_detail_products as pbd')
                ->join('products as p', 'p.id', 'pbd.productId')
                ->where('pbd.promoBundleId', $bundleId)
                ->select(
                    'pbd.promoBundleId',
                    'p.id as productId',
                    DB::raw('null as serviceId'),
                    'p.fullName as name',
                    DB::raw('p.price+0 as normal_price'),
                    'pbd.quantity'
                )
                ->get();

            $includedServices = DB::table('promotion_bundle_detail_services as pbd')
                ->join('services as s', 's.id', 'pbd.serviceId')
                ->where('pbd.promoBundleId', $bundleId)
                ->select(
                    'pbd.promoBundleId',
                    DB::raw('null as productId'),
                    's.id as serviceId',
                    's.fullName as name',
                    DB::raw('null as normal_price'),
                    'pbd.quantity'
                )
                ->get();

            $included    = $includedProducts->concat($includedServices)->map(fn($d) => (array) $d)->toArray();
            $normalTotal = array_sum(array_column($included, 'normal_price'));

            // Tandai semua item dalam bundle sebagai "consumed"
            foreach ($included as $bItem) {
                if (!empty($bItem['productId'])) $bundleConsumedProductIds[] = (int) $bItem['productId'];
                if (!empty($bItem['serviceId'])) $bundleConsumedServiceIds[] = (int) $bItem['serviceId'];
            }

            $results[]    = [
                'item_name'      => $promo->item_name,
                'category'       => '',
                'quantity'       => 1,
                'bonus'          => 0,
                'discount'       => 0,
                'unit_price'     => $promo->bundlePrice,
                'total'          => $promo->bundlePrice,
                'included_items' => $included,
                'promoId'        => $promo->promoId,
                'promoCategory'  => 'bundle',
            ];
            $subtotal    += $promo->bundlePrice;
            $promoNotes[] = "{$promo->item_name} only Rp " . number_format($promo->bundlePrice) . " (Save Rp " . number_format(max(0, $normalTotal - $promo->bundlePrice)) . ")";
        }

        // ── PHASE 2: Loop item — skip item yang sudah di-cover bundle ──────
        foreach ($allPurchaseItems as $item) {
            $isGetPromo = false;
            $type       = $item['_type'];
            $itemId     = $type === 'service' ? ($item['serviceId'] ?? null) : ($item['productId'] ?? null);

            // Skip jika item ini sudah masuk ke dalam bundle yang dipilih
            if ($type === 'product' && in_array((int) $itemId, $bundleConsumedProductIds)) continue;
            if ($type === 'service' && in_array((int) $itemId, $bundleConsumedServiceIds)) continue;

            // A. Free Item
            if ($type === 'product' && !empty($selFreeItems)) {
                foreach ($selFreeItems as $fId) {
                    $promo = $allPromos['freeItems']->where('promoId', $fId)->where('productBuyId', $itemId)->first();
                    if ($promo) {
                        $results[] = [
                            'promoId'         => $promo->promoId,
                            'item_name'       => $promo->item_name,
                            'buy_product_id'  => $promo->productBuyId,
                            'free_product_id' => $promo->productFreeId,
                            'category'        => $promo->category,
                            'quantity'        => $promo->quantityBuy,
                            'bonus'           => $promo->quantityFree,
                            'discount'        => 0,
                            'unit_price'      => $item['eachPrice'] ?? 0,
                            'total'           => $item['priceOverall'] ?? 0,
                            'promoCategory'   => 'freeItem',
                            'note'            => "Beli {$promo->quantityBuy} {$promo->item_name} Gratis {$promo->quantityFree}",
                        ];
                        $subtotal     += $item['priceOverall'] ?? 0;
                        $promoNotes[]  = "Beli {$promo->quantityBuy} Gratis {$promo->quantityFree}";
                        $isGetPromo    = true;
                        break;
                    }
                }
            }

            // C. Discount
            if (!$isGetPromo && !empty($selDiscounts)) {
                foreach ($selDiscounts as $dId) {
                    $lookupTable = $type === 'service' ? $allPromos['svcDiscounts'] : $allPromos['prodDiscounts'];
                    $promo       = $lookupTable->where('promoId', $dId)->where($type . 'Id', $itemId)->first();
                    if ($promo) {
                        $discountValue = $promo->discountType === 'percent'
                            ? ($promo->percent / 100) * ($item['eachPrice'] ?? 0)
                            : $promo->amount;
                        $saved       = $discountValue * ($item['quantity'] ?? 1);
                        $results[]   = [
                            'item_name'    => $promo->item_name,
                            'category'     => $promo->category,
                            'quantity'     => $item['quantity'] ?? 1,
                            'bonus'        => 0,
                            'discount'     => $promo->discountType === 'percent' ? $promo->percent : $promo->amount,
                            'discountType' => $promo->discountType,
                            'unit_price'   => $item['eachPrice'] ?? 0,
                            'total'        => ($item['priceOverall'] ?? 0) - $saved,
                            'promoId'      => $promo->promoId,
                            $type . 'Id'   => $itemId,
                            'promoCategory'=> 'discount',
                        ];
                        $subtotal      += ($item['priceOverall'] ?? 0) - $saved;
                        $totalDiscount += $saved;
                        $promoNotes[]   = "Diskon {$promo->item_name} sebesar " . ($promo->discountType === 'percent' ? $promo->percent . '%' : 'Rp ' . number_format($promo->amount));
                        $isGetPromo     = true;
                        break;
                    }
                }
            }

            // D. No Promo
            if (!$isGetPromo) {
                $itemName  = $type === 'service'
                    ? (($serviceNames[$itemId] ?? '') ?: ($item['name'] ?? 'Layanan'))
                    : (($productNames[$itemId] ?? '') ?: ($item['name'] ?? 'Produk'));
                $results[] = [
                    'promoId'    => null,
                    $type . 'Id' => $itemId,
                    'item_name'  => $itemName,
                    'category'   => $item['category'] ?? '',
                    'quantity'   => $item['quantity'] ?? 1,
                    'bonus'      => 0,
                    'discount'   => 0,
                    'unit_price' => $item['eachPrice'] ?? 0,
                    'total'      => $item['priceOverall'] ?? 0,
                    'note'       => '',
                ];
                $subtotal += $item['priceOverall'] ?? 0;
            }
        }

        // Based Sale
        $discountBasedSales = 0;
        $discountNote       = '';
        if ($selBasedSaleId) {
            $sale = DB::table('promotionMasters as pm')
                ->join('promotionBasedSales as pb', 'pm.id', 'pb.promoMasterId')
                ->where('pm.id', $selBasedSaleId)
                ->where('pb.minPurchase', '<=', $subtotal)
                ->where('pb.maxPurchase', '>=', $subtotal)
                ->first();
            if ($sale) {
                $isPercent          = $sale->percentOrAmount === 'percent';
                $discountBasedSales = $isPercent ? ($subtotal * ($sale->percent / 100)) : $sale->amount;
                $totalDiscount      = $discountBasedSales;
                $discountNote       = "Diskon " . ($isPercent ? $sale->percent . ' %' : 'Nominal') . " (Belanja > Rp " . number_format($sale->minPurchase) . ")";
                $promoNotes[]       = "Diskon Belanja > Rp " . number_format($sale->minPurchase);
            }
        }

        return response()->json([
            'purchases'       => $results,
            'availablePromos' => $availablePromos,
            'summary'         => [
                'subtotal'               => (float) $subtotal,
                'total_discount'         => (float) $totalDiscount,
                'discount_based_sales'   => (float) $discountBasedSales,
                'discount_note'          => $discountNote,
                'total_payment'          => (float) ($subtotal - $totalDiscount),
                'promo_notes'            => $promoNotes,
                'selected_based_sale_id' => $selBasedSaleId,
            ],
        ]);
    }

    public function getPaymentMethods()
    {
        $methods = DB::table('paymentMethodFinances')
            ->where('isDeleted', 0)
            ->select('id', 'paymentMethod as name')
            ->orderBy('paymentMethod')
            ->get();
        return response()->json($methods);
    }

    public function getPaymentHistory(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'transactionPetClinicId' => 'required|integer',
        ]);
        if ($validate->fails()) return responseInvalid($validate->errors()->all());

        $transId = $request->transactionPetClinicId;

        $totals = DB::table('transaction_pet_clinic_payment_totals as t')
            ->leftJoin('paymentMethodFinances as pm', 'pm.id', 't.paymentmethodId')
            ->where('t.transactionId', $transId)
            ->where('t.isDeleted', 0)
            ->select(
                't.id',
                't.nota_number',
                't.amount',
                't.amountPaid',
                't.nextPayment',
                'pm.paymentMethod as payment_method_name',
                't.created_at'
            )
            ->orderBy('t.created_at')
            ->get();

        $totalTagihan = $totals->first()?->amount ?? 0;
        $totalPaid    = $totals->sum('amountPaid');
        $remaining    = max(0, (float)$totalTagihan - (float)$totalPaid);

        return response()->json([
            'total_tagihan' => (float) $totalTagihan,
            'total_paid'    => (float) $totalPaid,
            'remaining'     => (float) $remaining,
            'is_paid_off'   => $remaining <= 0,
            'payments'      => $totals,
        ]);
    }

    public function transactionDiscount(Request $request)
    {
        // 1. Inisialisasi & Safety Check
        $services = collect($this->ensureIsArray($request->services));
        $recipes  = collect($this->ensureIsArray($request->recipes));
        $products = collect($this->ensureIsArray($request->products));

        $trans = TransactionPetClinic::find($request->transactionPetClinicId);
        if (!$trans) return response()->json(['error' => 'Transaction not found'], 404);

        $locationId = $trans->locationId;

        // 2. Ambil Semua Data Promo Sekaligus (Menghindari N+1 Query)
        $promoIds = array_unique(array_merge(
            $this->ensureIsArray($request->discounts),
            $this->ensureIsArray($request->bundles),
            $this->ensureIsArray($request->freeItems)
        ));

        $allPromos = $this->getLookupPromos($promoIds, $locationId);

        // Pre-fetch nama produk & layanan untuk item tanpa promo (hindari N+1)
        $fetchProductIds = $recipes->pluck('productId')->merge($products->pluck('productId'))->filter()->unique()->toArray();
        $fetchServiceIds = $services->pluck('serviceId')->filter()->unique()->toArray();
        $productNames = !empty($fetchProductIds)
            ? DB::table('products')->whereIn('id', $fetchProductIds)->pluck('fullName', 'id')
            : collect();
        $serviceNames = !empty($fetchServiceIds)
            ? DB::table('services')->whereIn('id', $fetchServiceIds)->pluck('fullName', 'id')
            : collect();

        $results = [];
        $promoNotes = [];
        $subtotal = 0;
        $totalDiscount = 0;

        // 3. Gabungkan semua item menjadi satu koleksi untuk diproses secara seragam
        // Recipe dikonversi ke format product dengan quantity yang sudah dikalkulasi
        $allPurchaseItems = $services->map(fn($item) => array_merge($item, ['_type' => 'service']))
            ->concat($recipes->map(fn($item) => array_merge($item, [
                '_type' => 'product',
                'quantity' => ($item['dosage'] ?? 0) * ($item['frequency'] ?? 0) * ($item['duration'] ?? 0)
            ])))
            ->concat($products->map(fn($item) => array_merge($item, ['_type' => 'product'])));

        // 4. Proses Per Item
        foreach ($allPurchaseItems as $item) {
            $isGetPromo = false;
            $type = $item['_type'];
            $itemId = ($type === 'service') ? ($item['serviceId'] ?? null) : ($item['productId'] ?? null);

            // A. Cek Free Items (Hanya untuk produk)
            if ($type === 'product' && $request->has('freeItems')) {
                foreach ($this->ensureIsArray($request->freeItems) as $fId) {
                    $promo = $allPromos['freeItems']->where('promoId', $fId)->where('productBuyId', $itemId)->first();
                    if ($promo) {
                        $results[] = [
                            'promoId' => $promo->promoId,
                            'item_name' => $promo->item_name,
                            'buy_product_id' => $promo->productBuyId,
                            'free_product_id' => $promo->productFreeId,
                            'category' => $promo->category,
                            'quantity' => $promo->quantityBuy,
                            'bonus' => $promo->quantityFree,
                            'discount' => 0,
                            'unit_price' => $item['eachPrice'],
                            'total' => $item['priceOverall'],
                            'promoCategory' => 'freeItem',
                            'note' => "Beli {$promo->quantityBuy} {$promo->item_name} Gratis {$promo->quantityFree}"
                        ];
                        $subtotal += $item['priceOverall'];
                        $promoNotes[] = "Beli {$promo->quantityBuy} Gratis {$promo->quantityFree}";
                        $isGetPromo = true;
                        break;
                    }
                }
            }

            // B. Cek Bundles
            if (!$isGetPromo && $request->has('bundles')) {
                foreach ($this->ensureIsArray($request->bundles) as $bId) {
                    $promo = $allPromos['bundles']->where('promoId', $bId)->first();
                    if ($promo) {
                        // Ambil included items dari lookup yang sudah kita siapkan
                        $included = $allPromos['bundleDetails']->where('promoBundleId', $promo->bundleId)->values()->toArray();
                        $normalTotal = array_sum(array_column($included, 'normal_price'));

                        $results[] = [
                            'item_name' => $promo->item_name,
                            'category' => '',
                            'quantity' => 1,
                            'bonus' => 0,
                            'discount' => 0,
                            'unit_price' => $promo->bundlePrice,
                            'total' => $promo->bundlePrice,
                            'included_items' => $included,
                            'promoId' => $promo->promoId,
                            'promoCategory' => 'bundle',
                        ];
                        $subtotal += $promo->bundlePrice;
                        $promoNotes[] = "{$promo->item_name} only Rp " . number_format($promo->bundlePrice) . " (Save Rp " . number_format($normalTotal - $promo->bundlePrice) . ")";
                        $isGetPromo = true;
                        break;
                    }
                }
            }

            // C. Cek Diskon Biasa
            if (!$isGetPromo && $request->has('discounts')) {
                foreach ($this->ensureIsArray($request->discounts) as $dId) {
                    $lookupTable = ($type === 'service') ? $allPromos['svcDiscounts'] : $allPromos['prodDiscounts'];
                    $promo = $lookupTable->where('promoId', $dId)->where($type . 'Id', $itemId)->first();

                    if ($promo) {
                        $discountValue = ($promo->discountType === 'percent')
                            ? ($promo->percent / 100) * $item['eachPrice']
                            : $promo->amount;

                        // percent: discount per unit × quantity; amount: fixed per unit × quantity
                        $saved = $discountValue * ($item['quantity'] ?? 1);

                        $results[] = [
                            'item_name' => $promo->item_name,
                            'category' => $promo->category,
                            'quantity' => $item['quantity'],
                            'bonus' => 0,
                            'discount' => $promo->discountType === 'percent' ? $promo->percent : $promo->amount,
                            'unit_price' => $item['eachPrice'],
                            'total' => $item['priceOverall'] - $saved,
                            'promoId' => $promo->promoId,
                            $type . 'Id' => $itemId,
                            'promoCategory' => 'discount',
                        ];
                        $subtotal += ($item['priceOverall'] - $saved);
                        $totalDiscount += $saved;
                        $promoNotes[] = "Diskon {$promo->item_name} sebesar " . ($promo->discountType === 'percent' ? $promo->percent . '%' : 'Rp ' . number_format($promo->amount));
                        $isGetPromo = true;
                        break;
                    }
                }
            }

            // D. Tanpa Promo
            if (!$isGetPromo) {
                // Gunakan ?: bukan ?? agar fallback juga untuk empty string dari DB
                $itemName = $type === 'service'
                    ? (($serviceNames[$itemId] ?? '') ?: ($item['name'] ?? 'Layanan'))
                    : (($productNames[$itemId] ?? '') ?: ($item['name'] ?? 'Produk'));

                $results[] = [
                    'promoId' => null,
                    $type . 'Id' => $itemId,
                    'item_name' => $itemName,
                    'category' => $item['category'] ?? '',
                    'quantity' => $item['quantity'],
                    'bonus' => 0,
                    'discount' => 0,
                    'unit_price' => $item['eachPrice'] ?? 0,
                    'total' => $item['priceOverall'] ?? 0,
                    'note' => ''
                ];
                $subtotal += $item['priceOverall'] ?? 0;
            }
        }

        // 5. Perhitungan Based Sales (Logika Akhir)
        $discountBasedSales = 0;
        $discountNote = '';
        if ($request->basedSale) {
            $sale = DB::table('promotionMasters as pm')
                ->join('promotionBasedSales as pb', 'pm.id', 'pb.promoMasterId')
                ->where('pm.id', $request->basedSale)
                ->where('pb.minPurchase', '<=', $subtotal)
                ->where('pb.maxPurchase', '>=', $subtotal)
                ->first();

            if ($sale) {
                $isPercent = $sale->percentOrAmount === 'percent';
                $discountBasedSales = $isPercent ? ($subtotal * ($sale->percent / 100)) : $sale->amount;
                $totalDiscount = $discountBasedSales; // Sesuai permintaan: meng-override total_discount
                $discountNote = "Diskon " . ($isPercent ? $sale->percent . ' %' : 'Nominal') . " (Belanja > Rp " . number_format($sale->minPurchase) . ")";
                $promoNotes[] = "Diskon Belanja > Rp " . number_format($sale->minPurchase);
            }
        }

        return response()->json([
            'purchases' => $results,
            'subtotal' => (float)$subtotal,
            'discount_note' => $discountNote,
            'discount_based_sales' => (float)$discountBasedSales,
            'total_discount' => (float)$totalDiscount,
            'total_payment' => (float)($subtotal - $totalDiscount),
            'promo_notes' => $promoNotes,
            'promoBasedSaleId' => $request->basedSale
        ]);
    }

    private function getLookupPromos($ids, $locationId)
    {
        return [
            'svcDiscounts' => DB::table('promotionMasters as pm')
                ->join('promotion_discount_services as pd', 'pm.id', 'pd.promoMasterId')
                ->join('services as s', 's.id', 'pd.serviceId')
                ->whereIn('pm.id', $ids)
                ->select('pm.id as promoId', 's.id as serviceId', 's.fullName as item_name', 's.type as category', 'pd.*')->get(),

            'prodDiscounts' => DB::table('promotionMasters as pm')
                ->join('promotion_discount_products as pd', 'pm.id', 'pd.promoMasterId')
                ->join('products as p', 'p.id', 'pd.productId')
                ->whereIn('pm.id', $ids)
                ->select('pm.id as promoId', 'p.id as productId', 'p.fullName as item_name', 'p.category', 'pd.*')->get(),

            'bundles' => DB::table('promotionMasters as pm')
                ->join('promotionBundles as pb', 'pm.id', 'pb.promoMasterId')
                ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
                ->whereIn('pm.id', $ids)
                ->where('pl.locationId', $locationId)
                ->select('pm.id as promoId', 'pm.name as item_name', 'pb.price as bundlePrice', 'pb.id as bundleId')->get(),

            'bundleDetails' => DB::table('promotion_bundle_detail_products as pbd')
                ->join('products as p', 'p.id', 'pbd.productId')
                ->select('pbd.promoBundleId', 'p.id as productId', DB::raw('null as serviceId'), 'p.fullName as name', 'p.price as normal_price', 'pbd.quantity')
                ->get()
                ->concat(
                    DB::table('promotion_bundle_detail_services as pbd')
                        ->join('services as s', 's.id', 'pbd.serviceId')
                        ->select('pbd.promoBundleId', DB::raw('null as productId'), 's.id as serviceId', 's.fullName as name', DB::raw('null as normal_price'), 'pbd.quantity')
                        ->get()
                ),

            'freeItems' => DB::table('promotionMasters as pm')
                ->join('promotionFreeItems as fi', 'pm.id', 'fi.promoMasterId')
                ->join('products as p', 'p.id', 'fi.productBuyId')
                ->whereIn('pm.id', $ids)
                ->select('pm.id as promoId', 'p.fullName as item_name', 'p.category', 'fi.productBuyId', 'fi.productFreeId', 'fi.quantityBuyItem as quantityBuy', 'fi.quantityFreeItem as quantityFree')->get(),
        ];
    }

    private function fetchAvailablePromos(array $productIds, array $serviceIds, int $locId, $now, float $totalTransaction): array
    {
        // Free Items (no customer group filter)
        $freeItems = [];
        if (!empty($productIds)) {
            $freeItems = DB::table('promotionMasters as pm')
                ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
                ->join('promotionFreeItems as fi', 'pm.id', 'fi.promoMasterId')
                ->join('products as pbuy', 'pbuy.id', 'fi.productBuyId')
                ->join('products as pfree', 'pfree.id', 'fi.productFreeId')
                ->whereIn('fi.productBuyId', $productIds)
                ->where('pl.locationId', $locId)
                ->where('pm.startDate', '<=', $now)->where('pm.endDate', '>=', $now)
                ->where('pm.status', 1)
                ->select('pm.id', 'pm.name', DB::raw("CONCAT('Pembelian ', fi.quantityBuyItem, ' ', pbuy.fullName, ' gratis ', fi.quantityFreeItem, ' ', pfree.fullName) as note"))
                ->distinct()->get()->toArray();
        }

        // Discounts
        $discounts = [];
        if (!empty($productIds)) {
            $discountProds = DB::table('promotionMasters as pm')
                ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
                ->join('promotion_discount_products as pd', 'pm.id', 'pd.promoMasterId')
                ->join('products as p', 'p.id', 'pd.productId')
                ->whereIn('pd.productId', $productIds)
                ->where('pl.locationId', $locId)
                ->where('pm.startDate', '<=', $now)->where('pm.endDate', '>=', $now)
                ->where('pm.status', 1)
                ->select('pm.id', 'pm.name', DB::raw("CONCAT('Pembelian Produk ', p.fullName, CASE WHEN pd.discountType = 'percent' THEN CONCAT(' diskon ', pd.percent, '%') WHEN pd.discountType = 'amount' THEN CONCAT(' diskon Rp ', pd.amount) ELSE '' END) as note"))
                ->distinct()->get()->toArray();
            $discounts = array_merge($discounts, $discountProds);
        }
        if (!empty($serviceIds)) {
            $discountServs = DB::table('promotionMasters as pm')
                ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
                ->join('promotion_discount_services as pd', 'pm.id', 'pd.promoMasterId')
                ->join('services as p', 'p.id', 'pd.serviceId')
                ->whereIn('pd.serviceId', $serviceIds)
                ->where('pl.locationId', $locId)
                ->where('pm.startDate', '<=', $now)->where('pm.endDate', '>=', $now)
                ->where('pm.status', 1)
                ->select('pm.id', 'pm.name', DB::raw("CONCAT('Pembelian Layanan ', p.fullName, CASE WHEN pd.discountType = 'percent' THEN CONCAT(' diskon ', pd.percent, '%') WHEN pd.discountType = 'amount' THEN CONCAT(' diskon Rp ', pd.amount) ELSE '' END) as note"))
                ->distinct()->get()->toArray();
            $discounts = array_merge($discounts, $discountServs);
        }

        // Bundles
        $resultBundle = [];
        $bundleIds    = collect();
        if (!empty($productIds)) {
            $bundleIds = $bundleIds->merge(DB::table('promotionMasters as pm')
                ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
                ->join('promotionBundles as pb', 'pm.id', 'pb.promoMasterId')
                ->join('promotion_bundle_detail_products as pbd', 'pb.id', 'pbd.promoBundleId')
                ->whereIn('pbd.productId', $productIds)
                ->where('pl.locationId', $locId)
                ->where('pm.startDate', '<=', $now)->where('pm.endDate', '>=', $now)
                ->where('pm.status', 1)->pluck('pbd.promoBundleId'));
        }
        if (!empty($serviceIds)) {
            $bundleIds = $bundleIds->merge(DB::table('promotionMasters as pm')
                ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
                ->join('promotionBundles as pb', 'pm.id', 'pb.promoMasterId')
                ->join('promotion_bundle_detail_services as pbd', 'pb.id', 'pbd.promoBundleId')
                ->whereIn('pbd.serviceId', $serviceIds)
                ->where('pl.locationId', $locId)
                ->where('pm.startDate', '<=', $now)->where('pm.endDate', '>=', $now)
                ->where('pm.status', 1)->pluck('pbd.promoBundleId'));
        }

        $bundleIds = $bundleIds->unique()->toArray();
        if (!empty($bundleIds)) {
            $bundleProds = DB::table('promotion_bundle_detail_products as b')
                ->join('products as p', 'p.id', 'b.productId')
                ->join('promotionBundles as pb', 'pb.id', 'b.promoBundleId')
                ->join('promotionMasters as m', 'pb.promoMasterId', 'm.id')
                ->whereIn('b.promoBundleId', $bundleIds)
                ->select('pb.id', 'b.promoBundleId', 'p.fullName', 'b.quantity', 'pb.price', 'm.name', 'm.id as promoMasterId')->get();
            $bundleServs = DB::table('promotion_bundle_detail_services as b')
                ->join('services as p', 'p.id', 'b.serviceId')
                ->join('promotionBundles as pb', 'pb.id', 'b.promoBundleId')
                ->join('promotionMasters as m', 'pb.promoMasterId', 'm.id')
                ->whereIn('b.promoBundleId', $bundleIds)
                ->select('pb.id', 'b.promoBundleId', 'p.fullName', 'b.quantity', 'pb.price', 'm.name', 'm.id as promoMasterId')->get();

            $allBundleDetails = collect($bundleProds)->merge($bundleServs)->groupBy('promoBundleId');
            foreach ($allBundleDetails as $promoBundleId => $items) {
                $kalimat    = 'paket bundling ';
                $itemsCount = $items->count();
                foreach ($items->values() as $i => $item) {
                    if ($itemsCount == 1) {
                        $kalimat .= $item->quantity . ' ' . $item->fullName;
                    } elseif ($i == $itemsCount - 1) {
                        $kalimat .= 'dan ' . $item->quantity . ' ' . $item->fullName;
                    } else {
                        $kalimat .= $item->quantity . ' ' . $item->fullName . ', ';
                    }
                }
                $firstItem      = $items->first();
                $kalimat       .= ' sebesar Rp ' . $firstItem->price;
                // Gunakan promoMasterId (pm.id) bukan pb.id, agar konsisten dengan getLookupPromos
                $resultBundle[] = ['id' => $firstItem->promoMasterId, 'name' => $firstItem->name, 'note' => $kalimat];
            }
        }

        // Based Sales
        $basedSales = [];
        if ($totalTransaction > 0) {
            $findBasedSales = DB::table('promotionMasters as pm')
                ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
                ->join('promotionBasedSales as bs', 'pm.id', 'bs.promoMasterId')
                ->where('pl.locationId', $locId)
                ->where('bs.minPurchase', '<=', $totalTransaction)
                ->where('bs.maxPurchase', '>=', $totalTransaction)
                ->where('pm.startDate', '<=', $now)->where('pm.endDate', '>=', $now)
                ->where('pm.status', 1)
                ->select('pm.id', 'pm.name', 'bs.percentOrAmount', 'bs.percent', 'bs.amount', 'bs.minPurchase')
                ->distinct()->get();
            foreach ($findBasedSales as $sale) {
                $text         = $sale->percentOrAmount == 'percent'
                    ? "Diskon {$sale->percent} % setiap pembelian minimal Rp {$sale->minPurchase}"
                    : "Potongan harga sebesar Rp {$sale->amount} setiap pembelian minimal Rp {$sale->minPurchase}";
                $basedSales[] = ['id' => $sale->id, 'name' => $sale->name, 'note' => $text];
            }
        }

        return [
            'freeItems'  => array_values($freeItems),
            'discounts'  => array_values($discounts),
            'bundles'    => $resultBundle,
            'basedSales' => $basedSales,
        ];
    }

    //pembayaran rawat inap — implementasi lengkap ada di bawah (paymentInpatient)

    //pembayaran rawat jalan
    public function paymentOutpatient(Request $request)
    {
        // 1. Validasi Awal & Parsing Data
        // Parse purchases dulu sebelum validasi — data bisa datang sebagai JSON string dari frontend
        $request->merge(['purchases' => $this->ensureIsArray($request->purchases)]);

        $validate = Validator::make($request->all(), [
            'transactionPetClinicId' => 'required|integer',
            'payment_method'         => 'required',
            'detail_total'           => 'required',
            'purchases'              => 'required|array',
        ]);

        if ($validate->fails()) return responseInvalid($validate->errors()->all());

        $payment   = json_decode($request->payment_method, true);
        $detail    = json_decode($request->detail_total, true);
        $purchases = $request->purchases;
        $userId = $request->user()->id;
        $transId = $request->transactionPetClinicId;

        $trans = TransactionPetClinic::find($transId);
        if (!$trans) return responseInvalid(['Transaction not found!']);

        // 2. Pre-fetch Semua Promo (Optimasi N+1 Query)
        $promoIds = collect($purchases)->pluck('promoId')->filter()->unique()->toArray();
        if (isset($detail['promoBasedSaleId'])) {
            $promoIds[] = $detail['promoBasedSaleId'];
        }
        $promos = PromotionMaster::whereIn('id', $promoIds)->get()->keyBy('id');

        try {
            DB::beginTransaction();

            foreach ($purchases as $value) {
                $promoId = $value['promoId'] ?? null;
                $promo = ($promoId && $promoId !== 'null') ? $promos->get($promoId) : null;

                // Pastikan Promo Ada jika ID dikirim
                if ($promoId && $promoId !== 'null' && !$promo) {
                    throw new \Exception("Promotion ID {$promoId} not found!");
                }

                // Inisialisasi Model Payment Detail
                $trx = new transaction_pet_clinic_payments();
                $trx->transactionId = $transId;
                $trx->paymentMethodId = $payment['paymentMethodId'];
                $trx->userId = $userId;
                $trx->promoId = $promo ? $promo->id : null;
                $trx->price = $value['unit_price'] ?? 0;
                $trx->priceOverall = $value['total'] ?? 0;
                $trx->quantity = $value['quantity'] ?? 1;

                // A. Logika Berdasarkan Tipe Item (Service / Product / Bundle)
                if (isset($value['buy_product_id'])) {
                    // Free Item Logic
                    $trx->productBuyId = $value['buy_product_id'];
                    $trx->productFreeId = $value['free_product_id'];
                    $trx->quantity = $value['quantity'] + ($value['bonus'] ?? 0);
                    $trx->quantityBuy = $value['quantity'];
                    $trx->quantityFree = $value['bonus'] ?? 0;
                } elseif (isset($value['promoCategory']) && $value['promoCategory'] === 'bundle') {
                    // Bundle Logic
                    $trx->isBundle = true;
                    $trx->save(); // Simpan dulu untuk dapat ID buat bundle detail

                    foreach ($value['included_items'] as $item) {
                        $bundle = new transaction_pet_clinic_payment_bundle();
                        $bundle->paymentId = $trx->id;
                        $bundle->promoId = $promo->id;
                        $bundle->serviceId = $item['serviceId'] ?? null;
                        $bundle->productId = $item['productId'] ?? null;
                        $bundle->quantity = $item['quantity'];
                        $bundle->amount = $item['normal_price'] ?? 0;
                        $bundle->userId = $userId;
                        $bundle->save();
                    }
                    continue; // Skip save di bawah karena sudah dihandle bundle
                } elseif (isset($value['serviceId'])) {
                    $trx->serviceId = $value['serviceId'];
                } elseif (isset($value['productId'])) {
                    $trx->productId = $value['productId'];
                }

                // B. Logika Diskon (Hanya jika ada promo dan bukan bundle)
                if ($promo && ($value['discount'] ?? 0) > 0) {
                    $trx->discountType = $value['discountType'] ?? 'amount';
                    if ($trx->discountType === 'percent') {
                        $trx->discountPercent = $value['discount'];
                        $trx->discountAmount = 0;
                    } else {
                        $trx->discountAmount = $value['discount'];
                        $trx->discountPercent = 0;
                    }
                }

                $trx->save();
            }

            // 3. Simpan Based Sales (Diskon belanja total)
            $promoBasedSaleIdOut = $detail['promoBasedSaleId'] ?? null;
            if ($promoBasedSaleIdOut !== null && $promoBasedSaleIdOut !== '' && $promoBasedSaleIdOut !== 'null') {
                $sales = new transaction_pet_clinic_payment_based_sales();
                $sales->transactionId = $transId;
                $sales->paymentMethodId = $payment['paymentMethodId'];
                $sales->promoId = (int) $promoBasedSaleIdOut;
                $sales->amountDiscount = $detail['discount_based_sales'] ?? 0;
                $sales->userId = $userId;
                $sales->save();
            }

            // 4. Simpan Total & Generate Nota (Gunakan Lock untuk keamanan nomor urut)
            $total = new transaction_pet_clinic_payment_total();
            $total->transactionId = $transId;
            $total->paymentmethodId = $payment['paymentMethodId'];
            $total->amount = $detail['total_payment'];
            $total->amountPaid = $payment['amountPaid'];
            $total->nextPayment = $payment['next_payment'] ?? null;
            $total->duration = $payment['duration'] ?? null;
            $total->tenor = $payment['tenor'] ?? null;

            $now = Carbon::now();
            $tahun = $now->format('Y');
            $bulan = $now->format('m');

            // Menghitung jumlah untuk nomor nota (Lock row untuk menghindari duplikat di waktu bersamaan)
            $jumlahTransaksi = DB::table('transaction_pet_clinic_payment_totals as tp')
                ->join('transactionPetClinics as tpc', 'tp.transactionId', '=', 'tpc.id')
                ->where('tpc.locationId', $trans->locationId)
                ->whereYear('tp.created_at', $tahun)
                ->whereMonth('tp.created_at', $bulan)
                ->lockForUpdate()
                ->count();

            $nomorUrut = str_pad($jumlahTransaksi + 1, 4, '0', STR_PAD_LEFT);
            $total->nota_number = "INV/PC/{$trans->locationId}/{$tahun}/{$bulan}/{$nomorUrut}";
            $total->userId = $userId;
            $total->save();

            // 5. Update Status & Log
            transactionPetClinicLog($transId, 'Nota diterbitkan', $total->nota_number, $userId);
            statusTransactionPetClinic($transId, 'Menunggu konfirmasi pembayaran', $userId);

            DB::commit();

            updateLastTransaction($trans->customerId);
            return response()->json(['id' => $total->id, 'message' => 'Add Data Successful!'], 200);
        } catch (\Throwable $th) {
            DB::rollback();
            return responseInvalid([$th->getMessage() . " at line " . $th->getLine()]);
        }
    }

    public function createList(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'category' => 'required|string|in:weight,temperature,breath,sound,heart,vaginal',
            'name' => 'required|string'
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return responseInvalid($errors);
        }

        if ($request->category == 'weight') {
            ListWeightTransaction::create(
                [
                    'name' => $request->name,
                    'userId' => $request->user()->id,
                    'userUpdateId' => $request->user()->id
                ]
            );
        } elseif ($request->category == 'temperature') {
            ListTemperatureTransaction::create(
                [
                    'name' => $request->name,
                    'userId' => $request->user()->id,
                    'userUpdateId' => $request->user()->id
                ]
            );
        } elseif ($request->category == 'breath') {
            ListBreathTransaction::create(
                [
                    'name' => $request->name,
                    'userId' => $request->user()->id,
                    'userUpdateId' => $request->user()->id
                ]
            );
        } elseif ($request->category == 'sound') {
            ListSoundTransaction::create(
                [
                    'name' => $request->name,
                    'userId' => $request->user()->id,
                    'userUpdateId' => $request->user()->id
                ]
            );
        } elseif ($request->category == 'heart') {
            ListHeartTransaction::create(
                [
                    'name' => $request->name,
                    'userId' => $request->user()->id,
                    'userUpdateId' => $request->user()->id
                ]
            );
        } elseif ($request->category == 'vaginal') {
            ListVaginalTransaction::create(
                [
                    'name' => $request->name,
                    'userId' => $request->user()->id,
                    'userUpdateId' => $request->user()->id
                ]
            );
        }

        return responseCreate();
    }

    public function printInvoce(Request $request)
    {
        $trans = transaction_pet_clinic_payment_total::find($request->paymentId);

        if (!$trans) {
            return responseInvalid(['Transaction not found!']);
        }

        $payment = transaction_pet_clinic_payments::where('transactionId', $trans->transactionId)->get();

        $trx = TransactionPetClinic::find($trans->transactionId);

        $locations = DB::table('location')
            ->leftJoin('location_telephone', 'location.codeLocation', '=', 'location_telephone.codeLocation')
            ->where(function ($query) {
                $query->where('location_telephone.usage', 'Utama')
                    ->orWhereNull('location_telephone.usage');
            })
            ->select(
                'location.locationName',
                'location.description',
                'location_telephone.phoneNumber',
                'location.codeLocation'
            )
            ->distinct()
            ->get();

        $locationGroups = [];
        foreach ($locations as $location) {
            $key = $location->codeLocation;
            if (!isset($locationGroups[$key])) {
                $locationGroups[$key] = [
                    'name'        => $location->locationName,
                    'description' => $location->description,
                    'phone'       => $location->phoneNumber ?? ''
                ];
            }
        }
        $formattedLocations = array_values($locationGroups);

        $customer = DB::table('customer as c')
            ->join('customerTelephones as ct', 'c.id', '=', 'ct.customerId')
            ->where('c.id', '=', $trx->customerId)
            ->select('c.firstName', 'ct.phoneNumber', 'c.memberNo')
            ->first();

        $details = $payment;
        $namaFile = $trans->nota_number . '.pdf';

        $detail_total = $trans;

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
            'total_tagihan'  => $detail_total['total_payment'],
        ];

        $pdf = Pdf::loadView('invoice.invoice_petclinic_outpatient', $data);
        return $pdf->download($namaFile);
    }

    public function printInvoceOutpatient(Request $request)
    {
        $trans = TransactionPetClinic::find($request->transactionPetClinicId);

        if (!$trans) {
            return responseInvalid(['Transaction not found!']);
        }

        $locations = DB::table('location')
            ->leftJoin('location_telephone', 'location.codeLocation', '=', 'location_telephone.codeLocation')
            ->where(function ($query) {
                $query->where('location_telephone.usage', 'Utama')
                    ->orWhereNull('location_telephone.usage');
            })
            ->select(
                'location.locationName',
                'location.description',
                'location_telephone.phoneNumber',
                'location.codeLocation'
            )
            ->distinct()
            ->get();

        $locationGroups = [];
        foreach ($locations as $location) {
            $key = $location->codeLocation;
            if (!isset($locationGroups[$key])) {
                $locationGroups[$key] = [
                    'name'        => $location->locationName,
                    'description' => $location->description,
                    'phone'       => $location->phoneNumber ?? ''
                ];
            }
        }
        $formattedLocations = array_values($locationGroups);

        // Ambil data customer dan telepon secara terpisah (pola yang sama dengan getBeforePayment)
        $cust  = $trans->customerId ? Customer::find($trans->customerId) : null;
        $phone = $trans->customerId
            ? CustomerTelephones::where('customerId', $trans->customerId)
                ->where('usage', 'Utama')
                ->where('isDeleted', 0)
                ->first()
            : null;

        // ── Baca nota & total dari DB (bukan dari request) ──────────────────────
        $paymentTotal = DB::table('transaction_pet_clinic_payment_totals')
            ->where('transactionId', $trans->id)
            ->orderByDesc('created_at')
            ->first();

        $notaNumber  = $paymentTotal?->nota_number ?? null;
        $namaFile    = str_replace('/', '_', $notaNumber ?? 'INV') . '.pdf';

        // Deposit (DP) yang sudah dibayar sebelumnya
        $totalDeposit = (float) DB::table('transactionPetClinicPrepayments')
            ->where('transactionId', $trans->id)
            ->where('isDeleted', 0)
            ->sum('amount');

        // Sisa tagihan yang harus dibayar sekarang
        $totalTagihan = (float)($paymentTotal?->amount ?? 0);

        // Total bruto = sisa tagihan + deposit yang sudah dibayar
        $totalBruto = $totalTagihan + $totalDeposit;

        // ── Baca item pembayaran dari DB ─────────────────────────────────────
        $payments = DB::table('transaction_pet_clinic_payments as tp')
            ->leftJoin('services as s', 's.id', '=', 'tp.serviceId')
            ->leftJoin('products as p', 'p.id', '=', 'tp.productId')
            ->leftJoin('products as pbuy', 'pbuy.id', '=', 'tp.productBuyId')
            ->leftJoin('promotionMasters as pm', 'pm.id', '=', 'tp.promoId')
            ->where('tp.transactionId', $trans->id)
            ->where(function ($q) {
                $q->where('tp.isDeleted', 0)->orWhereNull('tp.isDeleted');
            })
            ->select(
                'tp.id', 'tp.serviceId', 'tp.productId', 'tp.promoId',
                'tp.quantity', 'tp.quantityBuy', 'tp.quantityFree',
                'tp.price', 'tp.priceOverall', 'tp.isBundle',
                'tp.productBuyId', 'tp.productFreeId',
                'tp.discountType', 'tp.discountAmount', 'tp.discountPercent',
                's.fullName as serviceName',
                'p.fullName as productName',
                'pbuy.fullName as productBuyName',
                'pm.name as promoName'
            )
            ->orderBy('tp.id')
            ->get();

        // ── Baca bundle detail items ─────────────────────────────────────────
        $bundlePaymentIds = $payments->where('isBundle', 1)->pluck('id')->toArray();
        $bundleItemsMap   = collect();
        if (!empty($bundlePaymentIds)) {
            $bundleItemsMap = DB::table('transaction_pet_clinic_payment_bundles as tpb')
                ->leftJoin('services as s', 's.id', '=', 'tpb.serviceId')
                ->leftJoin('products as p', 'p.id', '=', 'tpb.productId')
                ->whereIn('tpb.paymentId', $bundlePaymentIds)
                ->where(function ($q) {
                    $q->where('tpb.isDeleted', 0)->orWhereNull('tpb.isDeleted');
                })
                ->select('tpb.paymentId', 'tpb.serviceId', 'tpb.productId',
                         'tpb.quantity', 'tpb.amount',
                         's.fullName as serviceName', 'p.fullName as productName')
                ->get()
                ->groupBy('paymentId');
        }

        // ── Bangun array $details untuk blade ───────────────────────────────
        $details = [];
        foreach ($payments as $pay) {
            $baseItemName = $pay->serviceId
                ? (($pay->serviceName ?: null) ?? 'Layanan')
                : (($pay->productName ?: null) ?? 'Produk');

            $detail = [
                'promoId'    => $pay->promoId,
                'quantity'   => (int)$pay->quantity,
                'bonus'      => 0,
                'discount'   => 0,
                'unit_price' => (float)$pay->price,
                'total'      => (float)$pay->priceOverall,
                'note'       => '',
                'item_name'  => $baseItemName,
            ];

            if ($pay->serviceId) $detail['serviceId'] = $pay->serviceId;
            if ($pay->productId) $detail['productId'] = $pay->productId;

            if ($pay->isBundle) {
                // ── Bundle ──────────────────────────────────────────────────
                $includedRaw = $bundleItemsMap->get($pay->id) ?? collect();
                $included = $includedRaw->map(fn($bi) => [
                    'name'      => $bi->serviceName ?: ($bi->productName ?: 'Item'),
                    'item_name' => $bi->serviceName ?: ($bi->productName ?: 'Item'),
                ])->values()->toArray();

                $detail['item_name']      = $pay->promoName ?? 'Bundle';
                $detail['unit_price']     = (float)$pay->priceOverall;
                $detail['promoCategory']  = 'bundle';
                $detail['included_items'] = $included;

            } elseif ($pay->productBuyId) {
                // ── Free Item ───────────────────────────────────────────────
                $detail['promoCategory'] = 'freeItem';
                $detail['bonus']         = (int)($pay->quantityFree ?? 0);
                $detail['note']          = "Beli {$pay->quantityBuy} {$baseItemName} Gratis {$pay->quantityFree}";

            } elseif ($pay->promoId && $pay->discountType) {
                // ── Discount ────────────────────────────────────────────────
                $detail['promoCategory'] = 'discount';
                $detail['discount']      = $pay->discountType === 'percent'
                    ? ($pay->discountPercent ?? 0)
                    : ($pay->discountAmount ?? 0);

            } else {
                // ── Tanpa Promo: pastikan promoId null ──────────────────────
                $detail['promoId'] = null;
            }

            $details[] = $detail;
        }

        $data = [
            'locations'      => $formattedLocations,
            'nota_date'      => Carbon::parse($trans->created_at)->format('d/m/Y'),
            'no_nota'        => $notaNumber ?? '___________',
            'member_no'      => $cust?->memberNo ?? '-',
            'customer_name'  => $cust ? (trim(implode(' ', array_filter([$cust->firstName, $cust->middleName ?? '', $cust->lastName ?? '']))) ?: '-') : '-',
            'phone_number'   => $phone?->phoneNumber ?? '-',
            'arrival_time'   => Carbon::parse($trans->created_at)->format('H:i'),
            'details'        => $details,
            'total_bruto'    => $totalBruto,
            'deposit'        => $totalDeposit,
            'total_tagihan'  => $totalTagihan,
        ];

        $pdf = Pdf::loadView('invoice.invoice_petclinic_outpatient', $data);
        return $pdf->download($namaFile);
    }

    public function uploadPaymentProof(Request $request)
    {
        $request->validate([
            'id'    => 'required|integer',
            'proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $trans_pay = transaction_pet_clinic_payment_total::find($request->id);

        return $this->handleUploadProof(
            $trans_pay,
            $request,
            'Transaction/Petclinic/proof_of_payment',
            'transaction_pet_clinic_payment_totals'
        );
    }

    public function confirmPayment(Request $request)
    {
        $request->validate(['id' => 'required|integer']);

        $trans_pay = transaction_pet_clinic_payment_total::find($request->id);

        $result = $this->handleConfirmProof($trans_pay, $request);
        if (!$result['ok']) {
            return $result['response'];
        }

        $trans_pay = $result['record'];
        $trans_pay->isPayed   = 1;
        $trans_pay->updated_at = now();
        $trans_pay->save();

        $total_amount = transaction_pet_clinic_payment_total::where('transactionId', $trans_pay->transactionId)->sum('amount');
        $amount_paid  = transaction_pet_clinic_payment_total::where('transactionId', $trans_pay->transactionId)
            ->where('isPayed', 1)->sum('amountPaid');

        if ($amount_paid < $total_amount)
            statusTransactionPetClinic($trans_pay->transactionId, 'Menunggu Pembayaran Berikutnya', $request->user()->id);
        else
            statusTransactionPetClinic($trans_pay->transactionId, 'Selesai', $request->user()->id);

        transactionPetClinicLog($trans_pay->transactionId, 'Pembayaran Dikonfirmasi', '', $request->user()->id);

        return responseCreate();
    }

    public function rejectPayment(Request $request)
    {
        $request->validate([
            'id'   => 'required|integer',
            'note' => 'required|string|max:500',
        ]);

        $trans_pay = transaction_pet_clinic_payment_total::find($request->id);

        $resp = $this->handleRejectProof($trans_pay, $request);

        if (isset($resp->original['status']) && $resp->original['status'] === 'success') {
            transactionPetClinicLog($trans_pay->transactionId, 'Bukti Pembayaran Ditolak', $request->note, $request->user()->id);
        }

        return $resp;
    }

    public function listDataWeight()
    {
        $data = ListWeightTransaction::select('id', 'name')
            ->where('isDeleted', '=', 0)
            ->get();

        return responseList($data);
    }

    public function listDataTemperature()
    {
        $data = ListTemperatureTransaction::select('id', 'name')
            ->where('isDeleted', '=', 0)
            ->get();

        return responseList($data);
    }

    public function listDatabreath()
    {
        $data = ListBreathTransaction::select('id', 'name')
            ->where('isDeleted', '=', 0)
            ->get();

        return responseList($data);
    }

    public function listDatasound()
    {
        $data = ListSoundTransaction::select('id', 'name')
            ->where('isDeleted', '=', 0)
            ->get();

        return responseList($data);
    }

    public function listDataheart()
    {
        $data = ListHeartTransaction::select('id', 'name')
            ->where('isDeleted', '=', 0)
            ->get();

        return responseList($data);
    }

    public function listDatavaginal()
    {
        $data = ListVaginalTransaction::select('id', 'name')
            ->where('isDeleted', '=', 0)
            ->get();

        return responseList($data);
    }

    // =========================================================================
    // RAWAT INAP — Treatment Plan
    // =========================================================================

    /**
     * Dokter submit treatment plan.
     * → Auto-generate Papan Kerja Harian
     * → Status: Menunggu Persetujuan Policy
     * Role: Dokter (jobTitleId=17) atau Administrator
     */
    public function storeTreatmentPlan(Request $request)
    {
        $user = $request->user();

        // Hanya dokter atau administrator
        if ($user->jobTitleId != 17 && $user->roleId != 1) {
            return response()->json(['message' => 'Hanya Dokter Hewan atau Administrator yang dapat menginput treatment plan.'], 403);
        }

        $validate = Validator::make($request->all(), [
            'transactionId' => 'required|integer',
            'treatmentPlans' => 'required|string', // JSON array of {id, name}
            'estimatedDays' => 'nullable|integer|min:1',
            'note' => 'nullable|string|max:1000',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $trans = TransactionPetClinic::where('id', $request->transactionId)
            ->where('isDeleted', 0)
            ->first();

        if (!$trans) {
            return responseInvalid(['Transaksi tidak ditemukan.']);
        }

        if ($trans->status !== 'Proses Rawat Inap') {
            return responseInvalid(['Status transaksi tidak valid. Harus berstatus "Proses Rawat Inap".']);
        }

        // Pastikan ini transaksi rawat inap (typeOfCare = 2)
        if ((int) $trans->typeOfCare !== 2) {
            return responseInvalid(['Treatment plan hanya berlaku untuk transaksi Rawat Inap.']);
        }

        // Cegah duplikat — jika sudah ada treatment plan, tolak
        $existingPlan = TransactionPetClinicTreatmentTreatPlan::where('transactionId', $trans->id)
            ->where('isDeleted', 0)
            ->exists();
        if ($existingPlan) {
            return responseInvalid(['Treatment plan sudah pernah disubmit untuk transaksi ini.']);
        }

        $treatmentPlans = json_decode($request->treatmentPlans, true) ?? [];
        $services       = json_decode($request->services ?? '[]', true) ?? [];
        $products       = json_decode($request->products ?? '[]', true) ?? [];

        if (count($treatmentPlans) === 0) {
            return responseInvalid(['Minimal satu rencana perawatan (Treatment Plan) harus dipilih.']);
        }

        DB::beginTransaction();
        try {
            // Simpan treatment plans
            foreach ($treatmentPlans as $tp) {
                TransactionPetClinicTreatmentTreatPlan::create([
                    'transactionId'   => $trans->id,
                    'treatmentPlanId' => $tp['id'],
                    'userId'          => $user->id,
                ]);
            }

            // Simpan services tambahan dalam plan
            foreach ($services as $svc) {
                TransactionPetClinicTreatmentService::create([
                    'transactionId' => $trans->id,
                    'serviceId'     => $svc['id'],
                    'quantity'      => $svc['quantity'] ?? 1,
                    'userId'        => $user->id,
                ]);
            }

            // Simpan produk tambahan dalam plan
            foreach ($products as $prd) {
                TransactionPetClinicTreatmentProduct::create([
                    'transactionId' => $trans->id,
                    'productId'     => $prd['id'],
                    'quantity'      => $prd['quantity'] ?? 1,
                    'userId'        => $user->id,
                ]);
            }

            // Update estimasi hari & catatan di transaksi utama (jika ada kolom-nya)
            // Simpan di note transaksi jika belum ada kolom dedicated
            if ($request->note) {
                TransactionPetClinic::where('id', $trans->id)
                    ->update(['note' => $request->note]);
            }

            // Auto-generate Papan Kerja Harian
            $this->generatePapanKerjaClinic($trans->id, $treatmentPlans, $request->estimatedDays ?? 1, $user->id);

            // Transisi status
            statusTransactionPetClinic($trans->id, 'Menunggu Persetujuan Policy', $user->id);
            transactionPetClinicLog($trans->id, 'Treatment plan diinput oleh Dr. ' . $user->firstName, '', $user->id);

            // Notif internal → Kasir/Admin di lokasi ybs
            $petName   = DB::table('customerPets')->where('id', $trans->petId)->value('petName') ?? 'Pasien';
            sendNotificationToStaffAtLocation(
                $trans->locationId,
                [1], // Kasir
                'petclinic',
                "Treatment plan {$petName} sudah diinput — proses TTD owner.",
                'info'
            );

            DB::commit();
            return responseCreate();
        } catch (\Throwable $th) {
            DB::rollBack();
            return responseInvalid([$th->getMessage()]);
        }
    }

    /**
     * Auto-generate Papan Kerja Harian dari treatment plans yang dipilih.
     * Membuat 1 row per treatmentsItems per hari selama estimatedDays.
     */
    private function generatePapanKerjaClinic(int $transactionId, array $treatmentPlans, int $estimatedDays, int $userId): void
    {
        $planIds = array_column($treatmentPlans, 'id');

        // Ambil semua items dari treatmentsItems untuk plan yang dipilih
        $items = DB::table('treatmentsItems as ti')
            ->join('treatments as t', 't.id', 'ti.treatments_id')
            ->whereIn('ti.treatments_id', $planIds)
            ->where('ti.isDeleted', 0)
            ->select('ti.product_name', 'ti.notes', 't.name as planName')
            ->get();

        // Slot waktu default aktivitas harian klinik
        $defaultTimes = ['07:00', '09:00', '12:00', '14:00', '17:00', '20:00'];

        $today = Carbon::today();

        for ($day = 0; $day < $estimatedDays; $day++) {
            $tanggal = $today->copy()->addDays($day)->toDateString();

            foreach ($items as $idx => $item) {
                $activityName = $item->product_name
                    ? "[{$item->planName}] {$item->product_name}"
                    : $item->planName;

                // Assign waktu default berdasarkan urutan aktivitas dalam 1 hari
                $time = $defaultTimes[$idx] ?? sprintf('%02d:00', 7 + ($idx * 2) % 14);

                TransactionPetClinicPapanKerjaHarian::create([
                    'transactionId' => $transactionId,
                    'activityName'  => $activityName,
                    'activityNote'  => $item->notes,
                    'tanggal'       => $tanggal,
                    'time'          => $time,
                    'status'        => 'pending',
                    'userId'        => $userId,
                ]);
            }

            // Jika tidak ada items, tetap buat 1 row generic per hari
            if ($items->isEmpty()) {
                TransactionPetClinicPapanKerjaHarian::create([
                    'transactionId' => $transactionId,
                    'activityName'  => 'Perawatan Harian',
                    'tanggal'       => $tanggal,
                    'time'          => '08:00',
                    'status'        => 'pending',
                    'userId'        => $userId,
                ]);
            }
        }
    }

    /**
     * GET treatment plan detail untuk sebuah transaksi.
     */
    public function getTreatmentPlan(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'transactionId' => 'required|integer',
        ]);
        if ($validate->fails()) return responseInvalid($validate->errors()->all());

        $treatmentPlans = DB::table('transactionPetClinicTreatmentTreatPlans as tp')
            ->join('treatments as t', 't.id', 'tp.treatmentPlanId')
            ->where('tp.transactionId', $request->transactionId)
            ->where('tp.isDeleted', 0)
            ->select('tp.id', 't.id as treatmentPlanId', 't.name')
            ->get();

        $services = DB::table('transactionPetClinicTreatmentServices as ts')
            ->join('services as s', 's.id', 'ts.serviceId')
            ->where('ts.transactionId', $request->transactionId)
            ->where('ts.isDeleted', 0)
            ->select('ts.id', 's.id as serviceId', 's.fullName as name', 'ts.quantity')
            ->get();

        $products = DB::table('transactionPetClinicTreatmentProducts as tp')
            ->join('products as p', 'p.id', 'tp.productId')
            ->where('tp.transactionId', $request->transactionId)
            ->where('tp.isDeleted', 0)
            ->select('tp.id', 'p.id as productId', 'p.fullName as name', 'tp.quantity')
            ->get();

        return response()->json([
            'treatmentPlans' => $treatmentPlans,
            'services'       => $services,
            'products'       => $products,
        ]);
    }

    // =========================================================================
    // RAWAT INAP — Policy Agreement
    // =========================================================================

    /**
     * GET daftar policy aktif untuk dipilih kasir.
     */
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

    /**
     * POST simpan tanda tangan owner → status: Dalam Perawatan.
     * Role: Kasir (jobTitleId=1), Admin, atau Manager.
     */
    public function savePolicyAgreement(Request $request)
    {
        $user = $request->user();

        $isKasir          = ($user->jobTitleId == 1);
        $isAdminOrManager = in_array($user->roleId, [1, 2]);

        if (!$isKasir && !$isAdminOrManager) {
            return response()->json(['message' => 'Hanya Kasir, Manager, atau Administrator yang dapat menyimpan persetujuan policy.'], 403);
        }

        $validate = Validator::make($request->all(), [
            'transactionId' => 'required|integer',
            'signerName'    => 'required|string',
            'signatureData' => 'required|string',
            'policies'      => 'required|string', // JSON array of {id, title, version}
        ]);
        if ($validate->fails()) return responseInvalid($validate->errors()->all());

        $trans = TransactionPetClinic::where('id', $request->transactionId)
            ->where('isDeleted', 0)
            ->first();

        if (!$trans) return responseInvalid(['Transaksi tidak ditemukan.']);

        if ($trans->status !== 'Menunggu Persetujuan Policy') {
            return responseInvalid(['Status transaksi tidak valid untuk persetujuan policy.']);
        }

        $policies = json_decode($request->policies, true) ?? [];
        if (empty($policies)) return responseInvalid(['Minimal satu policy harus dipilih.']);

        DB::beginTransaction();
        try {
            $signedAt = now();

            foreach ($policies as $policy) {
                TransactionPetClinicPolicyAgreement::create([
                    'transactionId'      => $trans->id,
                    'contractTemplateId' => $policy['id'],
                    'contractTitle'      => $policy['title'],
                    'contractVersion'    => $policy['version'],
                    'signatureData'      => $request->signatureData,
                    'signerName'         => $request->signerName,
                    'signedAt'           => $signedAt,
                    'userId'             => $user->id,
                ]);
            }

            statusTransactionPetClinic($trans->id, 'Dalam Perawatan', $user->id);
            transactionPetClinicLog(
                $trans->id,
                'Owner menyetujui ' . count($policies) . ' policy — pet resmi dalam perawatan.',
                'Ditandatangani oleh: ' . $request->signerName,
                $user->id
            );

            // Notif internal → Dokter, Paramedis, Vetnurse di lokasi
            $petName = DB::table('customerPets')->where('id', $trans->petId)->value('petName') ?? 'Pasien';
            sendNotificationToStaffAtLocation(
                $trans->locationId,
                [17, 4, 5], // Dokter Hewan, Paramedis, Vetnurse
                'petclinic',
                "{$petName} resmi dalam perawatan. Cek Papan Kerja Harian.",
                'success'
            );

            DB::commit();
            return responseCreate();
        } catch (\Throwable $th) {
            DB::rollBack();
            return responseInvalid([$th->getMessage()]);
        }
    }

    // =========================================================================
    // RAWAT INAP — Papan Kerja Harian
    // =========================================================================

    /**
     * GET daftar aktivitas papan kerja harian untuk sebuah transaksi.
     * Bisa filter by tanggal (default: semua).
     */
    public function getPapanKerjaHarian(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'transactionId' => 'required|integer',
        ]);
        if ($validate->fails()) return responseInvalid($validate->errors()->all());

        $trans = TransactionPetClinic::where('id', $request->transactionId)
            ->where('isDeleted', 0)->first();
        if (!$trans) return responseInvalid(['Transaksi tidak ditemukan.']);

        // Ambil info pet
        $pet = DB::table('customerPets as cp')
            ->leftJoin('petCategory as pc', 'pc.id', 'cp.petCategoryId')
            ->where('cp.id', $trans->petId)
            ->select('cp.petName', 'pc.petCategoryName as petBreed')
            ->first();

        $rows = TransactionPetClinicPapanKerjaHarian::where('transactionId', $request->transactionId)
            ->where('isDeleted', 0)
            ->orderBy('tanggal')
            ->orderBy('time')
            ->orderBy('id')
            ->get()
            ->map(function ($row) use ($pet) {
                $isDone      = $row->status === 'done';
                $doneByName  = $row->doneBy
                    ? optional(DB::table('users')->where('id', $row->doneBy)->select('firstName')->first())->firstName
                    : null;

                return [
                    'id'              => $row->id,
                    'petName'         => $pet->petName ?? '-',
                    'petBreed'        => $pet->petBreed ?? '-',
                    'scheduledDate'   => $row->tanggal
                        ? \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y')
                        : null,
                    'time'            => $row->time ?? '-',
                    'activity'        => $row->activityName,
                    'instructions'    => $row->activityNote ? [$row->activityNote] : [],
                    'isDone'          => $isDone,
                    'statusAktivitas' => $row->statusAktivitas,
                    'kondisiUmum'     => $row->kondisiUmum,
                    'nafsuMakan'      => $row->nafsuMakan,
                    'outputFeses'     => $row->outputFeses,
                    'outputUrin'      => $row->outputUrin,
                    'obatDiberikan'   => $row->obatDiberikan,
                    'catatanObat'     => $row->catatanObat,
                    'catatan'         => $row->catatan,
                    'fotoUrl'         => $row->foto ? url('storage/' . $row->foto) : null,
                    'completedAt'     => $row->doneAt ? $row->doneAt->format('d/m/Y H:i') : null,
                    'completedBy'     => $doneByName,
                ];
            });

        return response()->json($rows);
    }

    /**
     * PUT mark satu aktivitas papan kerja sebagai done.
     * → Kirim WA ke customer setiap kali satu aktivitas selesai.
     */
    public function markPapanKerjaHarianDone(Request $request)
    {
        $user = $request->user();

        // Hanya Dokter (17), Vetnurse (5), Paramedis (4), Admin/Manager
        $allowedJobTitles = [17, 5, 4];
        $isAdminOrManager = in_array($user->roleId, [1, 2]);
        if (!$isAdminOrManager && !in_array($user->jobTitleId, $allowedJobTitles)) {
            return response()->json(['message' => 'Hanya Dokter, Vetnurse, Paramedis, atau Administrator yang dapat menandai aktivitas selesai.'], 403);
        }

        $validate = Validator::make($request->all(), [
            'id'              => 'required|integer',
            'statusAktivitas' => 'required|in:terlaksana,dilewati',
            'kondisiUmum'     => 'nullable|in:baik,perlu_perhatian,kritis',
            'nafsuMakan'      => 'nullable|in:normal,sedikit,tidak_makan',
            'outputFeses'     => 'nullable|in:normal,diare,konstipasi,tidak_bab',
            'outputUrin'      => 'nullable|in:normal,tidak_bak',
            'obatDiberikan'   => 'nullable|boolean',
            'catatanObat'     => 'nullable|string|max:500',
            'catatan'         => 'nullable|string|max:1000',
            'foto'            => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
        ]);
        if ($validate->fails()) return responseInvalid($validate->errors()->all());

        $row = TransactionPetClinicPapanKerjaHarian::where('id', $request->id)
            ->where('isDeleted', 0)
            ->first();

        if (!$row) return responseInvalid(['Aktivitas tidak ditemukan.']);
        if ($row->status === 'done') return responseInvalid(['Aktivitas sudah ditandai selesai.']);

        // Upload foto jika ada
        $fotoPath = $row->foto;
        if ($request->hasFile('foto')) {
            $file     = $request->file('foto');
            $filename = 'pkh_' . $row->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $fotoPath = $file->storeAs('Transaction/PetClinic/papan_kerja', $filename, 'public');
        }

        $row->update([
            'status'          => 'done',
            'doneBy'          => $user->id,
            'doneAt'          => now(),
            'userUpdateId'    => $user->id,
            'statusAktivitas' => $request->statusAktivitas,
            'kondisiUmum'     => $request->kondisiUmum,
            'nafsuMakan'      => $request->nafsuMakan,
            'outputFeses'     => $request->outputFeses,
            'outputUrin'      => $request->outputUrin,
            'obatDiberikan'   => $request->obatDiberikan,
            'catatanObat'     => $request->catatanObat,
            'catatan'         => $request->catatan,
            'foto'            => $fotoPath,
        ]);

        // Kirim WA ke customer — ambil semua data dalam 1 query
        $trans = TransactionPetClinic::find($row->transactionId);
        if ($trans) {
            $customerData = DB::table('customer as c')
                ->join('customerPets as cp', 'cp.customerId', 'c.id')
                ->leftJoin('customerTelephones as ct', function ($join) {
                    $join->on('ct.customerId', 'c.id')
                         ->where('ct.usage', 'Utama')
                         ->where('ct.isDeleted', 0);
                })
                ->where('cp.id', $trans->petId)
                ->select('c.firstName as ownerName', 'cp.petName as petName', 'ct.phoneNumber')
                ->first();

            $phone     = $customerData->phoneNumber ?? '';
            $petName   = $customerData->petName ?? 'Hewan Anda';
            $ownerName = $customerData->ownerName ?? 'Owner';
            $tanggal   = Carbon::parse($row->tanggal)->translatedFormat('d F Y');
            $staffName = $user->firstName;

            $msg = "Halo {$ownerName}, update kondisi {$petName} — {$tanggal}:\n\n"
                . "✅ {$row->activityName} telah selesai dilakukan.\n\n"
                . "Dipantau oleh: {$staffName}\n"
                . "Info lebih lanjut hubungi klinik kami.";

            sendWhatsApp($phone ?? '', $msg);
        }

        return responseUpdate();
    }

    // =========================================================================
    // RAWAT INAP — Additional Treatments
    // =========================================================================

    /**
     * GET daftar tindakan/obat tambahan selama rawat inap.
     */
    public function getAdditionalTreatments(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'transactionId' => 'required|integer',
        ]);
        if ($validate->fails()) return responseInvalid($validate->errors()->all());

        $data = TransactionPetClinicAdditionalTreatment::where('transactionId', $request->transactionId)
            ->where('isDeleted', 0)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($data);
    }

    /**
     * POST tambah tindakan/obat selama rawat inap.
     * Role: Dokter atau Admin/Manager.
     */
    public function addAdditionalTreatment(Request $request)
    {
        $user = $request->user();

        $validate = Validator::make($request->all(), [
            'transactionId' => 'required|integer',
            'type'          => 'required|in:service,product',
            'itemId'        => 'required|integer',
            'quantity'      => 'required|integer|min:1',
            'catatan'       => 'nullable|string|max:500',
        ]);
        if ($validate->fails()) return responseInvalid($validate->errors()->all());

        $trans = TransactionPetClinic::where('id', $request->transactionId)
            ->where('isDeleted', 0)
            ->first();

        if (!$trans) return responseInvalid(['Transaksi tidak ditemukan.']);

        if (!in_array($trans->status, ['Dalam Perawatan'])) {
            return responseInvalid(['Tindakan tambahan hanya bisa ditambah saat status "Dalam Perawatan".']);
        }

        // Ambil nama dan harga item
        if ($request->type === 'service') {
            $item = DB::table('services')->where('id', $request->itemId)->select('fullName as name')->first();
            $itemPrice = 0; // harga service diambil dari pricing table jika diperlukan
        } else {
            $item = DB::table('products')->where('id', $request->itemId)->select('fullName as name')->first();
            $itemPrice = 0;
        }

        if (!$item) return responseInvalid(['Item tidak ditemukan.']);

        TransactionPetClinicAdditionalTreatment::create([
            'transactionId' => $trans->id,
            'type'          => $request->type,
            'itemId'        => $request->itemId,
            'itemName'      => $item->name,
            'itemPrice'     => $itemPrice,
            'quantity'      => $request->quantity,
            'catatan'       => $request->catatan,
            'userId'        => $user->id,
        ]);

        // Notif internal → Kasir/Admin
        $petName = DB::table('customerPets')->where('id', $trans->petId)->value('petName') ?? 'Pasien';
        sendNotificationToStaffAtLocation(
            $trans->locationId,
            [1], // Kasir
            'petclinic',
            "Additional treatment ditambahkan untuk {$petName}: {$item->name} ×{$request->quantity}.",
            'info'
        );

        return responseCreate();
    }

    /**
     * GET dropdown items (service atau product) untuk additional treatment.
     */
    public function getAvailableItems(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'transactionId' => 'required|integer',
            'type'          => 'required|in:service,product',
        ]);
        if ($validate->fails()) return responseInvalid($validate->errors()->all());

        $trans = TransactionPetClinic::where('id', $request->transactionId)
            ->where('isDeleted', 0)
            ->first();

        if (!$trans) return responseInvalid(['Transaksi tidak ditemukan.']);

        $search = $request->search ?? '';

        if ($request->type === 'service') {
            $data = DB::table('services as s')
                ->join('servicesLocation as sl', 'sl.service_id', 's.id')
                ->where('sl.location_id', $trans->locationId)
                ->where('s.isDeleted', 0)
                ->when($search, fn($q) => $q->where('s.fullName', 'like', "%{$search}%"))
                ->select('s.id', 's.fullName as name')
                ->distinct()
                ->limit(30)
                ->get();
        } else {
            $data = DB::table('products as p')
                ->join('productLocations as pl', 'pl.productId', 'p.id')
                ->where('pl.locationId', $trans->locationId)
                ->where('p.isDeleted', 0)
                ->when($search, fn($q) => $q->where('p.fullName', 'like', "%{$search}%"))
                ->select('p.id', 'p.fullName as name')
                ->limit(30)
                ->get();
        }

        return response()->json($data);
    }

    // =========================================================================
    // RAWAT INAP — Prepayments (DP)
    // =========================================================================

    /**
     * GET daftar DP untuk sebuah transaksi rawat inap.
     */
    public function getPrepayments(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'transactionId' => 'required|integer',
        ]);
        if ($validate->fails()) return responseInvalid($validate->errors()->all());

        $list = DB::table('transactionPetClinicPrepayments as pp')
            ->join('paymentmethod as pm', 'pm.id', 'pp.paymentMethodId')
            ->where('pp.transactionId', $request->transactionId)
            ->where('pp.isDeleted', 0)
            ->select(
                'pp.id',
                'pm.name as paymentMethod',
                'pp.amount',
                'pp.catatan',
                'pp.proofOfPayment',
                DB::raw("DATE_FORMAT(pp.created_at, '%d-%m-%Y %H:%i') as createdAt")
            )
            ->orderBy('pp.created_at', 'desc')
            ->get();

        $totalPrepaid = $list->sum('amount');

        return response()->json([
            'list'         => $list,
            'totalPrepaid' => $totalPrepaid,
        ]);
    }

    /**
     * GET estimasi total biaya rawat inap.
     * Menghitung dari: services (plan) + products (plan) + additional treatments.
     * Treatment plans (nama paket) tidak memiliki harga satuan, sehingga tidak dihitung.
     */
    public function getEstimatedCost(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'transactionId' => 'required|integer',
        ]);
        if ($validate->fails()) return responseInvalid($validate->errors()->all());

        $trans = TransactionPetClinic::where('id', $request->transactionId)
            ->where('isDeleted', 0)
            ->first();

        if (!$trans) return responseInvalid(['Transaksi tidak ditemukan.']);

        $locId       = (int) $trans->locationId;
        $cust        = Customer::find($trans->customerId);
        $custGroupId = $cust ? (int)($cust->customerGroupId ?? 0) : 0;

        // Subquery harga service (sama dengan showDataBeforePayment)
        $spSub = DB::raw("(
            SELECT service_id,
                   COALESCE(
                       (SELECT TRIM(REPLACE(REPLACE(sp_g.price, '.', ''), ',', '')) + 0
                        FROM servicesPrice sp_g
                        WHERE sp_g.service_id  = sp_base.service_id
                          AND sp_g.location_id = {$locId}
                          AND sp_g.customer_group_id = {$custGroupId}
                        LIMIT 1),
                       (SELECT TRIM(REPLACE(REPLACE(sp_f.price, '.', ''), ',', '')) + 0
                        FROM servicesPrice sp_f
                        WHERE sp_f.service_id  = sp_base.service_id
                          AND sp_f.location_id = {$locId}
                        LIMIT 1),
                       0
                   ) AS price
            FROM servicesPrice sp_base
            WHERE sp_base.location_id = {$locId}
            GROUP BY sp_base.service_id
        ) as sp");

        // Services dari treatment plan
        $services = DB::table('transactionPetClinicTreatmentServices as ts')
            ->join('services as s', 's.id', 'ts.serviceId')
            ->leftJoin($spSub, 's.id', '=', 'sp.service_id')
            ->where('ts.transactionId', $request->transactionId)
            ->where('ts.isDeleted', 0)
            ->select(
                's.fullName as name',
                DB::raw('ts.quantity + 0 as quantity'),
                DB::raw('COALESCE(sp.price, 0) as unitPrice'),
                DB::raw('COALESCE(sp.price, 0) * (ts.quantity + 0) as subtotal')
            )
            ->get();

        // Products dari treatment plan
        $products = DB::table('transactionPetClinicTreatmentProducts as tp')
            ->join('products as p', 'p.id', 'tp.productId')
            ->where('tp.transactionId', $request->transactionId)
            ->where('tp.isDeleted', 0)
            ->select(
                'p.fullName as name',
                DB::raw('tp.quantity + 0 as quantity'),
                DB::raw('p.price + 0 as unitPrice'),
                DB::raw('(p.price + 0) * (tp.quantity + 0) as subtotal')
            )
            ->get();

        // Additional treatments (service & product) — JOIN ke tabel masing-masing untuk harga terbaru
        $additionalServices = DB::table('transactionPetClinicAdditionalTreatments as at')
            ->join('services as s', 's.id', 'at.itemId')
            ->leftJoin($spSub, 's.id', '=', 'sp.service_id')
            ->where('at.transactionId', $request->transactionId)
            ->where('at.isDeleted', 0)
            ->where('at.type', 'service')
            ->select(
                DB::raw("CONCAT(at.itemName, ' (tambahan)') as name"),
                DB::raw('at.quantity + 0 as quantity'),
                DB::raw('COALESCE(sp.price, 0) as unitPrice'),
                DB::raw('COALESCE(sp.price, 0) * (at.quantity + 0) as subtotal')
            )
            ->get();

        $additionalProducts = DB::table('transactionPetClinicAdditionalTreatments as at')
            ->join('products as p', 'p.id', 'at.itemId')
            ->where('at.transactionId', $request->transactionId)
            ->where('at.isDeleted', 0)
            ->where('at.type', 'product')
            ->select(
                DB::raw("CONCAT(at.itemName, ' (tambahan)') as name"),
                DB::raw('at.quantity + 0 as quantity'),
                DB::raw('p.price + 0 as unitPrice'),
                DB::raw('(p.price + 0) * (at.quantity + 0) as subtotal')
            )
            ->get();

        $allItems = $services->merge($products)->merge($additionalServices)->merge($additionalProducts);
        $estimatedTotal = $allItems->sum('subtotal');

        return response()->json([
            'items'          => $allItems->values(),
            'estimatedTotal' => $estimatedTotal,
            'note'           => 'Estimasi belum termasuk harga paket treatment plan. Total akhir ditetapkan saat checkout.',
        ]);
    }

    /**
     * POST tambah DP / cicilan awal rawat inap.
     * Tersedia saat status: Menunggu Persetujuan Policy | Dalam Perawatan.
     * Role: Kasir atau Admin/Manager.
     */
    public function addPrepayment(Request $request)
    {
        $user = $request->user();

        $isKasir          = ($user->jobTitleId == 1);
        $isAdminOrManager = in_array($user->roleId, [1, 2]);

        if (!$isKasir && !$isAdminOrManager) {
            return response()->json(['message' => 'Hanya Kasir, Manager, atau Administrator yang dapat menginput DP.'], 403);
        }

        $validate = Validator::make($request->all(), [
            'transactionId'   => 'required|integer',
            'paymentMethodId' => 'required|integer',
            'amount'          => 'required|numeric|min:1',
            'catatan'         => 'nullable|string|max:500',
        ]);
        if ($validate->fails()) return responseInvalid($validate->errors()->all());

        $trans = TransactionPetClinic::where('id', $request->transactionId)
            ->where('isDeleted', 0)
            ->first();

        if (!$trans) return responseInvalid(['Transaksi tidak ditemukan.']);

        $allowedStatuses = ['Menunggu Persetujuan Policy', 'Dalam Perawatan'];
        if (!in_array($trans->status, $allowedStatuses)) {
            return responseInvalid(['DP hanya bisa diinput saat status "Menunggu Persetujuan Policy" atau "Dalam Perawatan".']);
        }

        // Handle upload bukti bayar
        $proofOfPayment  = null;
        $originalName    = null;
        $proofRandomName = null;

        if ($request->hasFile('proof')) {
            $file            = $request->file('proof');
            $originalName    = $file->getClientOriginalName();
            $proofRandomName = uniqid('dp_', true) . '.' . $file->getClientOriginalExtension();
            $proofOfPayment  = $file->storeAs('prepayments/clinic', $proofRandomName, 'public');
        }

        TransactionPetClinicPrepayment::create([
            'transactionId'   => $trans->id,
            'paymentMethodId' => $request->paymentMethodId,
            'amount'          => $request->amount,
            'catatan'         => $request->catatan,
            'proofOfPayment'  => $proofOfPayment,
            'originalName'    => $originalName,
            'proofRandomName' => $proofRandomName,
            'userId'          => $user->id,
        ]);

        transactionPetClinicLog(
            $trans->id,
            'DP sebesar Rp ' . number_format($request->amount, 0, ',', '.') . ' diterima.',
            '',
            $user->id
        );

        return responseCreate();
    }

    /**
     * GET cetak struk DP (PDF).
     */
    public function prepaymentReceipt(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);
        if ($validate->fails()) return responseInvalid($validate->errors()->all());

        $dp = DB::table('transactionPetClinicPrepayments as pp')
            ->join('paymentmethod as pm', 'pm.id', 'pp.paymentMethodId')
            ->join('transactionPetClinics as t', 't.id', 'pp.transactionId')
            ->leftJoin('customer as c', 'c.id', 't.customerId')
            ->leftJoin('customerPets as cp', 'cp.id', 't.petId')
            ->leftJoin('customerTelephones as ct', function ($join) {
                $join->on('ct.customerId', 'c.id')
                     ->where('ct.usage', 'Utama')
                     ->where('ct.isDeleted', 0);
            })
            ->leftJoin('users as u', 'u.id', 'pp.userId')
            ->where('pp.id', $request->id)
            ->where('pp.isDeleted', 0)
            ->select(
                'pp.id',
                'pp.amount',
                'pp.catatan',
                'pm.name as paymentMethod',
                't.registrationNo',
                't.startDate',
                't.endDate',
                DB::raw("COALESCE(c.firstName, t.registrant, '-') as customerName"),
                DB::raw("COALESCE(cp.petName, '-') as petName"),
                DB::raw("COALESCE(ct.phoneNumber, '-') as phoneNumber"),
                DB::raw("COALESCE(u.firstName, '-') as recordedBy"),
                'pp.created_at'
            )
            ->first();

        if (!$dp) return responseInvalid(['Data DP tidak ditemukan.']);

        $notaNumber = 'DP-' . $dp->id;

        $data = [
            'locations'       => $this->getActiveLocationsForInvoice(),
            'nota_number'     => $notaNumber,
            'nota_date'       => Carbon::parse($dp->created_at)->format('d/m/Y'),
            'registration_no' => $dp->registrationNo ?? '-',
            'customer_name'   => $dp->customerName ?? '-',
            'phone_number'    => $dp->phoneNumber ?? '-',
            'pet_name'        => $dp->petName ?? '-',
            'start_date'      => $dp->startDate ? Carbon::parse($dp->startDate)->format('d/m/Y') : '-',
            'end_date'        => $dp->endDate ? Carbon::parse($dp->endDate)->format('d/m/Y') : '-',
            'amount'          => $dp->amount,
            'payment_method'  => $dp->paymentMethod ?? '-',
            'catatan'         => $dp->catatan,
            'recorded_by'     => $dp->recordedBy ?? '-',
            'recorded_at'     => Carbon::parse($dp->created_at)->format('d/m/Y H:i'),
        ];

        $pdf = Pdf::loadView('invoice.prepayment_dp_receipt_clinic', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('defaultFont', 'Arial')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('compress', 1);
        return $pdf->stream("struk-dp-{$dp->registrationNo}.pdf");
    }

    /**
     * Daftar lokasi aktif untuk header invoice/struk (logo + cabang).
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

    // =========================================================================
    // RAWAT INAP — Inpatient Detail & Checkout
    // =========================================================================

    /**
     * GET detail rawat inap: treatment plan, additional treatments, total DP.
     */
    public function getInpatient(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'transactionId' => 'required|integer',
        ]);
        if ($validate->fails()) return responseInvalid($validate->errors()->all());

        $trans = TransactionPetClinic::where('id', $request->transactionId)
            ->where('isDeleted', 0)
            ->first();

        if (!$trans) return responseInvalid(['Transaksi tidak ditemukan.']);

        // Treatment plans terpilih
        $treatmentPlans = DB::table('transactionPetClinicTreatmentTreatPlans as tp')
            ->join('treatments as t', 't.id', 'tp.treatmentPlanId')
            ->where('tp.transactionId', $trans->id)
            ->where('tp.isDeleted', 0)
            ->select('tp.id', 't.name')
            ->get();

        // Services dalam plan
        $planServices = DB::table('transactionPetClinicTreatmentServices as ts')
            ->join('services as s', 's.id', 'ts.serviceId')
            ->where('ts.transactionId', $trans->id)
            ->where('ts.isDeleted', 0)
            ->select('ts.id', 's.fullName as name', 'ts.quantity')
            ->get();

        // Produk dalam plan
        $planProducts = DB::table('transactionPetClinicTreatmentProducts as tp')
            ->join('products as p', 'p.id', 'tp.productId')
            ->where('tp.transactionId', $trans->id)
            ->where('tp.isDeleted', 0)
            ->select('tp.id', 'p.fullName as name', 'tp.quantity')
            ->get();

        // Additional treatments
        $additionalTreatments = TransactionPetClinicAdditionalTreatment::where('transactionId', $trans->id)
            ->where('isDeleted', 0)
            ->get();

        // Total DP
        $totalPrepaid = TransactionPetClinicPrepayment::where('transactionId', $trans->id)
            ->where('isDeleted', 0)
            ->sum('amount');

        // Policy agreements
        $policyAgreements = DB::table('transactionPetClinicPolicyAgreements as pa')
            ->leftJoin('contract_templates as ct', 'ct.id', 'pa.contractTemplateId')
            ->where('pa.transactionId', $trans->id)
            ->select(
                'pa.contractTitle',
                'pa.contractVersion',
                'pa.signerName',
                DB::raw("DATE_FORMAT(pa.signedAt, '%d-%m-%Y %H:%i') as signedAt")
            )
            ->get();

        return response()->json([
            'treatmentPlans'       => $treatmentPlans,
            'planServices'         => $planServices,
            'planProducts'         => $planProducts,
            'additionalTreatments' => $additionalTreatments,
            'totalPrepaid'         => $totalPrepaid,
            'policyAgreements'     => $policyAgreements,
        ]);
    }

    /**
     * POST initiate checkout: Dalam Perawatan → Proses Pembayaran.
     * Role: Dokter atau Admin/Manager.
     */
    public function initiateCheckout(Request $request)
    {
        $user = $request->user();

        $validate = Validator::make($request->all(), [
            'transactionId' => 'required|integer',
        ]);
        if ($validate->fails()) return responseInvalid($validate->errors()->all());

        $trans = TransactionPetClinic::where('id', $request->transactionId)
            ->where('isDeleted', 0)
            ->first();

        if (!$trans) return responseInvalid(['Transaksi tidak ditemukan.']);

        if ($trans->status !== 'Dalam Perawatan') {
            return responseInvalid(['Hanya transaksi berstatus "Dalam Perawatan" yang bisa di-checkout.']);
        }

        // Set endDate = hari ini
        TransactionPetClinic::where('id', $trans->id)
            ->update(['endDate' => Carbon::today()->toDateString()]);

        statusTransactionPetClinic($trans->id, 'Proses Pembayaran', $user->id);
        transactionPetClinicLog($trans->id, 'Pasien dinyatakan siap checkout oleh ' . $user->firstName, '', $user->id);

        // Notif internal → Kasir/Admin
        $petName = DB::table('customerPets')->where('id', $trans->petId)->value('petName') ?? 'Pasien';
        sendNotificationToStaffAtLocation(
            $trans->locationId,
            [1], // Kasir
            'petclinic',
            "{$petName} siap checkout — proses pembayaran final.",
            'warning'
        );

        return responseUpdate();
    }

    /**
     * POST pembayaran final rawat inap.
     * Kalkulasi: (total dari showDataBeforePayment) − total prepayments.
     * Role: Kasir atau Admin/Manager.
     */
    public function paymentInpatient(Request $request)
    {
        $user = $request->user();

        $isKasir          = ($user->jobTitleId == 1);
        $isAdminOrManager = in_array($user->roleId, [1, 2]);

        if (!$isKasir && !$isAdminOrManager) {
            return response()->json(['message' => 'Hanya Kasir, Manager, atau Administrator yang dapat memproses pembayaran.'], 403);
        }

        // Parse array sebelum validasi
        $request->merge(['purchases' => $this->ensureIsArray($request->purchases)]);

        $validate = Validator::make($request->all(), [
            'transactionPetClinicId' => 'required|integer',
            'payment_method'         => 'required',
            'detail_total'           => 'required',
            'purchases'              => 'required|array',
        ]);
        if ($validate->fails()) return responseInvalid($validate->errors()->all());

        $payment   = json_decode($request->payment_method, true);
        $detail    = json_decode($request->detail_total, true);
        $purchases = $request->purchases;
        $transId   = $request->transactionPetClinicId;

        $trans = TransactionPetClinic::find($transId);
        if (!$trans) return responseInvalid(['Transaction not found!']);

        // Hitung total DP yang sudah dibayar
        $totalPrepaid = TransactionPetClinicPrepayment::where('transactionId', $transId)
            ->where('isDeleted', 0)
            ->sum('amount');

        // Pre-fetch promo
        $promoIds = collect($purchases)->pluck('promoId')->filter()->unique()->toArray();
        if (isset($detail['promoBasedSaleId'])) {
            $promoIds[] = $detail['promoBasedSaleId'];
        }
        $promos = PromotionMaster::whereIn('id', $promoIds)->get()->keyBy('id');

        try {
            DB::beginTransaction();

            foreach ($purchases as $value) {
                $promoId = $value['promoId'] ?? null;
                $promo   = ($promoId && $promoId !== 'null') ? $promos->get($promoId) : null;

                if ($promoId && $promoId !== 'null' && !$promo) {
                    throw new \Exception("Promotion ID {$promoId} not found!");
                }

                // Inisialisasi Model Payment Detail — ikuti struktur paymentOutpatient
                $trx = new transaction_pet_clinic_payments();
                $trx->transactionId   = $transId;
                $trx->paymentMethodId = $payment['paymentMethodId'];
                $trx->userId          = $user->id;
                $trx->promoId         = $promo ? $promo->id : null;
                $trx->price           = $value['unit_price'] ?? 0;
                $trx->priceOverall    = $value['total'] ?? 0;
                $trx->quantity        = $value['quantity'] ?? 1;

                // Logika berdasarkan tipe item (Free Item / Bundle / Service / Product)
                if (isset($value['buy_product_id'])) {
                    $trx->productBuyId  = $value['buy_product_id'];
                    $trx->productFreeId = $value['free_product_id'];
                    $trx->quantity      = $value['quantity'] + ($value['bonus'] ?? 0);
                    $trx->quantityBuy   = $value['quantity'];
                    $trx->quantityFree  = $value['bonus'] ?? 0;
                } elseif (isset($value['promoCategory']) && $value['promoCategory'] === 'bundle') {
                    $trx->isBundle = true;
                    $trx->save();
                    foreach ($value['included_items'] as $item) {
                        $bundle = new transaction_pet_clinic_payment_bundle();
                        $bundle->paymentId   = $trx->id;
                        $bundle->promoId     = $promo->id;
                        $bundle->serviceId   = $item['serviceId'] ?? null;
                        $bundle->productId   = $item['productId'] ?? null;
                        $bundle->quantity    = $item['quantity'];
                        $bundle->amount      = $item['normal_price'] ?? 0;
                        $bundle->userId      = $user->id;
                        $bundle->save();
                    }
                    continue;
                } elseif (isset($value['serviceId'])) {
                    $trx->serviceId = $value['serviceId'];
                } elseif (isset($value['productId'])) {
                    $trx->productId = $value['productId'];
                }

                // Diskon
                if ($promo && ($value['discount'] ?? 0) > 0) {
                    $trx->discountType = $value['discountType'] ?? 'amount';
                    if ($trx->discountType === 'percent') {
                        $trx->discountPercent = $value['discount'];
                        $trx->discountAmount  = 0;
                    } else {
                        $trx->discountAmount  = $value['discount'];
                        $trx->discountPercent = 0;
                    }
                }

                $trx->save();
            }

            // Based Sales — hanya simpan jika promoBasedSaleId valid (bukan null / string kosong)
            $promoBasedSaleId = $detail['promoBasedSaleId'] ?? null;
            if ($promoBasedSaleId !== null && $promoBasedSaleId !== '' && $promoBasedSaleId !== 'null') {
                $sales = new transaction_pet_clinic_payment_based_sales();
                $sales->transactionId   = $transId;
                $sales->paymentMethodId = $payment['paymentMethodId'];
                $sales->promoId         = (int) $promoBasedSaleId;
                $sales->amountDiscount  = $detail['discount_based_sales'] ?? 0;
                $sales->userId          = $user->id;
                $sales->save();
            }

            // Total yang harus dibayar: frontend sudah kirim total_payment yg sudah dikurangi DP
            // (lihat createPaymentPetClinicInpatient di service.jsx frontend)
            $totalPayment = max(0, $detail['total_payment'] ?? 0);
            $amountPaid   = $payment['amountPaid'] ?? $totalPayment;

            // Generate nomor nota (sama persis dengan paymentOutpatient)
            $now     = Carbon::now();
            $tahun   = $now->format('Y');
            $bulan   = $now->format('m');

            $jumlahTransaksi = DB::table('transaction_pet_clinic_payment_totals as tp')
                ->join('transactionPetClinics as tpc', 'tp.transactionId', '=', 'tpc.id')
                ->where('tpc.locationId', $trans->locationId)
                ->whereYear('tp.created_at', $tahun)
                ->whereMonth('tp.created_at', $bulan)
                ->lockForUpdate()
                ->count();

            $nomorUrut = str_pad($jumlahTransaksi + 1, 4, '0', STR_PAD_LEFT);
            $notaNomor = "INV/PC/{$trans->locationId}/{$tahun}/{$bulan}/{$nomorUrut}";

            // Simpan ke payment_totals — ikuti struktur kolom yang ada
            $paymentTotal = new transaction_pet_clinic_payment_total();
            $paymentTotal->transactionId    = $transId;
            $paymentTotal->paymentmethodId  = $payment['paymentMethodId'];
            $paymentTotal->amount           = $totalPayment;
            $paymentTotal->amountPaid       = $amountPaid;
            $paymentTotal->nextPayment      = $payment['next_payment'] ?? null;
            $paymentTotal->duration         = $payment['duration'] ?? null;
            $paymentTotal->tenor            = $payment['tenor'] ?? null;
            $paymentTotal->nota_number      = $notaNomor;
            $paymentTotal->userId           = $user->id;
            $paymentTotal->save();

            // Tentukan status akhir berdasarkan jumlah yang dibayar
            $totalAmountPaid = transaction_pet_clinic_payment_total::where('transactionId', $transId)->sum('amountPaid');
            $totalAmount     = transaction_pet_clinic_payment_total::where('transactionId', $transId)->value('amount') ?? $totalPayment;

            if ($totalAmountPaid < $totalAmount) {
                statusTransactionPetClinic($transId, 'Menunggu Pembayaran Berikutnya', $user->id);
            } else {
                statusTransactionPetClinic($transId, 'Selesai', $user->id);
            }

            transactionPetClinicLog(
                $transId,
                'Pembayaran rawat inap dikonfirmasi — DP sudah dikurangi Rp ' . number_format($totalPrepaid, 0, ',', '.'),
                $notaNomor,
                $user->id
            );

            // Notif internal
            sendNotificationToStaffAtLocation(
                $trans->locationId,
                [1],
                'petclinic',
                'Pembayaran rawat inap telah diproses.',
                'info'
            );

            DB::commit();
            return response()->json(['id' => $paymentTotal->id, 'message' => 'Add Data Successful!'], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return responseInvalid([$th->getMessage()]);
        }
    }
}
