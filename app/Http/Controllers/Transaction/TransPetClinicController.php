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
use App\Models\Service;
use App\Models\Staff\UsersLocation;
use App\Models\TransactionPetClinic;
use App\Models\TransactionPetClinicAdvice;
use App\Models\transactionPetClinicAnamnesis;
use App\Models\TransactionPetClinicCheckUpResult;
use App\Models\TransactionPetClinicDiagnose;
use App\Models\TransactionPetClinicRecipes;
use App\Models\TransactionPetClinicServices;
use App\Models\TransactionPetClinicTreatment;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Validator;
use DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TransPetClinicController extends Controller
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
                DB::raw("IFNULL(cg.customerGroup,'') as customerGroup"),
                't.typeOfCare',
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

        if (!$request->user()->roleId == 1 || !$request->user()->roleId == 2) {
            $locations = UsersLocation::select('id')->where('usersId', $request->user()->id)->get()->pluck('id')->toArray();
            $data = $data->whereIn('l.id', $locations);
        } else {

            if ($request->locationId) {

                $data = $data->whereIn('l.id', $request->locationId);
            }
        }

        if ($request->customerGroupId) {

            $data = $data->whereIn('cg.id', $request->customerGroupId);
        }

        if ($request->typeOfCare) {

            $data = $data->where('t.typeOfCare', '=', $request->typeOfCare);
        }

        if ($request->search) {
            $res = $this->Search($request);
            if ($res) {
                $data = $data->where($res[0], 'like', '%' . $request->search . '%');

                for ($i = 1; $i < count($res); $i++) {

                    $data = $data->orWhere($res[$i], 'like', '%' . $request->search . '%');
                }
            } else {
                $data = [];
                return response()->json([
                    'totalPagination' => 0,
                    'data' => $data
                ], 200);
            }
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
                'typeOfCare' => 'required|int',
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
                    responseInvalid(['Customer is Not Found']);
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

            $tran = TransactionPetClinic::create([
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

            transactionLog($tran->id, 'New Transaction', '', $request->user()->id);

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

        $log = DB::table('transactionLogs as tl')
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
                'typeOfCare' => 'required|int',
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
                'typeOfCare' => 'required|int',
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

                            transactionLog($request->id, 'Update Transaction', "Data '{$customName}' telah diubah menjadi {$doctor->firstName}", $request->user()->id);
                        } else {
                            transactionLog($request->id, 'Update Transaction', "Data '{$customName}' telah diubah menjadi {$newValue}", $request->user()->id);
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

        foreach ($request->id as $va) {
            $res = TransactionPetClinic::find($va);

            if (!$res) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['There is any Data not found!'],
                ], 422);
            }
        }

        foreach ($request->id as $va) {

            $tran = TransactionPetClinic::find($va);

            $tran->DeletedBy = $request->user()->id;
            $tran->isDeleted = true;
            $tran->DeletedAt = Carbon::now();
            $tran->save();

            transactionLog($va, 'Transaction Deleted', '', $request->user()->id);
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

        if ($request->status == 1) {

            statusTransactionPetClinic($request->transactionId, 'Cek Kondisi Pet', $request->user()->id);

            transactionPetClinicLog($request->transactionId, 'Pemeriksaan pasien oleh ' . $doctor->firstName, '', $request->user()->id);
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
            'nextControlCheckup' => 'required|date',

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
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return responseInvalid($errors);
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

        $dataServices = json_decode($request->services, true);

        foreach ($dataServices as $val) {
            $find = Service::find($val['serviceId']);
            if (!$find) {
                return responseInvalid(['Service not found!']);
            }
        }

        $ResultRecipe = json_decode($request->recipes, true);

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
                    'giveMedicine' => $val['giveMedicine'],
                    'notes' => $val['notes'],
                    'userId' => $request->user()->id,
                    'userUpdateId' => $request->user()->id,
                ]);
            }

            TransactionPetClinic::updateOrCreate(
                ['id' => $request->transactionPetClinicId],
                [
                    'status' => "Proses Pembayaran",
                    'userUpdatedId' => $request->user()->id,
                ]
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

        $dataServices = TransactionPetClinicServices::from('transaction_pet_clinic_services as tpcs')
            ->join('services as s', 's.id', '=', 'tpcs.serviceId')
            ->join('servicesPrice as sp', 's.id', '=', 'sp.service_id')
            ->select(
                's.id as serviceId',
                's.fullName as serviceName',
                'tpcs.quantity',
                'sp.price as basedPrice'
            )
            ->where('tpcs.transactionPetClinicId', '=', $request->transactionPetClinicId)
            ->where('sp.location_id', '=', $trans->locationId)
            ->get();

        $dataRecipes = TransactionPetClinicRecipes::from('transaction_pet_clinic_recipes as rc')
            ->join('products as p', 'p.id', '=', 'rc.productId')
            ->join('productLocations as pl', 'p.id', '=', 'pl.productId')
            ->select(
                'p.id as productId',
                'p.fullName as productName',
                'rc.dosage',
                'rc.unit',
                'rc.frequency',
                'rc.giveMedicine',
                'rc.notes',
                'p.price as basedPrice'
            )
            ->where('rc.transactionPetClinicId', '=', $request->transactionPetClinicId)
            ->where('pl.locationId', '=', $trans->locationId)
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
        $validate = Validator::make($request->all(), [
            'transactionPetClinicId' => 'required|integer',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();
            return responseInvalid($errors);
        }

        $trans = TransactionPetClinic::find($request->transactionPetClinicId);

        if (!$trans) {
            return responseInvalid(['Transaction not found!']);
        }

        $custGroup = "";

        if (!is_null($trans->customerId)) {
            $cust = Customer::find($trans->customerId);
            $custGroup = $cust->customerGroupId;
        }

        $dataRecipes = json_decode($request->recipes, true);
        $dataServices = json_decode($request->services, true);
        $dataProducts = json_decode($request->products, true);

        $tempFree = [];
        $tempDiscount = [];
        $resultBundle = [];

        //free item
        foreach ($dataRecipes as $value) {

            $res = DB::table('promotionMasters as pm')
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
                ->where('fi.productBuyId', '=', $value['productId'])
                ->where('pcg.customerGroupId', '=', $custGroup)
                ->where('pm.startDate', '<=', Carbon::now())
                ->where('pm.endDate', '>=', Carbon::now())
                ->where('pm.status', '=', 1)
                ->get()
                ->toArray();

            $tempFree = array_merge($tempFree, $res);
        }

        foreach ($dataProducts as $value) {

            $res = DB::table('promotionMasters as pm')
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
                ->where('fi.productBuyId', '=', $value['productId'])
                ->where('pcg.customerGroupId', '=', $custGroup)
                ->where('pm.startDate', '<=', Carbon::now())
                ->where('pm.endDate', '>=', Carbon::now())
                ->where('pm.status', '=', 1)
                ->get()
                ->toArray();

            $tempFree = array_merge($tempFree, $res);
        }

        //discount
        foreach ($dataRecipes as $value) {
            $res = DB::table('promotionMasters as pm')
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
                ->where('pd.productId', '=', $value['productId'])
                ->where('pcg.customerGroupId', '=', $custGroup)
                ->where('pm.startDate', '<=', Carbon::now())
                ->where('pm.endDate', '>=', Carbon::now())
                ->where('pm.status', '=', 1)
                ->get()
                ->toArray();

            $tempDiscount = array_merge($tempDiscount, $res);
        }

        foreach ($dataProducts as $value) {

            $res = DB::table('promotionMasters as pm')
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
                ->where('pd.productId', '=', $value['productId'])
                ->where('pcg.customerGroupId', '=', $custGroup)
                ->where('pm.startDate', '<=', Carbon::now())
                ->where('pm.endDate', '>=', Carbon::now())
                ->where('pm.status', '=', 1)
                ->get()
                ->toArray();

            $tempDiscount = array_merge($tempDiscount, $res);
        }

        foreach ($dataServices as $value) {

            $res = DB::table('promotionMasters as pm')
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
                ->where('pd.serviceId', '=', $value['serviceId'])
                ->where('pcg.customerGroupId', '=', $custGroup)
                ->where('pm.startDate', '<=', Carbon::now())
                ->where('pm.endDate', '>=', Carbon::now())
                ->where('pm.status', '=', 1)
                ->get()
                ->toArray();

            $tempDiscount = array_merge($tempDiscount, $res);
        }

        //bundle
        foreach ($dataRecipes as $value) {
            // return $value;
            $res = DB::table('promotionMasters as pm')
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
                ->where('pbd.productId', '=', $value['productId'])
                ->where('pcg.customerGroupId', '=', $custGroup)
                ->where('pm.startDate', '<=', Carbon::now())
                ->where('pm.endDate', '>=', Carbon::now())
                ->where('pm.status', '=', 1)
                ->get();

            foreach ($res as $valdtl) {

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

        foreach ($dataServices as $value) {
            // return $value;
            $res = DB::table('promotionMasters as pm')
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
                ->where('pbd.serviceId', '=', $value['serviceId'])
                ->where('pcg.customerGroupId', '=', $custGroup)
                ->where('pm.startDate', '<=', Carbon::now())
                ->where('pm.endDate', '>=', Carbon::now())
                ->where('pm.status', '=', 1)
                ->get();

            foreach ($res as $valdtl) {

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

        foreach ($dataProducts as $value) {
            // return $value;
            $res = DB::table('promotionMasters as pm')
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
                ->where('pbd.productId', '=', $value['productId'])
                ->where('pcg.customerGroupId', '=', $custGroup)
                ->where('pm.startDate', '<=', Carbon::now())
                ->where('pm.endDate', '>=', Carbon::now())
                ->where('pm.status', '=', 1)
                ->get();

            foreach ($res as $valdtl) {

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

        $resultBasedSales = [];

        $totalTransaction = 0;
        foreach ($dataRecipes as $value) {
            $totalTransaction += $value['priceOverall'];
        }

        foreach ($dataServices as $value) {
            $totalTransaction += $value['priceOverall'];
        }

        foreach ($dataProducts as $value) {
            $totalTransaction += $value['priceOverall'];
        }

        $findBasedSales = DB::table('promotionMasters as pm')
            ->leftjoin('promotionCustomerGroups as pcg', 'pm.id', 'pcg.promoMasterId')
            ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
            ->join('promotionBasedSales as bs', 'pm.id', 'bs.promoMasterId')
            ->where('pl.locationId', '=', $trans->locationId)
            ->where('bs.minPurchase', '<', $totalTransaction)
            ->where('bs.maxPurchase', '>', $totalTransaction)
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
            'freeItem' => $tempFree,
            'discount' => $tempDiscount,
            'bundles' => $resultBundle,
            'basedSales' => $resultBasedSales,
        ];

        return response()->json($result);
    }

    public function promoResult(Request $request) {}

    public function paymentInpatient(Request $request) {}

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
}
