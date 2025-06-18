<?php

namespace App\Http\Controllers\Staff;

use DB;
use Validator;
use Carbon\Carbon;
use App\Models\User;
use App\Models\StaffPayroll;
use Illuminate\Http\Request;
use App\Models\Staff\UsersLocation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class StaffPayrollController
{
    public function index(Request $request)
    {
        $itemPerPage = $request->rowPerPage ?? 10;
        $page = $request->goToPage ?? 1;

        $user = $request->user();
        $jobTitleId = $user->jobTitleId;
        $roleId = $user->roleId;

        $data = DB::table('staff_payroll as sp')
            ->join('location as l', 'sp.locationId', '=', 'l.id')
            ->select(
                'sp.id',
                'sp.name',
                'sp.payroll_date',
                'sp.basic_income',
                'sp.annual_increment_incentive',
                'sp.absent_days',
                'sp.late_days',
                'sp.total_income',
                'sp.total_deduction',
                'sp.net_pay',
                'l.locationName'
            );

        if ($roleId == 1) {
            if ($request->locationId) {
                $data = $data->whereIn('sp.locationId', $request->locationId);
            }
        } else {
            $locations = UsersLocation::where('usersId', $user->id)->pluck('id')->toArray();
            $data = $data->whereIn('sp.locationId', $locations);
        }

        $jobTitleAllowedToViewAll = [13, 14, 15]; // Finance, Director, Komisaris
        if (!in_array($jobTitleId, $jobTitleAllowedToViewAll)) {
            $data = $data->where('sp.staffId', $user->id);
        }

        if ($request->search) {
            $data = $data->where('sp.name', 'like', '%' . $request->search . '%');
        }

        $allowedColumns = [
            'sp.name',
            'sp.payroll_date',
            'sp.basic_income',
            'sp.total_income',
            'sp.total_deduction',
            'sp.net_pay'
        ];
        $orderColumn = in_array($request->orderColumn, $allowedColumns) ? $request->orderColumn : 'sp.payroll_date';
        $orderValue = in_array(strtolower($request->orderValue), ['asc', 'desc']) ? $request->orderValue : 'desc';

        $data = $data->orderBy(DB::raw($orderColumn), $orderValue);

        $offset = ($page - 1) * $itemPerPage;
        $count_data = $data->count();
        $totalPaging = ceil($count_data / $itemPerPage);

        $data = $data->offset($offset)->limit($itemPerPage)->get();

        return responseIndex($totalPaging, $data);
    }

    public function create(Request $request)
    {
        $staff = User::findOrFail($request->staffId);

        switch ($staff->jobTitleId) {
            case 5: // Veterinary Nurse (Helper)
                return $this->createPayrollVetNurse($request, $staff);
            default:
                return response()->json(['message' => 'Job title not supported for payroll creation.'], 400);
        }
    }

    private function createPayrollVetNurse(Request $request, $staff)
    {
        $input = $request->all();

        $structuredFields = [
            'lab_xray_incentive',
            'grooming_incentive',
            'replacement_days',
            'absent',
            'late',
            'not_wearing_attribute'
        ];

        foreach ($structuredFields as $field) {
            $input[$field] = $input[$field] ?? ['amount' => 0, 'unitNominal' => 0, 'total' => 0];
            $input[$field]['amount'] = $input[$field]['amount'] ?? 0;
            $input[$field]['unitNominal'] = $input[$field]['unitNominal'] ?? 0;
            $input[$field]['total'] = $input[$field]['total'] ?? 0;
        }

        $flatFields = [
            'attendance_allowance',
            'meal_allowance',
            'positional_allowance',
            'clinic_turnover_bonus',
            'bpjs_health_allowance',
            'current_month_cash_advance',
            'remaining_debt_last_month',
            'stock_opname_inventory',
            'basic_income',
            'annual_increment_incentive'
        ];

        foreach ($flatFields as $field) {
            $input[$field] = $input[$field] ?? 0;
        }

        $total_income = $input['basic_income']
            + $input['annual_increment_incentive']
            + $input['attendance_allowance']
            + $input['meal_allowance']
            + $input['positional_allowance']
            + $input['lab_xray_incentive']['total']
            + $input['grooming_incentive']['total']
            + $input['clinic_turnover_bonus']
            + $input['replacement_days']['total']
            + $input['bpjs_health_allowance'];

        $total_deduction = $input['absent']['total']
            + $input['not_wearing_attribute']['total']
            + $input['late']['total']
            + $input['current_month_cash_advance']
            + $input['remaining_debt_last_month']
            + $input['stock_opname_inventory'];

        $net_pay = $total_income - $total_deduction;

        $payroll = StaffPayroll::create([
            'staffId' => $input['staffId'],
            'name' => $input['name'], 
            'payroll_date' => $input['payroll_date'],
            'locationId' => $input['locationId'],
            'basic_income' => $input['basic_income'],
            'annual_increment_incentive' => $input['annual_increment_incentive'],
            'attendance_allowance' => $input['attendance_allowance'],
            'meal_allowance' => $input['meal_allowance'],
            'positional_allowance' => $input['positional_allowance'],

            'lab_xray_incentive_amount' => $input['lab_xray_incentive']['amount'],
            'lab_xray_incentive_unit_nominal' => $input['lab_xray_incentive']['unitNominal'],
            'lab_xray_incentive_total' => $input['lab_xray_incentive']['total'],

            'grooming_incentive_amount' => $input['grooming_incentive']['amount'],
            'grooming_incentive_unit_nominal' => $input['grooming_incentive']['unitNominal'],
            'grooming_incentive_total' => $input['grooming_incentive']['total'],

            'clinic_turnover_bonus' => $input['clinic_turnover_bonus'],

            'replacement_days_amount' => $input['replacement_days']['amount'],
            'replacement_days_unit_nominal' => $input['replacement_days']['unitNominal'],
            'replacement_days_total' => $input['replacement_days']['total'],

            'bpjs_health_allowance' => $input['bpjs_health_allowance'],

            'absent_amount' => $input['absent']['amount'],
            'absent_unit_nominal' => $input['absent']['unitNominal'],
            'absent_total' => $input['absent']['total'],

            'not_wearing_attribute_amount' => $input['not_wearing_attribute']['amount'],
            'not_wearing_attribute_unit_nominal' => $input['not_wearing_attribute']['unitNominal'],
            'not_wearing_attribute_total' => $input['not_wearing_attribute']['total'],

            'late_amount' => $input['late']['amount'],
            'late_unit_nominal' => $input['late']['unitNominal'],
            'late_total' => $input['late']['total'],

            'current_month_cash_advance' => $input['current_month_cash_advance'],
            'remaining_debt_last_month' => $input['remaining_debt_last_month'],
            'stock_opname_inventory' => $input['stock_opname_inventory'],

            'total_income' => $total_income,
            'total_deduction' => $total_deduction,
            'net_pay' => $net_pay,
            'userId' => $request->user()->id,
        ]);

        return response()->json(['message' => 'Payroll created successfully.', 'data' => $payroll], 201);
    }



    public function export(Request $request)
    {
        if ($request->user()->roleId != 1) {
            return response()->json([
                'message' => 'Unauthorized. Only admin can export data.'
            ], 403);
        }

        $data = DB::table('staff_payroll as sp')
            ->join('location as l', 'sp.locationId', '=', 'l.id')
            ->select(
                'sp.name',
                'sp.payroll_date',
                'l.locationName',
                'sp.basic_income',
                'sp.annual_increment_incentive',
                'sp.absent_days',
                'sp.late_days',
                'sp.total_income',
                'sp.total_deduction',
                'sp.net_pay'
            )
            ->get();

        $spreadsheet = IOFactory::load(public_path() . '/template/staff/' . 'Template_Export_Staff_Payroll.xlsx');
        $sheet = $spreadsheet->getSheet(0);

        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Name');
        $sheet->setCellValue('C1', 'Payroll Date');
        $sheet->setCellValue('D1', 'Branch');
        $sheet->setCellValue('E1', 'Basic Income');
        $sheet->setCellValue('F1', 'Annual Incentive');
        $sheet->setCellValue('G1', 'Absent Days');
        $sheet->setCellValue('H1', 'Late Days');
        $sheet->setCellValue('I1', 'Total Income');
        $sheet->setCellValue('J1', 'Total Deduction');
        $sheet->setCellValue('K1', 'Net Pay');

        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        $sheet->getStyle('A1:K1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:K1')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $row = 2;
        $no = 1;
        foreach ($data as $item) {
            $sheet->setCellValue("A{$row}", $no);
            $sheet->setCellValue("B{$row}", $item->name);
            $sheet->setCellValue("C{$row}", $item->payroll_date);
            $sheet->setCellValue("D{$row}", $item->locationName);
            $sheet->setCellValue("E{$row}", $item->basic_income);
            $sheet->setCellValue("F{$row}", $item->annual_increment_incentive);
            $sheet->setCellValue("G{$row}", $item->absent_days);
            $sheet->setCellValue("H{$row}", $item->late_days);
            $sheet->setCellValue("I{$row}", $item->total_income);
            $sheet->setCellValue("J{$row}", $item->total_deduction);
            $sheet->setCellValue("K{$row}", $item->net_pay);

            $sheet->getStyle("A{$row}:K{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("A{$row}:K{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            $row++;
            $no++;
        }

        foreach (range('A', 'K') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . 'Export_Staff_Payroll.xlsx';
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export_Staff_Payroll.xlsx"',
        ]);
    }
}
