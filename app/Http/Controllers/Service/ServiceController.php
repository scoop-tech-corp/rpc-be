<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Models\Service;
use App\Models\location;
use Illuminate\Http\Request;
use DB;
use Validator;
use Illuminate\Support\Carbon;
use App\Exports\Service\TemplateUploadServiceList;
use Excel;
use App\Imports\Service\ImportServiceList;
use App\Exports\Service\ServiceListExport;


class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        function buildQuery(Request $request)
        {
            $data = DB::table('services as sc')->where('sc.isDeleted', '=', 0);

            if ($request->type) {
                $data = $data->where('sc.type', $request->type);
            }

            $data = $data->join('users', 'sc.userId', '=', 'users.id');

            if ($request->search) {
                $data = $data->where('sc.fullName', 'like', '%' . $request->search . '%')->orWhere('users.firstName', 'like', '%' . $request->search . '%');
            }

            if ($request->location_id) {
                $data = $data->join('servicesLocation as sl', 'sc.id', '=', 'sl.service_id')
                    ->where('sl.isDeleted', 0)
                    ->where('sl.location_id', $request->location_id);
            }

            if ($request->orderValue) {
                $orderByColumn = $request->orderColumn == 'createdAt' ? 'sc.created_at' : $request->orderColumn;
                $data = $data->orderBy($orderByColumn, $request->orderValue);
            } else {
                $data = $data->orderBy('sc.created_at', 'desc');
            }

            return $data->select('sc.id', 'sc.fullName', 'sc.color', 'sc.type', 'sc.optionPolicy1', 'sc.status', 'sc.created_at', 'sc.updated_at', DB::raw("DATE_FORMAT(sc.created_at, '%d/%m/%Y') as createdAt"), 'users.firstName as createdBy');
        }

        $data = buildQuery($request);
        $data = paginateData($data, $request);

        return response()->json($data);
    }
    public function export(Request $request)
    {
        $fileName = "";
        $date = Carbon::now()->format('d-m-y');

        $fileName = "Rekap Daftar Servis " . $date . ".xlsx";

        return Excel::download(
            new ServiceListExport(
                $request->orderValue,
                $request->orderColumn,
            ),
            $fileName
        );
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function findByCategory(Request $request)
    {
        function buildQuery(Request $request)
        {
            $data = Service::where('services.isDeleted', '=', 0)
                ->join('servicesCategoryList as scl', 'services.id', '=', 'scl.service_id')
                ->where('scl.isDeleted', 0)
                ->where('scl.category_id', $request->id);

            if ($request->locationId) {
                $data = $data->join('servicesLocation as sl', 'services.id', '=', 'sl.service_id')
                    ->where('sl.isDeleted', 0)
                    ->where('sl.location_id', $request->locationId);
            }

            if ($request->type) {
                $data = $data->where('services.type', $request->type);
            }

            $data = $data->join('users', 'services.userId', '=', 'users.id');

            if ($request->search) {
                $data = $data->where('services.fullName', 'like', '%' . $request->search . '%');
            }

            if ($request->orderValue) {
                $orderByColumn = $request->orderColumn == 'createdAt' ? 'sc.created_at' : $request->orderColumn;
                $data = $data->orderBy($orderByColumn, $request->orderValue);
            } else {
                $data = $data->orderBy('services.created_at', 'desc');
            }

            return $data->with(['locationList'])->select('services.id', 'services.fullName', 'services.color', 'services.type', 'services.optionPolicy1', 'services.status', 'services.created_at', 'services.updated_at', DB::raw("DATE_FORMAT(services.created_at, '%d/%m/%Y') as createdAt"), 'users.firstName as createdBy');
        }

        $data = buildQuery($request);
        $data = paginateData($data, $request);

        return response()->json($data);
    }
    public function create(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'fullName' => 'required|string',
            'status' => 'required|integer',
            'color' => 'required',
            'type' => 'required|integer',
        ]);

        if ($validate->fails()) {
            return responseErrorValidation($validate->errors()->all());
        }
        $request->merge(['userId' => $request->user()->id, 'surcharges' => $request->surcharges ? $request->surcharges : 0]);

        DB::beginTransaction();
        try {

            $val = $request->all();
            $val = $request->except(['userUpdateId']);
            $val['optionPolicy1'] = $request->optionPolicy1 ? 1 : 0;
            $val['optionPolicy2'] = $request->optionPolicy2 ? 1 : 0;
            $val['optionPolicy3'] = $request->optionPolicy3 ? 1 : 0;
            $this->createService = Service::create($val);
            $this->userId = $request->user()->id;
            if ($request->categories) {
                $request->categories = json_decode($request->categories, true);

                collect($request->categories)->map(function (array $category) {
                    DB::table('servicesCategoryList')->insert([
                        'service_id' => $this->createService->id,
                        'category_id' => $category['value'],
                        'userId' => $this->userId,
                        'created_at' => Carbon::now(),
                    ]);
                });
            }
            if ($request->facility) {
                $request->facility = json_decode($request->facility, true);
                collect($request->facility)->map(function (array $facility) {
                    DB::table('servicesFacility')->insert([
                        'service_id' => $this->createService->id,
                        'facility_id' => $facility['value'],
                        'userId' => $this->userId,
                        'created_at' => Carbon::now(),
                    ]);
                });
            }
            if ($request->listStaff) {
                $request->listStaff = json_decode($request->listStaff, true);
                collect($request->listStaff)->map(function (array $listStaff) {
                    DB::table('servicesStaff')->insert([
                        'service_id' => $this->createService->id,
                        'fullName' => $listStaff['fullName'],
                        'jobName' => $listStaff['jobName'],
                        'price' => isset($value['price']) ? $listStaff['price'] : 0,
                        'surcharges' => isset($this->createService->surcharges) ? $this->createService->surcharges : 0,
                        'userId' => $this->userId,
                        'created_at' => Carbon::now(),

                    ]);
                });
            }

            if ($request->productRequired) {
                $request->productRequired = json_decode($request->productRequired, true);
                collect($request->productRequired)->map(function (array $productRequired) {
                    DB::table('servicesProductRequired')->insert([
                        'service_id' => $this->createService->id,
                        'product_type' => $productRequired['productType'],
                        'product_name' => $productRequired['productList'],
                        'quantity' => $productRequired['quantity'],
                        'userId' => $this->userId,
                    ]);
                });
            }
            if ($request->location) {
                $request->location = json_decode($request->location, true);
                collect($request->location)->map(function (array $location) {
                    DB::table('servicesLocation')->insert([
                        'service_id' => $this->createService->id,
                        'location_id' => $location['value'],
                        'userId' => $this->userId,
                        'created_at' => Carbon::now(),
                    ]);
                });
            }
            if ($request->listPrice) {
                $request->listPrice = json_decode($request->listPrice, true);
                collect($request->listPrice)->map(function (array $listPrice) {
                    DB::table('servicesPrice')->insert([
                        'service_id' => $this->createService->id,
                        'customer_group_id' => $listPrice['customerGroup']['value'],
                        'location_id' => $listPrice['location']['value'],
                        'price' => $listPrice['price'],
                        'duration' => $listPrice['duration'],
                        'title' => $listPrice['title'],
                        'unit' => $listPrice['unit'],
                        'userId' => $this->userId,
                        'created_at' => Carbon::now(),
                    ]);
                });
            }
            if ($request->followup) {
                $request->followup = json_decode($request->followup, true);
                collect($request->followup)->map(function (array $followup) {
                    DB::table('servicesFollowup')->insert([
                        'service_id' => $this->createService->id,
                        'followup_id' => $followup['value'],
                        'userId' => $this->userId,
                        'created_at' => Carbon::now(),
                    ]);
                });
            }
            if ($request->images && count($request->images) > 0) {
                $this->imagesName = json_decode($request->imagesName, true);
                collect($request->images)->map(function ($file, $index) {
                    if ($file) {
                        $name = $file->hashName();
                        $file->move(public_path() . '/ServiceListImages/', $name);
                        $fileName = "/ServiceListImages/" . $name;

                        DB::table('servicesImages')->insert([
                            'service_id' => $this->createService->id,
                            'labelName' => $this->imagesName[$index]['name'],
                            'realImageName' => $file->getClientOriginalName(),
                            'imagePath' => $fileName,
                            'userId' => $this->userId,
                            'created_at' => Carbon::now(),
                        ]);
                    }
                });
            }
            $result = Service::with(['categoryList', 'facilityList', 'staffList', 'productRequiredList', 'locationList', 'priceList', 'imageList'])
                ->where('id', $this->createService->id)
                ->get();
            DB::commit();
            return responseSuccess($result, 'Service created successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return responseError($e->getMessage(), 'Something went wrong');
        }
    }

    public function downloadTemplate()
    {
        // return view('example-input-service-list');
        return (new TemplateUploadServiceList())->download('Template Upload Layanan.xlsx');
    }

    public function Import(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'file' => 'required|mimes:xls,xlsx',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'errors' => 'The given data was invalid.',
                'message' => $errors,
            ], 422);
        }

        $id = $request->user()->id;
        $rows = Excel::toArray(new ImportServiceList($id), $request->file('file'));
        $src = $rows[0];
        $tempValue = [];
        $count_row = 1;

        if ($src) {
            $count_row = $count_row + 2;
            foreach ($src as $value) {
                if ($value['tipe'] != 'Pet Shop' && $value['tipe'] != 'Grooming' && $value['tipe'] != 'Klinik') {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any input invalid Tipe at row ' . $count_row],
                    ], 422);
                }

                if ($value['nama'] == "") {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any empty cell on column Nama at row ' . $count_row],
                    ], 422);
                }

                if ($value['status'] != 0 && $value['status'] != 1) {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any input invalid Status at row ' . $count_row],
                    ], 422);
                }

                if ($value['lokasi'] == "") {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any empty cell on column Location at row ' . $count_row],
                    ], 422);
                }

                if (array_key_exists('lokasi', $value) && $value['lokasi'] !== "") {
                    $codeLocation = explode(';', $value['lokasi']);
                    if (count($codeLocation)) {
                        $location = location::whereIn('id', $codeLocation)->where('isDeleted', '=', 0)->count();
                        if ($location != count($codeLocation)) {
                            return response()->json([
                                'errors' => 'The given data was invalid.',
                                'message' => ['There is any input invalid Lokasi Code at row ' . $count_row],
                            ], 422);
                        }
                    }
                }

                if (array_key_exists('followup', $value) && $value['followup'] !== "") {
                    $codeFollowup = explode(';', $value['followup']);
                    if ($codeFollowup && $value['followup'] != '') {
                        $followup = DB::table('services')->whereIn('id', $codeFollowup)->where('isDeleted', '=', 0)->count();
                        if ($followup != count(array_values($codeFollowup))) {
                            return response()->json([
                                'errors' => 'The given data was invalid.',
                                'message' => ['There is any input invalid Followup Code at row ' . $count_row],
                            ], 422);
                        }
                    }
                }

                // Check 'kategori' key before exploding
                if (array_key_exists('kategori', $value) && $value['kategori'] !== "") {
                    $codeCategory = explode(';', $value['kategori']);
                    if (count($codeCategory) && $value['kategori'] != '') {
                        $category = DB::table('serviceCategory')->whereIn('id', $codeCategory)->where('isDeleted', '=', 0)->count();
                        if ($category != count($codeCategory)) {
                            return response()->json([
                                'errors' => 'The given data was invalid.',
                                'message' => ['There is any input invalid Kategori Code at row ' . $count_row],
                            ], 422);
                        }
                    }
                }


                $tempValue[] = [
                    'type' => $value['tipe'] == 'Pet Shop' ? 1 : ($value['tipe'] == 'Grooming' ? 2 : 3),
                    'fullName' => $value['nama'],
                    'simpleName' => $value['nama_singkat'],
                    'status' => $value['status'],
                    'color' => '#000000',
                    'policy' => $value['ketentuan'] ? 1 : 0,
                    'description' => $value['perkenalan'],
                    'introduction' => $value['deskripsi'],
                    'surcharges' => 1,
                    'optionPolicy1' => $value['dapat_dipesan_online'] ? 1 : 0,
                    'optionPolicy2' => $value['rekam_medis_alasan_kunjungan'] ? 1 : 0,
                    'optionPolicy3' => $value['rekam_diagnosa'] ? 1 : 0,
                    'location' => $codeLocation && $value['lokasi'] != '' ? $codeLocation : [],
                    'followup' => $codeFollowup && $value['followup'] != '' ? $codeFollowup : [],
                    'category' => $codeCategory && $value['kategori'] != '' ? $codeCategory : []
                ];
            }
            try {
                DB::beginTransaction();
                foreach ($tempValue as $key => $value) {
                    $val = $value;
                    $val['userId'] = $request->user()->id;
                    $this->createService = Service::create($val);
                    $this->userId = $request->user()->id;

                    if (count($val['category'])) {
                        collect($val['category'])->map(function ($category) {
                            DB::table('servicesCategoryList')->insert([
                                'service_id' => $this->createService->id,
                                'category_id' => (int)$category,
                                'userId' => $this->userId,
                                'created_at' => Carbon::now(),
                            ]);
                        });
                    }

                    if (count($val['followup'])) {
                        collect($val['followup'])->map(function ($followup) {
                            DB::table('servicesFollowup')->insert([
                                'service_id' => $this->createService->id,
                                'followup_id' => $followup,
                                'userId' => $this->userId,
                                'created_at' => Carbon::now(),
                            ]);
                        });
                    }

                    if (count($val['location'])) {
                        collect($val['location'])->map(function ($location) {
                            DB::table('servicesLocation')->insert([
                                'service_id' => $this->createService->id,
                                'location_id' => $location,
                                'userId' => $this->userId,
                                'created_at' => Carbon::now(),
                            ]);
                        });
                    }
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                return responseError($e->getMessage(), 'Something went wrong');
            }
        } else {
            return response()->json([
                'errors' => 'The given data was invalid.',
                'message' => ['There is no any data to import'],
            ], 422);
        }

        return response()->json(
            [
                'message' => 'Insert Data Successful!',
            ],
            200
        );
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Service  $service
     * @return \Illuminate\Http\Response
     */
    public function detail(Request $request)
    {
        $result = Service::with(['categoryList', 'followupList', 'facilityList', 'staffList', 'productRequiredList', 'locationList', 'priceList', 'imageList'])
            ->where('id', $request->id)
            ->get();

        return responseSuccess($result, 'Service detail');
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateServiceRequest  $request
     * @param  \App\Models\Service  $service
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $service = Service::find($request->id);

        $validate = Validator::make($request->all(), [
            'fullName' => 'required|string',
            'status' => 'required|integer',
            'color' => 'required'
        ]);
        if (!$service) return responseErrorValidation('Service not found!');
        if ($validate->fails()) return responseErrorValidation($validate->errors()->all());


        $request->merge(['userUpdateId' => $request->user()->id, 'surcharges' => $request->surcharges ? $request->surcharges : 0]);

        // Hasil array setelah menghapus nilai null
        $val = array_filter($request->all(), function ($value) {
            return $value !== null && $value !== "null";
        });
        $val['optionPolicy1'] = $request->optionPolicy1 ? 1 : 0;
        $val['optionPolicy2'] = $request->optionPolicy2 ? 1 : 0;
        $val['optionPolicy3'] = $request->optionPolicy3 ? 1 : 0;


        DB::beginTransaction();
        try {
            $service->update($val);

            $this->updateService = Service::find($request->id);
            $this->userId = $request->user()->id;
            //$request->categories = json_decode($request->categories, true);
            $getId = DB::table('servicesCategoryList')->where('service_id', $this->updateService->id)->where('isDeleted', 0)->get()->pluck('id');
            $categoryWithCreatedAt = array_filter($request->categories, function ($value) {
                return isset($value['created_at']);
            });
            foreach ($getId as $key => $value) {
                if (!in_array($value, array_column($categoryWithCreatedAt, 'id'))) {
                    DB::table('servicesCategoryList')->where('id', $value)->update([
                        'isDeleted' => 1,
                        'deletedBy' => $this->userId,
                        'deletedAt' => Carbon::now(),
                    ]);
                }
            }
            $categoryWithoutCreatedAt = array_filter($request->categories, function ($value) {
                return !isset($value['created_at']);
            });
            collect($categoryWithoutCreatedAt)->map(function (array $category) {
                DB::table('servicesCategoryList')->insert([
                    'service_id' => $this->updateService->id,
                    'category_id' => $category['value'],
                    'userId' => $this->userId,
                    'created_at' => Carbon::now(),
                ]);
            });
            //$request->followup = json_decode($request->followup, true);
            $getId = DB::table('servicesFollowup')->where('service_id', $this->updateService->id)->where('isDeleted', 0)->get()->pluck('id');
            $followupWithCreatedAt = array_filter($request->followup, function ($value) {
                return isset($value['created_at']);
            });
            foreach ($getId as $key => $value) {
                if (!in_array($value, array_column($followupWithCreatedAt, 'id'))) {
                    DB::table('servicesFollowup')->where('id', $value)->update([
                        'isDeleted' => 1,
                        'deletedBy' => $this->userId,
                        'deletedAt' => Carbon::now(),
                    ]);
                }
            }
            $followupWithoutCreatedAt = array_filter($request->followup, function ($value) {
                return !isset($value['created_at']);
            });
            collect($followupWithoutCreatedAt)->map(function (array $followup) {
                DB::table('servicesFollowup')->insert([
                    'service_id' => $this->updateService->id,
                    'followup_id' => $followup['value'],
                    'userId' => $this->userId,
                    'created_at' => Carbon::now(),
                ]);
            });

            //$request->facility = json_decode($request->facility, true);
            $getId = DB::table('servicesFacility')->where('service_id', $this->updateService->id)->where('isDeleted', 0)->get()->pluck('id');
            $facilityWithCreatedAt = array_filter($request->facility, function ($value) {
                return isset($value['created_at']);
            });
            foreach ($getId as $key => $value) {
                if (!in_array($value, array_column($facilityWithCreatedAt, 'id'))) {
                    DB::table('servicesFacility')->where('id', $value)->update([
                        'isDeleted' => 1,
                        'deletedBy' => $this->userId,
                        'deletedAt' => Carbon::now(),
                    ]);
                }
            }
            $facilityWithoutCreatedAt = array_filter($request->facility, function ($value) {
                return !isset($value['created_at']);
            });
            collect($facilityWithoutCreatedAt)->map(function (array $facility) {
                DB::table('servicesFacility')->insert([
                    'service_id' => $this->updateService->id,
                    'facility_id' => $facility['value'],
                    'userId' => $this->userId,
                    'created_at' => Carbon::now(),
                ]);
            });

            //$request->listPrice = json_decode($request->listPrice, true);
            $getId = DB::table('servicesPrice')->where('service_id', $this->updateService->id)->where('isDeleted', 0)->get()->pluck('id');
            $priceWithCreatedAt = array_filter($request->listPrice, function ($value) {
                return isset($value['created_at']);
            });

            foreach ($getId as $key => $value) {
                if (!in_array($value, array_column($priceWithCreatedAt, 'id'))) {
                    DB::table('servicesPrice')->where('id', $value)->update([
                        'isDeleted' => 1,
                        'deletedBy' => $this->userId,
                        'deletedAt' => Carbon::now(),
                    ]);
                }
            }

            $priceWithoutCreatedAt = array_filter($request->listPrice, function ($value) {
                return !isset($value['created_at']);
            });

            collect($priceWithoutCreatedAt)->map(function (array $listPrice) {
                DB::table('servicesPrice')->insert([
                    'service_id' => $this->updateService->id,
                    'customer_group_id' => $listPrice['customerGroup']['value'],
                    'location_id' => $listPrice['location']['value'],
                    'price' => $listPrice['price'],
                    'duration' => $listPrice['duration'],
                    'title' => $listPrice['title'],
                    'unit' => $listPrice['unit'],
                    'userId' => $this->userId,
                    'created_at' => Carbon::now(),
                ]);
            });


            //$request->productRequired = json_decode($request->productRequired, true);

            $getId = DB::table('servicesProductRequired')->where('service_id', $this->updateService->id)->where('isDeleted', 0)->get()->pluck('id');
            $productWithCreatedAt = array_filter($request->productRequired, function ($value) {
                return isset($value['created_at']);
            });

            foreach ($getId as $key => $value) {
                if (!in_array($value, array_column($productWithCreatedAt, 'id'))) {
                    DB::table('servicesProductRequired')->where('id', $value)->update([
                        'isDeleted' => 1,
                        'deletedBy' => $this->userId,
                        'deletedAt' => Carbon::now(),
                    ]);
                }
            }

            $productWithoutCreatedAt = array_filter($request->productRequired, function ($value) {
                return !isset($value['created_at']);
            });
            collect($productWithoutCreatedAt)->map(function (array $productRequired) {
                DB::table('servicesProductRequired')->insert([
                    'service_id' => $this->updateService->id,
                    'product_type' => $productRequired['productType'],
                    'product_name' => $productRequired['productList'],
                    'quantity' => $productRequired['quantity'],
                    'userId' => $this->userId,
                ]);
            });

            //$request->listStaff = json_decode($request->listStaff, true);
            $getId = DB::table('servicesStaff')->where('service_id', $this->updateService->id)->where('isDeleted', 0)->get()->pluck('id');
            $staffWithCreatedAt = array_filter($request->listStaff, function ($value) {
                return isset($value['created_at']);
            });

            foreach ($getId as $key => $value) {
                if (!in_array($value, array_column($staffWithCreatedAt, 'id'))) {
                    DB::table('servicesStaff')->where('id', $value)->update([
                        'isDeleted' => 1,
                        'deletedBy' => $this->userId,
                        'deletedAt' => Carbon::now(),
                    ]);
                }
            }

            foreach ($request->listStaff as $key => $value) {
                if (in_array($value['id'], $getId->toArray())) {
                    DB::table('servicesStaff')->where('id', $value)->update([
                        'price' => isset($value['price']) ? $value['price'] : 0,
                        'surcharges' => isset($this->updateService->surcharges) ? $this->updateService->surcharges : 0,
                    ]);
                }
            }

            $staffWithoutCreatedAt = array_filter($request->listStaff, function ($value) {
                return !isset($value['created_at']);
            });
            collect($staffWithoutCreatedAt)->map(function (array $listStaff) {
                DB::table('servicesStaff')->insert([
                    'service_id' => $this->updateService->id,
                    'fullName' => $listStaff['fullName'],
                    'jobName' => $listStaff['jobName'],
                    'price' => isset($value['price']) ? $value['price'] : 0,
                    'surcharges' => isset($this->updateService->surcharges) ? $this->updateService->surcharges : 0,
                    'userId' => $this->userId,
                    'created_at' => Carbon::now(),

                ]);
            });

            //$request->location = json_decode($request->location, true);
            $getId = DB::table('servicesLocation')->where('service_id', $this->updateService->id)->where('isDeleted', 0)->get()->pluck('id');
            $locationWithCreatedAt = array_filter($request->location, function ($value) {
                return isset($value['created_at']);
            });

            foreach ($getId as $key => $value) {
                if (!in_array($value, array_column($locationWithCreatedAt, 'id'))) {
                    DB::table('servicesLocation')->where('id', $value)->update([
                        'isDeleted' => 1,
                        'deletedBy' => $this->userId,
                        'deletedAt' => Carbon::now(),
                    ]);
                }
            }

            $locationWithoutCreatedAt = array_filter($request->location, function ($value) {
                return !isset($value['created_at']);
            });

            collect($locationWithoutCreatedAt)->map(function (array $location) {
                DB::table('servicesLocation')->insert([
                    'service_id' => $this->updateService->id,
                    'location_id' => $location['value'],
                    'userId' => $this->userId,
                    'created_at' => Carbon::now(),
                ]);
            });

            //$request->photos = json_decode($request->imagesName, true);
            $getId = DB::table('servicesImages')->where('service_id', $this->updateService->id)->where('isDeleted', 0)->get()->pluck('id');
            $imageWithCreatedAt = array_filter($request->photos, function ($value) {
                return isset($value['created_at']);
            });

            foreach ($imageWithCreatedAt as $key => $value) {
                if ($value['status'] == 'del') {
                    DB::table('servicesImages')->where('id', $value)->update([
                        'isDeleted' => 1,
                        'deletedBy' => $this->userId,
                        'deletedAt' => Carbon::now(),
                    ]);
                }
            }

            foreach ($request->photos as $key => $value) {
                if (in_array($value['id'], $getId->toArray())) {
                    DB::table('servicesImages')->where('id', $value)->update([
                        'labelName' => $value['labelName'],
                    ]);
                }
            }
            $imageWithoutCreatedAt = array_filter($request->photos, function ($value) {
                return !isset($value['created_at']);
            });
            $imageWithoutCreatedAt = array_values($imageWithoutCreatedAt);

            $this->images = $request->photos;

            foreach ($imageWithoutCreatedAt as $file) {

                if (is_null($file['id'])) {

                    $base64String = $file['imagePath'];

                    preg_match('/^data:image\/(\w+);base64,/', $base64String, $matches);
                    $extension = isset($matches[1]) ? $matches[1] : 'png';

                    $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $base64String);
                    $binaryData = base64_decode($base64Data);

                    $filename = uniqid() . '.' . $extension;

                    $path = public_path('ServiceListImages');

                    $filePath = $path . '/' . $filename;
                    file_put_contents($filePath, $binaryData);

                    DB::table('servicesImages')->insert([
                        'service_id' => $this->updateService->id,
                        'labelName' => $file['label'],
                        'realImageName' => $file['originalName'],
                        'imagePath' => '/ServiceListImages/' . $filename,
                        'userId' => $this->userId,
                        'created_at' => Carbon::now(),
                    ]);
                }
            }
            DB::commit();

            $result = Service::with(['categoryList', 'facilityList', 'staffList', 'productRequiredList', 'locationList', 'priceList', 'imageList'])->find($request->id);
            return responseSuccess($result, 'Update Data Successful!');
        } catch (\Exception $e) {
            DB::rollback();
            return responseError($e->getMessage(), 'Something went wrong');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Service  $service
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        if (!$request->id) {
            return responseErrorValidation(['There is no any Data to delete!']);
        }

        foreach ($request->id as $va) {
            $res = Service::find($va);

            if (!$res) {
                return responseErrorValidation(['data with id ' . $va .  ' not found!']);
            }
        }

        foreach ($request->id as $va) {
            $cat = Service::find($va);
            $cat->DeletedBy = $request->user()->id;
            $cat->isDeleted = true;
            $cat->DeletedAt = Carbon::now();
            $cat->save();
        }

        return responseSuccess($request->id, 'Delete Data Successful!');
    }

    public function ListServiceWithLocation(Request $request)
    {
        if (count($request->locationId) == 0) {

            $data = DB::table('services as s')
                ->join('servicesLocation as sl', 's.id', 'sl.service_id')
                ->select('s.id', 's.fullName')
                ->where('s.isDeleted', '=', 0)
                ->distinct()
                ->get();
        } else {
            $data = DB::table('services as s')
                ->join('servicesLocation as sl', 's.id', 'sl.service_id')
                ->select('s.id', 's.fullName')
                ->wherein('sl.location_id', $request->locationId)
                ->where('s.isDeleted', '=', 0)
                ->distinct()
                ->get();
        }

        return responseList($data);
    }
}
