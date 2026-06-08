<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
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
    public function index(Request $request)
    {
        $data = $this->buildIndexQuery($request);
        $data = paginateData($data, $request);

        return response()->json($data);
    }

    public function export(Request $request)
    {
        $date     = Carbon::now()->format('d-m-y');
        $fileName = "Rekap Daftar Servis {$date}.xlsx";

        return Excel::download(
            new ServiceListExport($request->orderValue, $request->orderColumn),
            $fileName
        );
    }

    public function findByCategory(Request $request)
    {
        $data = $this->buildFindByCategoryQuery($request);
        $data = paginateData($data, $request);

        return response()->json($data);
    }

    public function create(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'fullName' => 'required|string',
            'status'   => 'required|integer',
            'color'    => 'required',
            'type'     => 'required|integer',
        ]);

        if ($validate->fails()) {
            return responseErrorValidation($validate->errors()->all());
        }

        $request->merge([
            'userId'    => $request->user()->id,
            'surcharges' => $request->surcharges ?: 0,
        ]);

        DB::beginTransaction();
        try {
            $val                 = $request->except(['userUpdateId']);
            $val['optionPolicy1'] = $request->optionPolicy1 ? 1 : 0;
            $val['optionPolicy2'] = $request->optionPolicy2 ? 1 : 0;
            $val['optionPolicy3'] = $request->optionPolicy3 ? 1 : 0;

            $service = Service::create($val);
            $userId  = $request->user()->id;

            if ($request->categories) {
                $categories = json_decode($request->categories, true);
                foreach ($categories as $category) {
                    DB::table('servicesCategoryList')->insert([
                        'service_id'  => $service->id,
                        'category_id' => $category['value'],
                        'userId'      => $userId,
                        'created_at'  => Carbon::now(),
                    ]);
                }
            }

            if ($request->facility) {
                $facilities = json_decode($request->facility, true);
                foreach ($facilities as $facility) {
                    DB::table('servicesFacility')->insert([
                        'service_id'  => $service->id,
                        'facility_id' => $facility['value'],
                        'userId'      => $userId,
                        'created_at'  => Carbon::now(),
                    ]);
                }
            }

            if ($request->listStaff) {
                $staffList = json_decode($request->listStaff, true);
                foreach ($staffList as $staff) {
                    DB::table('servicesStaff')->insert([
                        'service_id' => $service->id,
                        'fullName'   => $staff['fullName'],
                        'jobName'    => $staff['jobName'],
                        'price'      => $staff['price'] ?? 0,        // fix: was isset($value['price']) - $value undefined
                        'surcharges' => $service->surcharges ?? 0,
                        'userId'     => $userId,
                        'created_at' => Carbon::now(),
                    ]);
                }
            }

            if ($request->productRequired) {
                $products = json_decode($request->productRequired, true);
                foreach ($products as $product) {
                    DB::table('servicesProductRequired')->insert([
                        'service_id'   => $service->id,
                        'product_type' => $product['productType'],
                        'product_name' => $product['productList'],
                        'quantity'     => $product['quantity'],
                        'userId'       => $userId,
                    ]);
                }
            }

            if ($request->location) {
                $locations = json_decode($request->location, true);
                foreach ($locations as $loc) {
                    DB::table('servicesLocation')->insert([
                        'service_id'  => $service->id,
                        'location_id' => $loc['value'],
                        'userId'      => $userId,
                        'created_at'  => Carbon::now(),
                    ]);
                }
            }

            if ($request->listPrice) {
                $prices = json_decode($request->listPrice, true);
                foreach ($prices as $price) {
                    DB::table('servicesPrice')->insert([
                        'service_id'        => $service->id,
                        'customer_group_id' => $price['customerGroup']['value'],
                        'location_id'       => $price['location']['value'],
                        'price'             => $price['price'],
                        'duration'          => $price['duration'],
                        'title'             => $price['title'],
                        'unit'              => $price['unit'],
                        'userId'            => $userId,
                        'created_at'        => Carbon::now(),
                    ]);
                }
            }

            if ($request->followup) {
                $followups = json_decode($request->followup, true);
                foreach ($followups as $followup) {
                    DB::table('servicesFollowup')->insert([
                        'service_id'  => $service->id,
                        'followup_id' => $followup['value'],
                        'userId'      => $userId,
                        'created_at'  => Carbon::now(),
                    ]);
                }
            }

            if ($request->images && count($request->images) > 0) {
                $imagesName = json_decode($request->imagesName, true);
                foreach ($request->images as $index => $file) {
                    if ($file) {
                        $name     = $file->hashName();
                        $file->move(public_path() . '/ServiceListImages/', $name);
                        $fileName = "/ServiceListImages/{$name}";

                        DB::table('servicesImages')->insert([
                            'service_id'    => $service->id,
                            'labelName'     => $imagesName[$index]['name'],
                            'realImageName' => $file->getClientOriginalName(),
                            'imagePath'     => $fileName,
                            'userId'        => $userId,
                            'created_at'    => Carbon::now(),
                        ]);
                    }
                }
            }

            $result = Service::with(['categoryList', 'facilityList', 'staffList', 'productRequiredList', 'locationList', 'priceList', 'imageList'])
                ->where('id', $service->id)
                ->get();

            recentActivity($request->user()->id, 'Service', 'Add Service', 'Create service');

            DB::commit();
            return responseSuccess($result, 'Service created successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return responseError($e->getMessage(), 'Something went wrong');
        }
    }

    public function downloadTemplate()
    {
        return (new TemplateUploadServiceList())->download('Template Upload Layanan.xlsx');
    }

    public function Import(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'file' => 'required|mimes:xls,xlsx',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'errors'  => 'The given data was invalid.',
                'message' => $validate->errors()->all(),
            ], 422);
        }

        $rows = Excel::toArray(new ImportServiceList($request->user()->id), $request->file('file'));
        $src  = $rows[0];

        if (!$src) {
            return response()->json([
                'errors'  => 'The given data was invalid.',
                'message' => ['There is no any data to import'],
            ], 422);
        }

        $tempValue = [];
        $rowNumber = 3;  // data starts at row 3 in template (header = row 1-2)

        foreach ($src as $value) {
            $error = $this->validateImportRow($value, $rowNumber);
            if ($error) {
                return response()->json([
                    'errors'  => 'The given data was invalid.',
                    'message' => [$error],
                ], 422);
            }

            // fix: initialize to [] to avoid undefined variable if key is missing/empty
            $codeLocation = [];
            $codeFollowup = [];
            $codeCategory = [];

            if (!empty($value['lokasi'])) {
                $codeLocation = explode(';', $value['lokasi']);
            }
            if (!empty($value['followup'])) {
                $codeFollowup = explode(';', $value['followup']);
            }
            if (!empty($value['kategori'])) {
                $codeCategory = explode(';', $value['kategori']);
            }

            $tempValue[] = [
                'type'         => $value['tipe'] == 'Pet Shop' ? 1 : ($value['tipe'] == 'Grooming' ? 2 : 3),
                'fullName'     => $value['nama'],
                'simpleName'   => $value['nama_singkat'],
                'status'       => $value['status'],
                'color'        => '#000000',
                'policy'       => $value['ketentuan'] ? 1 : 0,
                'description'  => $value['perkenalan'],
                'introduction' => $value['deskripsi'],
                'surcharges'   => 1,
                'optionPolicy1' => $value['dapat_dipesan_online'] ? 1 : 0,
                'optionPolicy2' => $value['rekam_medis_alasan_kunjungan'] ? 1 : 0,
                'optionPolicy3' => $value['rekam_diagnosa'] ? 1 : 0,
                'location'     => $codeLocation,
                'followup'     => $codeFollowup,
                'category'     => $codeCategory,
            ];

            $rowNumber++;
        }

        try {
            DB::beginTransaction();
            foreach ($tempValue as $value) {
                $val           = $value;
                $val['userId'] = $request->user()->id;
                $service       = Service::create($val);
                $userId        = $request->user()->id;

                foreach ($val['category'] as $categoryId) {
                    DB::table('servicesCategoryList')->insert([
                        'service_id'  => $service->id,
                        'category_id' => (int) $categoryId,
                        'userId'      => $userId,
                        'created_at'  => Carbon::now(),
                    ]);
                }

                foreach ($val['followup'] as $followupId) {
                    DB::table('servicesFollowup')->insert([
                        'service_id'  => $service->id,
                        'followup_id' => $followupId,
                        'userId'      => $userId,
                        'created_at'  => Carbon::now(),
                    ]);
                }

                foreach ($val['location'] as $locationId) {
                    DB::table('servicesLocation')->insert([
                        'service_id'  => $service->id,
                        'location_id' => $locationId,
                        'userId'      => $userId,
                        'created_at'  => Carbon::now(),
                    ]);
                }
            }

            recentActivity($request->user()->id, 'Service', 'Upload Service', 'Upload Template service');
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return responseError($e->getMessage(), 'Something went wrong');
        }

        return response()->json(['message' => 'Insert Data Successful!'], 200);
    }

    public function detail(Request $request)
    {
        $result = Service::with(['categoryList', 'followupList', 'facilityList', 'staffList', 'productRequiredList', 'locationList', 'priceList', 'imageList'])
            ->where('id', $request->id)
            ->get();

        return responseSuccess($result, 'Service detail');
    }

    public function update(Request $request)
    {
        $service = Service::find($request->id);

        $validate = Validator::make($request->all(), [
            'fullName' => 'required|string',
            'status'   => 'required|integer',
            'color'    => 'required',
        ]);

        if (!$service) return responseErrorValidation('Service not found!');
        if ($validate->fails()) return responseErrorValidation($validate->errors()->all());

        $request->merge([
            'userUpdateId' => $request->user()->id,
            'surcharges'   => $request->surcharges ?: 0,
        ]);

        $val = array_filter($request->all(), fn($v) => $v !== null && $v !== "null");
        $val['optionPolicy1'] = $request->optionPolicy1 ? 1 : 0;
        $val['optionPolicy2'] = $request->optionPolicy2 ? 1 : 0;
        $val['optionPolicy3'] = $request->optionPolicy3 ? 1 : 0;

        DB::beginTransaction();
        try {
            $service->update($val);
            $service = Service::find($request->id);
            $userId  = $request->user()->id;

            // --- Categories ---
            $this->softDeleteRemoved('servicesCategoryList', $service->id, $request->categories ?? [], $userId);
            foreach ($this->filterNew($request->categories ?? []) as $category) {
                DB::table('servicesCategoryList')->insert([
                    'service_id'  => $service->id,
                    'category_id' => $category['value'],
                    'userId'      => $userId,
                    'created_at'  => Carbon::now(),
                ]);
            }

            // --- Followups ---
            $this->softDeleteRemoved('servicesFollowup', $service->id, $request->followup ?? [], $userId);
            foreach ($this->filterNew($request->followup ?? []) as $followup) {
                DB::table('servicesFollowup')->insert([
                    'service_id'  => $service->id,
                    'followup_id' => $followup['value'],
                    'userId'      => $userId,
                    'created_at'  => Carbon::now(),
                ]);
            }

            // --- Facilities ---
            $this->softDeleteRemoved('servicesFacility', $service->id, $request->facility ?? [], $userId);
            foreach ($this->filterNew($request->facility ?? []) as $facility) {
                DB::table('servicesFacility')->insert([
                    'service_id'  => $service->id,
                    'facility_id' => $facility['value'],
                    'userId'      => $userId,
                    'created_at'  => Carbon::now(),
                ]);
            }

            // --- Prices ---
            $this->softDeleteRemoved('servicesPrice', $service->id, $request->listPrice ?? [], $userId);
            foreach ($this->filterNew($request->listPrice ?? []) as $price) {
                DB::table('servicesPrice')->insert([
                    'service_id'        => $service->id,
                    'customer_group_id' => $price['customerGroup']['value'],
                    'location_id'       => $price['location']['value'],
                    'price'             => $price['price'],
                    'duration'          => $price['duration'],
                    'title'             => $price['title'],
                    'unit'              => $price['unit'],
                    'userId'            => $userId,
                    'created_at'        => Carbon::now(),
                ]);
            }

            // --- Products Required ---
            $this->softDeleteRemoved('servicesProductRequired', $service->id, $request->productRequired ?? [], $userId);
            foreach ($this->filterNew($request->productRequired ?? []) as $product) {
                DB::table('servicesProductRequired')->insert([
                    'service_id'   => $service->id,
                    'product_type' => $product['productType'],
                    'product_name' => $product['productList'],
                    'quantity'     => $product['quantity'],
                    'userId'       => $userId,
                ]);
            }

            // --- Staff ---
            $existingStaffIds   = DB::table('servicesStaff')->where('service_id', $service->id)->where('isDeleted', 0)->pluck('id');
            $staffWithCreatedAt = array_filter($request->listStaff ?? [], fn($v) => isset($v['created_at']));

            foreach ($existingStaffIds as $id) {
                if (!in_array($id, array_column($staffWithCreatedAt, 'id'))) {
                    DB::table('servicesStaff')->where('id', $id)->update([
                        'isDeleted' => 1,
                        'deletedBy' => $userId,
                        'deletedAt' => Carbon::now(),
                    ]);
                }
            }

            foreach ($request->listStaff ?? [] as $staff) {
                if (in_array($staff['id'], $existingStaffIds->toArray())) {
                    DB::table('servicesStaff')->where('id', $staff['id'])->update([  // fix: was ->where('id', $value) passing full array
                        'price'      => $staff['price'] ?? 0,
                        'surcharges' => $service->surcharges ?? 0,
                    ]);
                }
            }

            foreach ($this->filterNew($request->listStaff ?? []) as $staff) {
                DB::table('servicesStaff')->insert([
                    'service_id' => $service->id,
                    'fullName'   => $staff['fullName'],
                    'jobName'    => $staff['jobName'],
                    'price'      => $staff['price'] ?? 0,
                    'surcharges' => $service->surcharges ?? 0,
                    'userId'     => $userId,
                    'created_at' => Carbon::now(),
                ]);
            }

            // --- Locations ---
            $this->softDeleteRemoved('servicesLocation', $service->id, $request->location ?? [], $userId);
            foreach ($this->filterNew($request->location ?? []) as $loc) {
                DB::table('servicesLocation')->insert([
                    'service_id'  => $service->id,
                    'location_id' => $loc['value'],
                    'userId'      => $userId,
                    'created_at'  => Carbon::now(),
                ]);
            }

            // --- Images ---
            $existingImageIds    = DB::table('servicesImages')->where('service_id', $service->id)->where('isDeleted', 0)->pluck('id');
            $photosWithCreatedAt = array_filter($request->photos ?? [], fn($v) => isset($v['created_at']));

            foreach ($photosWithCreatedAt as $photo) {
                if ($photo['status'] == 'del') {
                    DB::table('servicesImages')->where('id', $photo['id'])->update([  // fix: was ->where('id', $value) passing full array
                        'isDeleted' => 1,
                        'deletedBy' => $userId,
                        'deletedAt' => Carbon::now(),
                    ]);
                }
            }

            foreach ($request->photos ?? [] as $photo) {
                if (in_array($photo['id'], $existingImageIds->toArray())) {
                    DB::table('servicesImages')->where('id', $photo['id'])->update([  // fix: was ->where('id', $value) passing full array
                        'labelName' => $photo['labelName'],
                    ]);
                }
            }

            foreach ($this->filterNew($request->photos ?? []) as $photo) {
                if (is_null($photo['id'])) {
                    preg_match('/^data:image\/(\w+);base64,/', $photo['imagePath'], $matches);
                    $extension  = $matches[1] ?? 'png';
                    $binaryData = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $photo['imagePath']));
                    $filename   = uniqid() . '.' . $extension;

                    file_put_contents(public_path('ServiceListImages') . '/' . $filename, $binaryData);

                    DB::table('servicesImages')->insert([
                        'service_id'    => $service->id,
                        'labelName'     => $photo['label'],
                        'realImageName' => $photo['originalName'],
                        'imagePath'     => '/ServiceListImages/' . $filename,
                        'userId'        => $userId,
                        'created_at'    => Carbon::now(),
                    ]);
                }
            }

            recentActivity($request->user()->id, 'Service', 'Update Service', 'Updated service');
            DB::commit();

            $result = Service::with(['categoryList', 'facilityList', 'staffList', 'productRequiredList', 'locationList', 'priceList', 'imageList'])
                ->find($request->id);

            return responseSuccess($result, 'Update Data Successful!');
        } catch (\Exception $e) {
            DB::rollback();
            return responseError($e->getMessage(), 'Something went wrong');
        }
    }

    public function destroy(Request $request)
    {
        if (!$request->id) {
            return responseErrorValidation(['There is no any Data to delete!']);
        }

        foreach ($request->id as $id) {
            if (!Service::find($id)) {
                return responseErrorValidation(['data with id ' . $id . ' not found!']);
            }
        }

        foreach ($request->id as $id) {
            $service            = Service::find($id);
            $service->DeletedBy = $request->user()->id;
            $service->isDeleted = true;
            $service->DeletedAt = Carbon::now();
            $service->save();

            recentActivity($request->user()->id, 'Service', 'Delete Service', 'Deleted service');
        }

        return responseSuccess($request->id, 'Delete Data Successful!');
    }

    public function ListServiceWithLocation(Request $request)
    {
        $query = DB::table('services as s')
            ->join('servicesLocation as sl', 's.id', 'sl.service_id')
            ->leftjoin('servicesPrice as sp', 's.id', 'sp.service_id')
            ->select('s.id', 's.fullName', 'sp.price')
            ->where('s.isDeleted', '=', 0)
            ->distinct();

        // fix: added null check before count() to avoid error when locationId is not sent
        if ($request->locationId && count($request->locationId) > 0) {
            $query->whereIn('sl.location_id', $request->locationId);
        }

        // Filter hanya service dalam kategori tertentu (opsional)
        if ($request->categoryId) {
            $serviceIds = DB::table('servicesCategoryList')
                ->where('category_id', $request->categoryId)
                ->where('isDeleted', 0)
                ->pluck('service_id');
            $query->whereIn('s.id', $serviceIds);
        }

        // Exclude service dari kategori tertentu (opsional)
        if ($request->excludeCategoryId) {
            $excludeServiceIds = DB::table('servicesCategoryList')
                ->where('category_id', $request->excludeCategoryId)
                ->where('isDeleted', 0)
                ->pluck('service_id');
            $query->whereNotIn('s.id', $excludeServiceIds);
        }

        return responseList($query->get());
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function buildIndexQuery(Request $request)
    {
        $query = DB::table('services as sc')
            ->where('sc.isDeleted', '=', 0)
            ->join('users', 'sc.userId', '=', 'users.id');

        if ($request->type) {
            $query->where('sc.type', $request->type);
        }

        if ($request->search) {
            $query->where('sc.fullName', 'like', '%' . $request->search . '%')
                  ->orWhere('users.firstName', 'like', '%' . $request->search . '%');
        }

        if ($request->location_id) {
            $query->join('servicesLocation as sl', 'sc.id', '=', 'sl.service_id')
                  ->where('sl.isDeleted', 0)
                  ->where('sl.location_id', $request->location_id);
        }

        if ($request->orderValue) {
            $orderByColumn = $request->orderColumn == 'createdAt' ? 'sc.created_at' : $request->orderColumn;
            $query->orderBy($orderByColumn, $request->orderValue);
        } else {
            $query->orderBy('sc.created_at', 'desc');
        }

        return $query->select(
            'sc.id', 'sc.fullName', 'sc.color', 'sc.type', 'sc.optionPolicy1', 'sc.status',
            'sc.created_at', 'sc.updated_at',
            DB::raw("DATE_FORMAT(sc.created_at, '%d/%m/%Y') as createdAt"),
            'users.firstName as createdBy'
        );
    }

    private function buildFindByCategoryQuery(Request $request)
    {
        $query = Service::where('services.isDeleted', '=', 0)
            ->join('servicesCategoryList as scl', 'services.id', '=', 'scl.service_id')
            ->where('scl.isDeleted', 0)
            ->where('scl.category_id', $request->id)
            ->join('users', 'services.userId', '=', 'users.id');

        if ($request->locationId) {
            $query->join('servicesLocation as sl', 'services.id', '=', 'sl.service_id')
                  ->where('sl.isDeleted', 0)
                  ->where('sl.location_id', $request->locationId);
        }

        if ($request->type) {
            $query->where('services.type', $request->type);
        }

        if ($request->search) {
            $query->where('services.fullName', 'like', '%' . $request->search . '%');
        }

        if ($request->orderValue) {
            $orderByColumn = $request->orderColumn == 'createdAt' ? 'sc.created_at' : $request->orderColumn;
            $query->orderBy($orderByColumn, $request->orderValue);
        } else {
            $query->orderBy('services.created_at', 'desc');
        }

        return $query->with(['locationList'])->select(
            'services.id', 'services.fullName', 'services.color', 'services.type', 'services.optionPolicy1', 'services.status',
            'services.created_at', 'services.updated_at',
            DB::raw("DATE_FORMAT(services.created_at, '%d/%m/%Y') as createdAt"),
            'users.firstName as createdBy'
        );
    }

    /**
     * Soft-delete records in $table that are no longer present in $items.
     * An item is considered "existing" if it has a 'created_at' key.
     */
    private function softDeleteRemoved(string $table, int $serviceId, array $items, int $userId): void
    {
        $existingIds = DB::table($table)->where('service_id', $serviceId)->where('isDeleted', 0)->pluck('id');
        $keptIds     = array_column(array_filter($items, fn($v) => isset($v['created_at'])), 'id');

        foreach ($existingIds as $id) {
            if (!in_array($id, $keptIds)) {
                DB::table($table)->where('id', $id)->update([
                    'isDeleted' => 1,
                    'deletedBy' => $userId,
                    'deletedAt' => Carbon::now(),
                ]);
            }
        }
    }

    /** Returns only items that do NOT have 'created_at' (i.e. newly added items). */
    private function filterNew(array $items): array
    {
        return array_values(array_filter($items, fn($v) => !isset($v['created_at'])));
    }

    /** Validates a single row from the import spreadsheet. Returns an error string or null. */
    private function validateImportRow(array $value, int $rowNumber): ?string
    {
        if (!in_array($value['tipe'], ['Pet Shop', 'Grooming', 'Klinik'])) {
            return 'There is any input invalid Tipe at row ' . $rowNumber;
        }

        if ($value['nama'] == '') {
            return 'There is any empty cell on column Nama at row ' . $rowNumber;
        }

        if ($value['status'] != 0 && $value['status'] != 1) {
            return 'There is any input invalid Status at row ' . $rowNumber;
        }

        if ($value['lokasi'] == '') {
            return 'There is any empty cell on column Location at row ' . $rowNumber;
        }

        if (!empty($value['lokasi'])) {
            $codes = explode(';', $value['lokasi']);
            $count = location::whereIn('id', $codes)->where('isDeleted', 0)->count();
            if ($count != count($codes)) {
                return 'There is any input invalid Lokasi Code at row ' . $rowNumber;
            }
        }

        if (!empty($value['followup'])) {
            $codes = explode(';', $value['followup']);
            $count = DB::table('services')->whereIn('id', $codes)->where('isDeleted', 0)->count();
            if ($count != count($codes)) {
                return 'There is any input invalid Followup Code at row ' . $rowNumber;
            }
        }

        if (!empty($value['kategori'])) {
            $codes = explode(';', $value['kategori']);
            $count = DB::table('serviceCategory')->whereIn('id', $codes)->where('isDeleted', 0)->count();
            if ($count != count($codes)) {
                return 'There is any input invalid Kategori Code at row ' . $rowNumber;
            }
        }

        return null;
    }
}
