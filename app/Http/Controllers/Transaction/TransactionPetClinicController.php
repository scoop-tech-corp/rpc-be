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
use App\Models\TransactionPetClinic;
use App\Models\TransactionPetClinicAdvice;
use App\Models\transactionPetClinicAnamnesis;
use App\Models\TransactionPetClinicCheckUpResult;
use App\Models\TransactionPetClinicDiagnose;
use App\Models\TransactionPetClinicTreatment;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Validator;
use DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TransactionPetClinicController extends Controller
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

        if ($request->startDate > $request->endDate || $request->startDate == $request->endDate) {
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

        if ($request->startDate > $request->endDate || $request->startDate == $request->endDate) {
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
        $data = transactionPetClinicAnamnesis::where('locationId', $trx->locationId)->count();

        $date = Carbon::now()->format('d');
        $month = Carbon::now()->format('m');
        $year = Carbon::now()->format('Y');

        $regisNo = str_pad($data + 1, 3, 0, STR_PAD_LEFT) . '/LPIK-RIS-RPC-VET/' . $trx->locationId . '/' . $date . '/' . $month . '/' . $year;

        return response()->json($regisNo, 200);
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

        $cust = Customer::find($trx->customerId);

        $phone = CustomerTelephones::where('customerId', '=', $trx->customerId)
            ->where('usage', '=', 'Utama')
            ->first();

        $pet = CustomerPets::join('petCategory', 'customerPets.petCategoryId', '=', 'petCategory.id')
            ->where('customerPets.id', $trx->petId)
            ->select('customerPets.petName', 'petCategory.petCategoryName as petCategory')
            ->first();

        return response()->json([
            'ownerName' => $cust->firstName,
            'phoneNumber' => $phone->phoneNumber,
            'type' => $pet->petCategory,
            'petName' => $pet->petName,
        ], 200);
    }

    public function createPetCheck(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'transactionPetClinicId' => 'required|integer',
            'petCheckRegistrationNo' => 'required|string',
            'locationId' => 'required|integer',

            'isAnthelmintic' => 'required|boolean',
            'anthelminticDate' => 'required|date',
            'anthelminticBrand' => 'required|string',

            'isVaccination' => 'required|boolean',
            'vaccinationDate' => 'required|date',
            'vaccinationBrand' => 'required|string',

            'isFleaMedicine' => 'required|boolean',
            'fleaMedicineDate' => 'required|date',
            'fleaMedicineBrand' => 'required|string',

            'previousAction' => 'required|string',
            'othersCompalints' => 'nullable|string',

            'weight' => 'required|numeric',
            'weightCategory' => 'required|integer',

            'temperature' => 'required|numeric',
            'temperatureBottom' => 'required|numeric',
            'temperatureTop' => 'required|numeric',
            'temperatureCategory' => 'required|integer',

            'isLice' => 'required|boolean',
            'noteLice' => 'nullable|string',

            'isFlea' => 'required|boolean',
            'noteFlea' => 'nullable|string',

            'isCaplak' => 'required|boolean',
            'noteCaplak' => 'nullable|string',

            'isTungau' => 'required|boolean',
            'noteTungau' => 'nullable|string',

            'ectoParasitCategory' => 'nullable|integer',

            'isNematoda' => 'required|boolean',
            'noteNematoda' => 'nullable|string',
            //endoparasit
            'isTermatoda' => 'required|boolean',
            'noteTermatoda' => 'nullable|string',

            'isCestode' => 'required|boolean',
            'noteCestode' => 'nullable|string',

            'isFungiFound' => 'required|boolean',

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
            'isSnot' => 'required|boolean',
            'noteSnot' => 'nullable|string',

            'breathType' => 'nullable|integer',
            'breathSoundType' => 'nullable|integer',
            'breathSoundNote' => 'nullable|string',
            'othersFoundBreath' => 'nullable|string',

            'isPulsus' => 'required|boolean',
            'heartSound' => 'nullable|integer',
            'othersFoundHeart' => 'nullable|string',

            'othersFoundSkin' => 'nullable|string',
            'othersFoundHair' => 'nullable|string',

            //urogenital
            'maleTesticles' => 'nullable|integer',
            'othersMaleTesticles' => 'nullable|string',
            'penisCondition' => 'nullable|string',
            'vaginalDischargeType' => 'nullable|integer',
            'urinationType' => 'nullable|string',
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
            'isEarwax' => 'required|boolean',
            'earwaxCharacter' => 'nullable|string',
            'othersFoundEar' => 'nullable|string',

            'isInpatient' => 'required|boolean',
            'noteInpatient' => 'nullable|string',

            'isTherapeuticFeed' => 'required|boolean',
            'noteTherapeuticFeed' => 'nullable|string',

            'imuneBooster' => 'required|boolean',
            'suplement' => 'nullable|string',
            'desinfeksi' => 'nullable|string',
            'care' => 'nullable|string',

            'isGrooming' => 'required|boolean',
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

            'isSitologi' => 'required|boolean',
            'noteSitologi' => 'nullable|string',

            'isBloodLab' => 'required|boolean',
            'noteBloodLab' => 'nullable|string',

            'isSurgery' => 'required|boolean',
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
                'locationId' => $request->locationId,
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
                'isPulsus' => $request->isPulsus,
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
                'isEarwax' => $request->isEarwax,
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
                'isSitologi' => $request->isSitologi,
                'noteSitologi' => $request->noteSitologi,
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
                'isInpatient' => $request->isInpatient,
                'noteInpatient' => $request->noteInpatient,
                'isTherapeuticFeed' => $request->isTherapeuticFeed,
                'noteTherapeuticFeed' => $request->noteTherapeuticFeed,
                'imuneBooster' => $request->imuneBooster,
                'suplement' => $request->suplement,
                'desinfeksi' => $request->desinfeksi,
                'care' => $request->care,
                'isGrooming' => $request->isGrooming,
                'noteGrooming' => $request->noteGrooming,
                'othersNoteAdvice' => $request->othersNoteAdvice,
                'nextControlCheckup' => $request->nextControlCheckup,
                'userId' => $request->user()->id,
                'userUpdateId' => $request->user()->id, // Jika `userUpdateId` sama dengan `userId`, bisa disesuaikan
            ]);

            DB::commit();
            return responseCreate();
        } catch (Exception $th) {
            DB::rollback();
            return responseInvalid([$th->getMessage()]);
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
