<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;
use App\Models\Customer\Customer;
use App\Models\Customer\CustomerPets;
use App\Models\Staff\UsersLocation;
use App\Models\transaction_pet_salon_payment;
use App\Models\transaction_pet_salon_payment_based_sales;
use App\Models\transaction_pet_salon_payment_bundle;
use App\Models\transaction_pet_salon_payment_total;
use App\Models\transactionpetsalon;
use App\Models\transactionpetsaloncheck;
use App\Models\transactionPetSalonTreatmentCage;
use App\Models\TransactionPetSalonPolicyAgreement;
use App\Models\TransactionPetSalonTreatmentProduct;
use App\Models\TransactionPetSalonTreatmentService;
use App\Models\TransactionPetSalonTreatmentTreatPlan;
use App\Models\User;

class PetSalonController extends Controller
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

        $data = DB::table('transaction_pet_salons as t')
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
                DB::raw("IFNULL(cg.customerGroup,'') as customerGroup"),
                DB::raw("IFNULL(t.startDate,'') as startDate"),
                DB::raw("IFNULL(t.endDate,'') as endDate"),
                't.status',
                'u.firstName as picDoctor',
                'uc.firstName as createdBy',
                DB::raw("DATE_FORMAT(t.created_at, '%d-%m-%Y %H:%m:%s') as createdAt"),
                DB::raw('CASE WHEN ' . $statusDoc . '=1 and u.id=' . $request->user()->id . ' and t.status="Cek Kondisi Pet" THEN 1 ELSE 0 END as isPetCheck')
            )
            ->where('t.isDeleted', '=', 0);

        if ($request->status == 'ongoing') {
            $data = $data->whereNotIn('t.status', ['Selesai', 'Batal']);
        } elseif ($request->status == 'finished') {
            $data = $data->whereIn('t.status', ['Selesai', 'Batal']);
        }

        // Kasir (jobTitleId=1) mendapat visibilitas penuh seperti Admin/Manager
        $isKasir = ($request->user()->jobTitleId == 1);

        if (
            $request->user()->roleId != 1
            && $request->user()->roleId != 2
            && !$isKasir
        ) {
            $locations = UsersLocation::select('locationId')
                ->where('usersId', $request->user()->id)
                ->pluck('locationId')
                ->toArray();

            if (!empty($locations)) {
                $data = $data->whereIn('l.id', $locations);
            }
        } else {
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

        $loc = transactionpetsalon::where('locationId', $request->locationId)->count();

        $date = Carbon::now()->format('d');
        $month = Carbon::now()->format('m');
        $year = Carbon::now()->format('Y');

        $petCheckRegistrationNo = str_pad($loc + 1, 3, 0, STR_PAD_LEFT) . '/LPIK-RIS-RPC-PS/' . $request->locationId . '/' . $date . '/' . $month . '/' . $year;

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

            $trx = transactionpetsalon::where('locationId', $request->locationId)->count();

            $regisNo = 'RPC.TRX.' . $request->locationId . '.' . str_pad($trx + 1, 8, 0, STR_PAD_LEFT);

            $tran = transactionpetsalon::create([
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
                'note' => $request->note,
                'userId' => $request->user()->id,
            ]);

            transactionPetSalonLog($tran->id, 'New Transaction', '', $request->user()->id);

            DB::commit();
            return responseCreate();
        } catch (Exception $th) {
            DB::rollback();
            return responseInvalid([$th->getMessage()]);
        }
    }

    public function detail(Request $request)
    {
        $detail = DB::table('transaction_pet_salons as t')
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

        $log = DB::table('transaction_pet_salon_logs as tl')
            ->join('transaction_pet_salons as t', 't.id', 'tl.transactionId')
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

        $data = ['detail' => $detail, 'transactionLogs' => $log];

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

            $oldTransaction = transactionpetsalon::find($request->id);

            $transaction = transactionpetsalon::updateOrCreate(
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
                    'note' => $request->note,
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

                            transactionPetSalonLog($request->id, 'Update Transaction', "Data '{$customName}' telah diubah menjadi {$doctor->firstName}", $request->user()->id);
                        } else {
                            transactionPetSalonLog($request->id, 'Update Transaction', "Data '{$customName}' telah diubah menjadi {$newValue}", $request->user()->id);
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
        $count = transactionpetsalon::whereIn('id', $request->id)->count();
        if ($count !== count($request->id)) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['There is any Data not found!'],
            ], 422);
        }

        transactionpetsalon::whereIn('id', $request->id)->update([
            'DeletedBy' => $request->user()->id,
            'isDeleted' => true,
            'DeletedAt' => Carbon::now()
        ]);

        foreach ($request->id as $va) {
            transactionPetSalonLog($va, 'Transaction Deleted', '', $request->user()->id);
        }

        return responseDelete();
    }

    public function export(Request $request)
    {

        $data = DB::table('transaction_pet_salons as t')
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

        $fileName = 'Export Transaksi Pet Salon.xlsx';

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

        $tran = transactionpetsalon::where([['id', '=', $request->transactionId]])->first();

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

            statusTransactionPetSalon($request->transactionId, 'Cek Kondisi Pet', $request->user()->id);

            transactionPetSalonLog($request->transactionId, 'Pemeriksaan pasien oleh ' . $doctor->firstName, '', $request->user()->id);
        } else {

            $validate = Validator::make($request->all(), [
                'reason' => 'required|string',
            ]);

            if ($validate->fails()) {
                $errors = $validate->errors()->all();
                return responseInvalid($errors);
            }

            statusTransactionPetSalon($request->transactionId, 'Ditolak Dokter', $request->user()->id);

            transactionPetSalonLog($request->transactionId, 'Pasien Ditolak oleh ' . $doctor->firstName, $request->reason, $request->user()->id);
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

        $doctor = User::where([['id', '=', $request->doctorId]])->first();

        $user = User::where([['id', '=', $request->user()->id]])->first();

        statusTransactionPetSalon($request->transactionId, 'Menunggu Dokter', $request->user()->id);

        transactionPetSalonLog($request->transactionId, 'Menunggu konfirmasi dokter', 'Dokter dipindahkan oleh ' . $user->firstName, $request->user()->id);

        return responseCreate();
    }

    public function createPetCheck(Request $request)
    {
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

        transactionpetsaloncheck::create([
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
                transactionPetSalonLog($request->transactionId, 'Pet Selesai diperiksa oleh ' . $doctor->firstName, 'Pet dipindahkan ke Pet Clinic', $request->user()->id);
                statusTransactionPetSalon($request->transactionId, 'Pet dipindahkan ke Pet Clinic', $request->user()->id);
            } else {
                transactionPetSalonLog($request->transactionId, 'Pet Selesai diperiksa oleh ' . $doctor->firstName, 'Pet diterima masuk Pet Hotel', $request->user()->id);
                statusTransactionPetSalon($request->transactionId, 'Pet diterima masuk Pet Hotel', $request->user()->id);
            }
        } else {
            transactionPetSalonLog($request->transactionId, 'Pet Selesai diperiksa oleh ' . $doctor->firstName, 'Pet ditolak masuk Pet Hotel karena ' . $request->reasonReject, $request->user()->id);
            statusTransactionPetSalon($request->transactionId, 'Pet ditolak Pet Hotel', $request->user()->id);
        }

        return responseCreate();
    }

    public function Treatment(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'transactionId' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return responseInvalid($errors);
        }

        $tran = transactionpetsalon::where('id', '=', $request->transactionId)->where('isDeleted', '=', 0)->first();

        if (!$tran) {
            return responseInvalid(['Transaction is not found or already deleted!']);
        }

        $services = json_decode($request->services, true);
        $productSell = json_decode($request->productSells, true);
        $productClinic = json_decode($request->productClinics, true);
        $treatmentPlans = json_decode($request->treatmentPlans, true);

        if (count($services) == 0 && count($productSell) == 0 && count($productClinic) == 0 && count($treatmentPlans) == 0) {

            return responseInvalid(['All category must one to filled!']);
        }

        // if ($services) {

        //     $validateServices = Validator::make(
        //         $services,
        //         [
        //             '*.id' => 'required|integer',
        //             '*.quantity' => 'required|integer',
        //         ],
        //         [
        //             '*.id.integer' => 'Id Should be Integer!',
        //             '*.id.required' => 'Id Should be Required!',
        //             '*.quantity.integer' => 'Quantity Should be Integer!',
        //             '*.quantity.required' => 'Quantity Should be Required!',
        //         ]
        //     );

        //     if ($validateServices->fails()) {
        //         $errors = $validateServices->errors()->first();

        //         return response()->json([
        //             'message' => 'The given data was invalid.',
        //             'errors' => [$errors],
        //         ], 422);
        //     }
        // }

        // if ($productSell) {

        //     $validateProductSell = Validator::make(
        //         $productSell,
        //         [
        //             '*.id' => 'required|integer',
        //             '*.quantity' => 'required|integer',
        //         ],
        //         [
        //             '*.id.integer' => 'Id Should be Integer!',
        //             '*.id.required' => 'Id Should be Required!',
        //             '*.quantity.integer' => 'Quantity Should be Integer!',
        //             '*.quantity.required' => 'Quantity Should be Required!',
        //         ]
        //     );

        //     if ($validateProductSell->fails()) {
        //         $errors = $validateProductSell->errors()->first();

        //         return response()->json([
        //             'message' => 'The given data was invalid.',
        //             'errors' => [$errors],
        //         ], 422);
        //     }
        // }

        // if ($productClinic) {

        //     $validateProductClinic = Validator::make(
        //         $productClinic,
        //         [
        //             '*.id' => 'required|integer',
        //             '*.quantity' => 'required|integer',
        //         ],
        //         [
        //             '*.id.integer' => 'Id Should be Integer!',
        //             '*.id.required' => 'Id Should be Required!',
        //             '*.quantity.integer' => 'Quantity Should be Integer!',
        //             '*.quantity.required' => 'Quantity Should be Required!',
        //         ]
        //     );

        //     if ($validateProductClinic->fails()) {
        //         $errors = $validateProductClinic->errors()->first();

        //         return response()->json([
        //             'message' => 'The given data was invalid.',
        //             'errors' => [$errors],
        //         ], 422);
        //     }
        // }

        // if ($treatmentPlans) {

        //     $validateTreatmentPlans = Validator::make(
        //         ['treatmentPlans' => $treatmentPlans],
        //         [
        //             'treatmentPlans' => 'required|array',
        //             'treatmentPlans.*' => 'required|integer',
        //         ],
        //         [
        //             'treatmentPlans.*.required' => 'Id is required!',
        //             'treatmentPlans.*.integer' => 'Id should be integer!',
        //         ]
        //     );

        //     if ($validateTreatmentPlans->fails()) {
        //         $errors = $validateTreatmentPlans->errors()->first();

        //         return response()->json([
        //             'message' => 'The given data was invalid.',
        //             'errors' => [$errors],
        //         ], 422);
        //     }
        // }

        //proses insert
        DB::beginTransaction();
        try {
            foreach ($services as $value) {

                TransactionPetSalonTreatmentService::create([
                    'transactionId' => $request->transactionId,
                    'serviceId' => $value['id'],
                    'quantity' => $value['quantity'],
                    'userId' => $request->user()->id,
                ]);
            }

            foreach ($productSell as $value) {
                TransactionPetSalonTreatmentProduct::create([
                    'transactionId' => $request->transactionId,
                    'productId' => $value['id'],
                    'quantity' => $value['quantity'],
                    'userId' => $request->user()->id,
                ]);
            }

            foreach ($productClinic as $value) {
                TransactionPetSalonTreatmentProduct::create([
                    'transactionId' => $request->transactionId,
                    'productId' => $value['id'],
                    'quantity' => $value['quantity'],
                    'userId' => $request->user()->id,
                ]);
            }

            foreach ($treatmentPlans as $value) {
                TransactionPetSalonTreatmentTreatPlan::create([
                    'transactionId'   => $request->transactionId,
                    'treatmentPlanId' => $value['id'],
                    'userId'          => $request->user()->id,
                ]);
            }

            // Kandang di-assign di step "Tandai Selesai" (markSalonDone), bukan di sini
            statusTransactionPetSalon($request->transactionId, 'Menunggu Persetujuan Policy', $request->user()->id);

            transactionPetSalonLog($request->transactionId, 'Input treatment selesai — menunggu persetujuan policy owner.', '', $request->user()->id);

            return responseCreate();
        } catch (\Throwable $th) {
            DB::rollback();

            return response()->json([
                'message' => 'Failed',
                'errors' => $th,
            ]);
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

        $trans = TransactionPetSalon::find($request->transactionId);

        $cages = transactionPetSalonTreatmentCage::from('transactionPetSalonTreatmentCages as tpcs')
            ->join('facility_unit as fu', 'fu.id', '=', 'tpcs.cageId')
            ->select('fu.id', 'fu.unitName')
            ->where('tpcs.transactionId', $request->transactionId)
            ->first();

        if (!$trans) {
            return responseInvalid(['Transaction is not found!']);
        }

        $phone = CustomerTelephones::where('customerId', '=', $trans->customerId)
            ->where('usage', '=', 'Utama')
            ->first();

        $cust = Customer::find($trans->customerId);

        $dataServices = TransactionPetSalonTreatmentService::from('transactionPetSalonTreatmentServices as tpcs')
            ->join('services as s', 's.id', '=', 'tpcs.serviceId')
            ->join('servicesPrice as sp', 's.id', '=', 'sp.service_id')
            ->select(
                's.id as serviceId',
                's.fullName as serviceName',
                DB::raw("TRIM(tpcs.quantity)+0 as quantity"),
                DB::raw("TRIM(sp.price)+0 as basedPrice"),
            )
            ->where('tpcs.transactionId', '=', $request->transactionId)
            ->where('sp.location_id', '=', $trans->locationId)
            ->get();

        $dataProducts = TransactionPetSalonTreatmentProduct::from('transactionPetSalonTreatmentProducts as rc')
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

    protected function ensureIsArray($data): ?array
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
        $services = $this->ensureIsArray($request->services);
        $products = $this->ensureIsArray($request->products);

        $trans = transactionpetsalon::find($request->transactionId);

        $responseService = [];
        $responseProduct = [];
        $freeItems = [];
        $discounts = [];
        $bundles = [];

        // PRE-FETCH PROMOTIONS TO AVOID N+1 IN LOOPS
        $promoServiceDiscounts = [];
        $promoServiceBundles = [];
        if (!empty($services)) {
            $serviceIds = array_column($services, 'serviceId');
            
            // Fetch discounts for services
            $dataDiscounts = DB::table('promotionMasters as pm')
                ->join('promotion_discount_services as pd', 'pm.id', 'pd.promoMasterId')
                ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
                ->select('pm.id as promoId', 'pm.name', 'pd.serviceId')
                ->where('pm.status', '=', 1)
                ->where('pl.locationId', '=', $trans->locationId)
                ->whereIn('pd.serviceId', $serviceIds)
                ->get();
            foreach ($dataDiscounts as $d) {
                $promoServiceDiscounts[$d->serviceId][] = $d;
            }

            // Fetch bundles for services
            $dataBundles = DB::table('promotionMasters as pm')
                ->join('promotionBundles as pb', 'pm.id', 'pb.promoMasterId')
                ->join('promotion_bundle_detail_services as pbd', 'pb.id', 'pbd.promoBundleId')
                ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
                ->select('pm.id as promoId', 'pm.name', 'pbd.serviceId')
                ->where('pm.status', '=', 1)
                ->where('pl.locationId', '=', $trans->locationId)
                ->whereIn('pbd.serviceId', $serviceIds)
                ->get();
            foreach ($dataBundles as $d) {
                $promoServiceBundles[$d->serviceId][] = $d;
            }
        }

        $promoProductFreeItems = [];
        $promoProductDiscounts = [];
        $promoProductBundles = [];
        if (!empty($products)) {
            $productIds = array_column($products, 'productId');

            // Fetch free items for products
            $dataFreeItems = DB::table('promotionMasters as pm')
                ->join('promotionFreeItems as fi', 'pm.id', 'fi.promoMasterId')
                ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
                ->select('pm.id as promoId', 'pm.name', 'fi.productBuyId', 'fi.quantityBuyItem')
                ->where('pm.status', '=', 1)
                ->where('pl.locationId', '=', $trans->locationId)
                ->whereIn('fi.productBuyId', $productIds)
                ->get();
            foreach ($dataFreeItems as $d) {
                $promoProductFreeItems[$d->productBuyId][] = $d;
            }

            // Fetch discounts for products
            $dataDiscounts = DB::table('promotionMasters as pm')
                ->join('promotion_discount_products as pd', 'pm.id', 'pd.promoMasterId')
                ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
                ->select('pm.id as promoId', 'pm.name', 'pd.productId')
                ->where('pm.status', '=', 1)
                ->where('pl.locationId', '=', $trans->locationId)
                ->whereIn('pd.productId', $productIds)
                ->get();
            foreach ($dataDiscounts as $d) {
                $promoProductDiscounts[$d->productId][] = $d;
            }

            // Fetch bundles for products
            $dataBundles = DB::table('promotionMasters as pm')
                ->join('promotionBundles as pb', 'pm.id', 'pb.promoMasterId')
                ->join('promotion_bundle_detail_products as pbd', 'pb.id', 'pbd.promoBundleId')
                ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
                ->select('pm.id as promoId', 'pm.name', 'pbd.productId')
                ->where('pm.status', '=', 1)
                ->where('pl.locationId', '=', $trans->locationId)
                ->whereIn('pbd.productId', $productIds)
                ->get();
            foreach ($dataBundles as $d) {
                $promoProductBundles[$d->productId][] = $d;
            }
        }

        // PROCESSING SERVICES
        foreach ($services as $value) {
            $tmp_promo = [];
            
            if (isset($promoServiceDiscounts[$value['serviceId']])) {
                foreach ($promoServiceDiscounts[$value['serviceId']] as $data) {
                    $tmp_promo[] = [
                        'promoId' => $data->promoId,
                        'name' => $data->name,
                        'status' => 'discount',
                    ];
                    $discounts[] = $data->promoId;
                }
            }
            if (isset($promoServiceBundles[$value['serviceId']])) {
                foreach ($promoServiceBundles[$value['serviceId']] as $bundleData) {
                    $tmp_promo[] = [
                        'promoId' => $bundleData->promoId,
                        'name' => $bundleData->name,
                        'status' => 'bundle',
                    ];
                    $bundles[] = $bundleData->promoId;
                }
            }

            $responseService[] = [
                'serviceId' => $value['serviceId'],
                'promo' => $tmp_promo
            ];
        }

        // PROCESSING PRODUCTS
        foreach ($products as $value) {
            $tmp_promo = [];
            
            if (isset($promoProductFreeItems[$value['productId']])) {
                foreach ($promoProductFreeItems[$value['productId']] as $data) {
                    if ($value['quantity'] >= $data->quantityBuyItem) {
                        $tmp_promo[] = [
                            'promoId' => $data->promoId,
                            'name' => $data->name,
                            'status' => 'free item',
                        ];
                        $freeItems[] = $data->promoId;
                    }
                }
            }

            if (isset($promoProductDiscounts[$value['productId']])) {
                foreach ($promoProductDiscounts[$value['productId']] as $data) {
                    $tmp_promo[] = [
                        'promoId' => $data->promoId,
                        'name' => $data->name,
                        'status' => 'discount',
                    ];
                    $discounts[] = $data->promoId;
                }
            }
            
            if (isset($promoProductBundles[$value['productId']])) {
                foreach ($promoProductBundles[$value['productId']] as $bundleData) {
                    $tmp_promo[] = [
                        'promoId' => $bundleData->promoId,
                        'name' => $bundleData->name,
                        'status' => 'bundle',
                    ];
                    $bundles[] = $bundleData->promoId;
                }
            }

            $responseProduct[] = [
                'productId' => $value['productId'],
                'promo' => $tmp_promo
            ];
        }

        return response()->json([
            'service' => $responseService,
            'product' => $responseProduct,
            'freeItems' => array_values(array_unique($freeItems)),
            'discounts' => array_values(array_unique($discounts)),
            'bundles' => array_values(array_unique($bundles))
        ]);
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

        $trans = transactionpetsalon::find($request->transactionId);
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
                    'pm.id as promoId', 's.id as serviceId', 's.fullName as item_name', 's.type as category',
                    'pd.discountType', 'pd.percent', 'pd.amount'
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
                    'pm.id as promoId', 'pbuy.fullName as item_name', 'pbuy.id as buy_product_id', 'pfree.id as free_product_id',
                    'pbuy.category', 'fi.quantityBuyItem', 'fi.quantityFreeItem', 'pfree.fullName as free_product_name'
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
                    'pm.id as promoId', 'p.id as productId', 'p.fullName as item_name', 'p.category',
                    'pd.discountType', 'pd.percent', 'pd.amount'
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

                        $existingIdx = collect($results)->search(function($item) use ($data) {
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
                    'pm.name', 'pb.minPurchase',
                    DB::raw("CASE WHEN percentOrAmount = 'amount' THEN 'amount' WHEN percentOrAmount = 'percent' THEN 'percent' ELSE '' END as discountType"),
                    DB::raw("CASE WHEN percentOrAmount = 'amount' THEN amount WHEN percentOrAmount = 'percent' THEN percent ELSE 0 END as totaldiscount")
                )
                ->where('pm.id', '=', $request->basedSale)
                ->where('minPurchase', '<=', $subtotal)
                ->where('maxPurchase', '>=', $subtotal)
                ->first();

            if ($res) {
                if ($res->discountType == 'amount') {
                    $discount_based_sales = $res->totaldiscount;
                    $promoNotes[] = 'Diskon Rp ' . $res->totaldiscount . ' untuk pembelian lebih dari Rp ' . $res->minPurchase;
                    $discountNote = 'Diskon Nominal (Belanja > Rp ' . $res->minPurchase . ')';
                    $totalDiscount = $res->totaldiscount;
                } else if ($res->discountType == 'percent') {
                    $discount_based_sales = $subtotal * ($res->totaldiscount / 100);
                    $promoNotes[] = 'Diskon ' . $res->totaldiscount . '% untuk pembelian lebih dari Rp ' . $res->minPurchase;
                    $discountNote = 'Diskon ' . $res->totaldiscount . ' % (Belanja > Rp ' . $res->minPurchase . ')';
                    $totalDiscount = $res->totaldiscount;
                }
            }
        }

        $response = [
            'purchases' => $results,
            'subtotal' => $subtotal,
            'discount_note' => $discountNote,
            'discount_based_sales' => floatval($discount_based_sales),
            'total_discount' => floatval($totalDiscount),
            'total_payment' => $subtotal - $totalDiscount,
            'promo_notes' => $promoNotes,
        ];
        
        if ($request->basedSale) {
            $response['promoBasedSaleId'] = $request->basedSale;
        }

        return response()->json($response);
    }
    public function payment(Request $request)
    {
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

        $trans = TransactionPetSalon::find($request->transactionId);
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

                            $trx = new transaction_pet_salon_payment();
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
                        $trx = new transaction_pet_salon_payment();
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

                            $trx = new transaction_pet_salon_payment();
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
                        $trx = new transaction_pet_salon_payment();
                        $trx->transactionId = $request->transactionId;
                        $trx->paymentMethodId = $payment['paymentId'];
                        $trx->promoId = $promo->id;
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

                    $trx = new transaction_pet_salon_payment();
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

                    $trx = new transaction_pet_salon_payment();
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

                            $bundle = new transaction_pet_salon_payment_bundle();
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

                            $bundle = new transaction_pet_salon_payment_bundle();
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

            if (array_key_exists('promoBasedSaleId', $detail)) {

                $promo = PromotionMaster::find($detail['promoBasedSaleId']);
                if (!$promo) {
                    DB::rollBack();
                    return responseInvalid(['Promotion based sales not found!']);
                }

                $sales = new transaction_pet_salon_payment_based_sales();
                $sales->transactionId = $request->transactionId;
                $sales->paymentMethodId = $payment['paymentId'];
                $sales->promoId = $detail['promoBasedSaleId'];
                $sales->amountDiscount = $detail['discount_based_sales'];
                $sales->userId = $request->user()->id;
                $sales->save();
            }

            //detail total
            $total = new transaction_pet_salon_payment_total();
            $total->transactionId = $request->transactionId;
            $total->paymentMethodId = $payment['paymentId'];
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

            $jumlahTransaksi = DB::table('transaction_pet_salon_payment_totals as tp')
                ->join('transaction_pet_salon as tpc', 'tp.transactionId', '=', 'tpc.id')
                ->where('tpc.locationId', $locationId)
                ->whereYear('tp.created_at', $tahun)
                ->whereMonth('tp.created_at', $bulan)
                ->count();

            $nomorUrut = str_pad($jumlahTransaksi + 1, 4, '0', STR_PAD_LEFT);

            $notaNumber = "INV/PSL/{$locationId}/{$tahun}/{$bulan}/{$nomorUrut}";
            $total->nota_number = $notaNumber;

            $total->userId = $request->user()->id;
            $total->save();

            transactionPetSalonLog($request->transactionId, 'Nota diterbitkan', '', $request->user()->id);

            statusTransactionPetSalon($request->transactionId, 'Menunggu konfirmasi pembayaran', $request->user()->id);
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
        $trans = TransactionPetSalon::find($request->transactionId);

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

        $pdf = Pdf::loadView('invoice.invoice_petsalon', $data);
        return $pdf->download($namaFile);

        return view('transaction.pet_salon.print_invoice_pet_salon');
    }

    // ─────────────────────────────────────────────────────────────────
    //  POLICY AGREEMENT
    // ─────────────────────────────────────────────────────────────────

    /**
     * GET list policy / contract template aktif.
     * Dipakai untuk mengisi opsi policy di dialog persetujuan.
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
     * POST simpan tanda tangan owner → status: Proses Salon.
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

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $trans = transactionpetsalon::where('id', $request->transactionId)
            ->where('isDeleted', 0)
            ->first();

        if (!$trans) {
            return responseInvalid(['Transaksi tidak ditemukan.']);
        }

        if ($trans->status !== 'Menunggu Persetujuan Policy') {
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
                TransactionPetSalonPolicyAgreement::create([
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

            statusTransactionPetSalon($trans->id, 'Proses Salon', $user->id);

            transactionPetSalonLog(
                $trans->id,
                'Owner menyetujui ' . count($policies) . ' policy — proses salon dimulai.',
                'Ditandatangani oleh: ' . $request->signerName,
                $user->id
            );

            // Notifikasi internal ke Groomer (id=3) di lokasi
            $petName = DB::table('customerPets')->where('id', $trans->petId)->value('petName') ?? 'Pet';
            sendNotificationToStaffAtLocation(
                $trans->locationId,
                [3], // Groomer
                'petsalon',
                "{$petName} siap diproses. Policy sudah ditandatangani.",
                'info'
            );

            DB::commit();
            return responseCreate();
        } catch (\Throwable $th) {
            DB::rollback();
            return responseInvalid([$th->getMessage()]);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    //  TANDAI SELESAI (GROOMER)
    // ─────────────────────────────────────────────────────────────────

    /**
     * POST groomer menandai salon selesai + assign kandang + kirim WA ke customer.
     * Role: Groomer (jobTitleId=3), Admin, atau Manager.
     */
    public function markSalonDone(Request $request)
    {
        $user = $request->user();

        $isGroomer        = ($user->jobTitleId == 3);
        $isAdminOrManager = in_array($user->roleId, [1, 2]);

        if (!$isGroomer && !$isAdminOrManager) {
            return response()->json(['message' => 'Hanya Groomer, Manager, atau Administrator yang dapat menandai salon selesai.'], 403);
        }

        $validate = Validator::make($request->all(), [
            'transactionId' => 'required|integer',
            'cageId'        => 'required|integer',
            'note'          => 'nullable|string',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $trans = transactionpetsalon::where('id', $request->transactionId)
            ->where('isDeleted', 0)
            ->first();

        if (!$trans) {
            return responseInvalid(['Transaksi tidak ditemukan.']);
        }

        if ($trans->status !== 'Proses Salon') {
            return responseInvalid(['Hanya transaksi berstatus "Proses Salon" yang dapat ditandai selesai.']);
        }

        DB::beginTransaction();
        try {
            // Assign kandang
            transactionPetSalonTreatmentCage::create([
                'transactionId' => $trans->id,
                'cageId'        => $request->cageId,
                'userId'        => $user->id,
            ]);

            statusTransactionPetSalon($trans->id, 'Menunggu Penjemputan', $user->id);

            transactionPetSalonLog(
                $trans->id,
                'Proses salon selesai — pet ditempatkan di kandang dan menunggu penjemputan.',
                $request->note ?? '',
                $user->id
            );

            // Kirim WA ke customer
            $customerData = DB::table('customer as c')
                ->join('customerPets as cp', 'cp.customerId', 'c.id')
                ->leftJoin('customerTelephones as ct', function ($join) {
                    $join->on('ct.customerId', 'c.id')
                        ->where('ct.usage', 'Utama')
                        ->where('ct.isDeleted', 0);
                })
                ->where('cp.id', $trans->petId)
                ->select('c.firstName as ownerName', 'cp.name as petName', 'ct.phoneNumber')
                ->first();

            if ($customerData && $customerData->phoneNumber) {
                $petName   = $customerData->petName ?? 'pet Anda';
                $ownerName = $customerData->ownerName ?? 'Owner';
                $message   = "Halo {$ownerName},\n\nProses grooming untuk *{$petName}* sudah selesai 🎉\n"
                    . "Silakan datang untuk menjemput. Pet akan menunggu maksimal 6 jam.\n\n"
                    . "Terima kasih telah mempercayakan perawatan kepada kami. 🐾";
                sendWhatsApp($customerData->phoneNumber, $message);
            }

            DB::commit();
            return responseCreate();
        } catch (\Throwable $th) {
            DB::rollback();
            return responseInvalid([$th->getMessage()]);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    //  INISIASI PEMBAYARAN
    // ─────────────────────────────────────────────────────────────────

    /**
     * POST customer sudah hadir → ubah status ke "Proses Pembayaran".
     * Role: Kasir (jobTitleId=1), Admin, atau Manager.
     */
    public function initiateCheckout(Request $request)
    {
        $user = $request->user();

        $isKasir          = ($user->jobTitleId == 1);
        $isAdminOrManager = in_array($user->roleId, [1, 2]);

        if (!$isKasir && !$isAdminOrManager) {
            return response()->json(['message' => 'Hanya Kasir, Manager, atau Administrator yang dapat memulai proses pembayaran.'], 403);
        }

        $validate = Validator::make($request->all(), [
            'transactionId' => 'required|integer',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $trans = transactionpetsalon::where('id', $request->transactionId)
            ->where('isDeleted', 0)
            ->first();

        if (!$trans) {
            return responseInvalid(['Transaksi tidak ditemukan.']);
        }

        if ($trans->status !== 'Menunggu Penjemputan') {
            return responseInvalid(['Hanya transaksi berstatus "Menunggu Penjemputan" yang dapat diproses pembayaran.']);
        }

        statusTransactionPetSalon($trans->id, 'Proses Pembayaran', $user->id);

        transactionPetSalonLog(
            $trans->id,
            'Customer hadir — proses pembayaran dimulai.',
            '',
            $user->id
        );

        return responseCreate();
    }
}
