<?php

namespace App\Http\Controllers\Staff;

use DB;
use Validator;
use Carbon\Carbon;
use App\Models\User;
use App\Models\StaffPayroll;
use Illuminate\Http\Request;
use App\Models\Staff\UsersLocation;
use Illuminate\Support\Facades\Log;
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
                'sp.payrollDate',
                'sp.startDate',
                'sp.endDate',
                'sp.basicIncome',
                'sp.annualIncrementIncentive',
                'sp.absentDays',
                'sp.lateDays',
                'sp.totalIncome',
                'sp.totalDeduction',
                'sp.netPay',
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
            'sp.payrollDate',
            'sp.basicIncome',
            'sp.totalIncome',
            'sp.totalDeduction',
            'sp.netPay'
        ];
        $orderColumn = in_array($request->orderColumn, $allowedColumns) ? $request->orderColumn : 'sp.payrollDate';
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
        $request->validate([
            'staffId' => 'required|integer',
            'name' => 'required|string',
            'locationId' => 'required|integer',
            'payrollDate' => 'required|date',
            'startDate' => 'required|date',
            'endDate' => 'required|date',
            'basicIncome' => 'numeric|min:0',
            'annualIncrementIncentive' => 'numeric|min:0',
            'income' => 'array',
            'expense' => 'array',
        ]);

        $input = $request->all();

        $income = $input['income'] ?? [];
        $expense = $input['expense'] ?? [];

        $structuredIncome = [
            'labXrayIncentive',
            'groomingIncentive',
            'replacementDays',
        ];

        foreach ($structuredIncome as $field) {
            $income[$field] = $income[$field] ?? ['amount' => 0, 'unitNominal' => 0, 'total' => 0];
        }

        $structuredExpense = [
            'absent',
            'late',
            'notWearingAttribute',
        ];

        foreach ($structuredExpense as $field) {
            $expense[$field] = $expense[$field] ?? ['amount' => 0, 'unitNominal' => 0, 'total' => 0];
        }

        $incomeFields = [
            'attendanceAllowance',
            'mealAllowance',
            'positionalAllowance',
            'clinicTurnoverBonus',
            'bpjsHealthAllowance',
        ];

        foreach ($incomeFields as $field) {
            $income[$field] = $income[$field] ?? 0;
        }

        $expenseFields = [
            'currentMonthCashAdvance',
            'remainingDebtLastMonth',
            'stockOpnameInventory',
        ];

        foreach ($expenseFields as $field) {
            $expense[$field] = $expense[$field] ?? 0;
        }

        $totalIncome = $input['basicIncome']
            + $input['annualIncrementIncentive']
            + $income['attendanceAllowance']
            + $income['mealAllowance']
            + $income['positionalAllowance']
            + $income['labXrayIncentive']['total']
            + $income['groomingIncentive']['total']
            + $income['clinicTurnoverBonus']
            + $income['replacementDays']['total']
            + $income['bpjsHealthAllowance'];

        $totalDeduction = $expense['absent']['total']
            + $expense['notWearingAttribute']['total']
            + $expense['late']['total']
            + $expense['currentMonthCashAdvance']
            + $expense['remainingDebtLastMonth']
            + $expense['stockOpnameInventory'];

        $netPay = $totalIncome - $totalDeduction;

        $payroll = StaffPayroll::create([
            'staffId' => $input['staffId'],
            'name' => $input['name'],
            'payrollDate' => $input['payrollDate'],
            'startDate' => $input['startDate'],
            'endDate' => $input['endDate'],
            'locationId' => $input['locationId'],
            'basicIncome' => $input['basicIncome'],
            'annualIncrementIncentive' => $input['annualIncrementIncentive'],

            'attendanceAllowance' => $income['attendanceAllowance'],
            'mealAllowance' => $income['mealAllowance'],
            'positionalAllowance' => $income['positionalAllowance'],
            'clinicTurnoverBonus' => $income['clinicTurnoverBonus'],
            'bpjsHealthAllowance' => $income['bpjsHealthAllowance'],

            'labXrayIncentiveAmount' => $income['labXrayIncentive']['amount'],
            'labXrayIncentiveUnitNominal' => $income['labXrayIncentive']['unitNominal'],
            'labXrayIncentiveTotal' => $income['labXrayIncentive']['total'],

            'groomingIncentiveAmount' => $income['groomingIncentive']['amount'],
            'groomingIncentiveUnitNominal' => $income['groomingIncentive']['unitNominal'],
            'groomingIncentiveTotal' => $income['groomingIncentive']['total'],

            'replacementDaysAmount' => $income['replacementDays']['amount'],
            'replacementDaysUnitNominal' => $income['replacementDays']['unitNominal'],
            'replacementDaysTotal' => $income['replacementDays']['total'],

            'absentAmount' => $expense['absent']['amount'],
            'absentUnitNominal' => $expense['absent']['unitNominal'],
            'absentTotal' => $expense['absent']['total'],

            'lateAmount' => $expense['late']['amount'],
            'lateUnitNominal' => $expense['late']['unitNominal'],
            'lateTotal' => $expense['late']['total'],

            'notWearingAttributeAmount' => $expense['notWearingAttribute']['amount'],
            'notWearingAttributeUnitNominal' => $expense['notWearingAttribute']['unitNominal'],
            'notWearingAttributeTotal' => $expense['notWearingAttribute']['total'],

            'currentMonthCashAdvance' => $expense['currentMonthCashAdvance'],
            'remainingDebtLastMonth' => $expense['remainingDebtLastMonth'],
            'stockOpnameInventory' => $expense['stockOpnameInventory'],

            'totalIncome' => $totalIncome,
            'totalDeduction' => $totalDeduction,
            'netPay' => $netPay,
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
