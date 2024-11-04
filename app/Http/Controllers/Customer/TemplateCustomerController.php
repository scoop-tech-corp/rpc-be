<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use DB;

class TemplateCustomerController extends Controller
{
    function index(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('templateCustomers as tc')
            ->join('users as u', 'tc.userId', 'u.id')
            ->select(
                'tc.id',
                'tc.fileName',
                'tc.fileType',
                DB::raw("DATE_FORMAT(tc.lastChange, '%d/%m/%Y %H:%i:%s') as lastChange"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(tc.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            )
            ->where('tc.isDeleted', '=', 0);

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

        $data = $data->orderBy('tc.updated_at', 'desc');

        $offset = ($page - 1) * $itemPerPage;

        $count_data = $data->count();
        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $data = $data->offset(0)->limit($itemPerPage)->get();
        } else {
            $data = $data->offset($offset)->limit($itemPerPage)->get();
        }

        $totalPaging = $count_data / $itemPerPage;

        return response()->json([
            'totalPagination' => ceil($totalPaging),
            'data' => $data
        ], 200);
    }

    private function Search($request)
    {

        $temp_column = null;

        $data = DB::table('templateCustomers as tc')
            ->join('users as u', 'tc.userId', 'u.id')
            ->select(
                'tc.fileName',
            )
            ->where('tc.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('tc.fileName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'tc.fileName';
        }

        $data = DB::table('templateCustomers as tc')
            ->join('users as u', 'tc.userId', 'u.id')
            ->select(
                'u.firstName',
            )
            ->where('tc.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('u.firstName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'u.firstName';
        }

        return $temp_column;
    }

    function download(Request $request)
    {
        $fileName = "";

        if ($request->fileType == 'importCustomer') {

            $spreadsheet = IOFactory::load(public_path() . '/template/' . 'Template_Input_Customer.xlsx');

            $sheet = $spreadsheet->getSheet(6);

            $titles = DB::table('titleCustomer')
                ->select('id', 'titleName')
                ->where('isActive', '=', 1)
                ->get();

            $row = 2;
            foreach ($titles as $item) {
                // Adjust according to your data structure
                $sheet->setCellValue("A{$row}", $item->id);
                $sheet->setCellValue("B{$row}", $item->titleName);
                // Add more columns as needed
                $row++;
            }

            $row = 2;
            $sheet = $spreadsheet->getSheet(7);

            $customerGroups = DB::table('customerGroups')
                ->select('id', 'customerGroup')
                ->where('isDeleted', '=', 0)
                ->get();

            foreach ($customerGroups as $item) {
                // Adjust according to your data structure
                $sheet->setCellValue("A{$row}", $item->id);
                $sheet->setCellValue("B{$row}", $item->customerGroup);
                // Add more columns as needed
                $row++;
            }

            $row = 2;
            $sheet = $spreadsheet->getSheet(8);

            $locations = DB::table('location')
                ->select('id', 'locationName')
                ->where('isDeleted', '=', 0)
                ->get();

            foreach ($locations as $item) {
                // Adjust according to your data structure
                $sheet->setCellValue("A{$row}", $item->id);
                $sheet->setCellValue("B{$row}", $item->locationName);
                // Add more columns as needed
                $row++;
            }

            $row = 2;
            $sheet = $spreadsheet->getSheet(9);

            $typeIdCustomers = DB::table('typeIdCustomer')
                ->select('id', 'typeName')
                ->where('isActive', '=', 1)
                ->get();

            foreach ($typeIdCustomers as $item) {
                // Adjust according to your data structure
                $sheet->setCellValue("A{$row}", $item->id);
                $sheet->setCellValue("B{$row}", $item->typeName);
                // Add more columns as needed
                $row++;
            }

            $row = 2;
            $sheet = $spreadsheet->getSheet(10);

            $customerOccupations = DB::table('customerOccupation')
                ->select('id', 'occupationName')
                ->where('isActive', '=', 1)
                ->get();

            foreach ($customerOccupations as $item) {
                // Adjust according to your data structure
                $sheet->setCellValue("A{$row}", $item->id);
                $sheet->setCellValue("B{$row}", $item->occupationName);
                // Add more columns as needed
                $row++;
            }

            $row = 2;
            $sheet = $spreadsheet->getSheet(11);

            $referenceCustomers = DB::table('referenceCustomer')
                ->select('id', 'referenceName')
                ->where('isActive', '=', 1)
                ->get();

            foreach ($referenceCustomers as $item) {
                // Adjust according to your data structure
                $sheet->setCellValue("A{$row}", $item->id);
                $sheet->setCellValue("B{$row}", $item->referenceName);
                // Add more columns as needed
                $row++;
            }

            $row = 2;
            $sheet = $spreadsheet->getSheet(12);

            $petCategories = DB::table('petCategory')
                ->select('id', 'petCategoryName')
                ->where('isActive', '=', 1)
                ->get();

            foreach ($petCategories as $item) {
                // Adjust according to your data structure
                $sheet->setCellValue("A{$row}", $item->id);
                $sheet->setCellValue("B{$row}", $item->petCategoryName);
                // Add more columns as needed
                $row++;
            }

            $row = 2;
            $sheet = $spreadsheet->getSheet(13);

            $provinsi = DB::table('provinsi')
                ->select('id', 'namaProvinsi')
                ->get();

            foreach ($provinsi as $item) {
                // Adjust according to your data structure
                $sheet->setCellValue("A{$row}", $item->id);
                $sheet->setCellValue("B{$row}", $item->namaProvinsi);
                // Add more columns as needed
                $row++;
            }

            $row = 2;
            $sheet = $spreadsheet->getSheet(14);

            $kabupaten = DB::table('kabupaten')
                ->select('kodeKabupaten', 'kodeProvinsi', 'namaKabupaten')
                ->get();

            foreach ($kabupaten as $item) {
                // Adjust according to your data structure
                $sheet->setCellValue("A{$row}", $item->kodeKabupaten);
                $sheet->setCellValue("B{$row}", $item->kodeProvinsi);
                $sheet->setCellValue("C{$row}", $item->namaKabupaten);
                // Add more columns as needed
                $row++;
            }

            //usage
            $row = 2;
            $sheet = $spreadsheet->getSheet(15);

            $staticUsage = DB::table('dataStaticCustomer')
                ->select('id', 'name')
                ->where('isDeleted', '=', '0')
                ->where('value', '=', 'Usage')
                ->get();

            foreach ($staticUsage as $item) {
                // Adjust according to your data structure
                $sheet->setCellValue("A{$row}", $item->id);
                $sheet->setCellValue("B{$row}", $item->name);
                // Add more columns as needed
                $row++;
            }

            //tipe telepon

            $row = 2;
            $sheet = $spreadsheet->getSheet(16);

            $staticTelp = DB::table('dataStaticCustomer')
                ->select('id', 'name')
                ->where('isDeleted', '=', '0')
                ->where('value', '=', 'Telephone')
                ->get();

            foreach ($staticTelp as $item) {
                // Adjust according to your data structure
                $sheet->setCellValue("A{$row}", $item->id);
                $sheet->setCellValue("B{$row}", $item->name);
                // Add more columns as needed
                $row++;
            }

            //tipe messenger
            $row = 2;
            $sheet = $spreadsheet->getSheet(17);

            $staticMes = DB::table('dataStaticCustomer')
                ->select('id', 'name')
                ->where('isDeleted', '=', '0')
                ->where('value', '=', 'Messenger')
                ->get();

            foreach ($staticMes as $item) {
                // Adjust according to your data structure
                $sheet->setCellValue("A{$row}", $item->id);
                $sheet->setCellValue("B{$row}", $item->name);
                // Add more columns as needed
                $row++;
            }

            $fileName = 'Template Upload Customer.xlsx';
        }

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
}
