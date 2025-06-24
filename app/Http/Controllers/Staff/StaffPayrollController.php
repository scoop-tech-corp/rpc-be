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

        if ($request->startDate) {
            $data = $data->whereDate('sp.payrollDate', '>=', $request->startDate);
        }

        if ($request->endDate) {
            $data = $data->whereDate('sp.payrollDate', '<=', $request->endDate);
        }

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
        $user = $request->user();
        $allowedToCreateAll = [13, 14, 15]; // Finance, Director, Komisaris

        $staff = User::findOrFail($request->staffId);

        if (!in_array($user->jobTitleId, $allowedToCreateAll) && $user->id !== $staff->id) {
            return response()->json(['message' => 'You are not authorized to create payroll for this staff.'], 403);
        }

        switch ($staff->jobTitleId) {
            case 1: // Cashier
                return $this->createPayrollCashier($request, $staff);
            case 4: // Paramedic
                return $this->createPayrollParamedic($request, $staff);
            case 5: // Veterinary Nurse (Helper)
                return $this->createPayrollVetNurse($request, $staff);
            case 17: // Vet Doctor
                return $this->createPayrollVetDoctor($request, $staff);
            case 18: // QC
                return $this->createPayrollQualityControl($request, $staff);

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

        if ($staff->jobTitleId !== 5) {
            return response()->json(['message' => 'Staff is not a Veterinary Nurse.'], 400);
        }

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

    private function createPayrollCashier(Request $request, $staff)
    {
        $request->validate([
            'staffId' => 'required|integer',
            'name' => 'required|string',
            'locationId' => 'required|integer',
            'payrollDate' => 'required|date',
            'startDate' => 'required|date',
            'endDate' => 'required|date',
            'income' => 'required|array',
            'income.basicIncome' => 'numeric|min:0',
            'income.annualIncrementIncentive' => 'numeric|min:0',
            'expense' => 'array',
        ]);

        if ($staff->jobTitleId !== 1) {
            return response()->json(['message' => 'Staff is not a Cashier.'], 400);
        }

        $input = $request->all();
        $income = $input['income'] ?? [];
        $expense = $input['expense'] ?? [];

        $structuredIncome = ['replacementDays'];
        $structuredExpense = ['absent', 'late', 'notWearingAttribute'];

        foreach ($structuredIncome as $field) {
            $income[$field] = $income[$field] ?? ['amount' => 0, 'unitNominal' => 0, 'total' => 0];
        }

        foreach ($structuredExpense as $field) {
            $expense[$field] = $expense[$field] ?? ['amount' => 0, 'unitNominal' => 0, 'total' => 0];
        }

        $incomeFields = [
            'basicIncome',
            'annualIncrementIncentive',
            'attendanceAllowance',
            'mealAllowance',
            'positionalAllowance',
            'housingAllowance',
            'petshopTurnoverIncentive',
            'salesAchievementBonus',
            'memberAchievementBonus',
            'bpjsHealthAllowance',
        ];
        foreach ($incomeFields as $field) {
            $income[$field] = $income[$field] ?? 0;
        }

        $expenseFields = [
            'currentMonthCashAdvance',
            'remainingDebtLastMonth',
            'stockOpnameInventory',
            'lostInventory',
        ];
        foreach ($expenseFields as $field) {
            $expense[$field] = $expense[$field] ?? 0;
        }

        $totalIncome = $income['basicIncome']
            + $income['annualIncrementIncentive']
            + $income['attendanceAllowance']
            + $income['mealAllowance']
            + $income['positionalAllowance']
            + $income['housingAllowance']
            + $income['petshopTurnoverIncentive']
            + $income['salesAchievementBonus']
            + $income['memberAchievementBonus']
            + $income['replacementDays']['total']
            + $income['bpjsHealthAllowance'];

        $totalDeduction = $expense['absent']['total']
            + $expense['late']['total']
            + $expense['notWearingAttribute']['total']
            + $expense['currentMonthCashAdvance']
            + $expense['remainingDebtLastMonth']
            + $expense['stockOpnameInventory']
            + $expense['lostInventory'];

        $netPay = $totalIncome - $totalDeduction;

        $payroll = StaffPayroll::create([
            'staffId' => $input['staffId'],
            'name' => $input['name'],
            'locationId' => $input['locationId'],
            'payrollDate' => $input['payrollDate'],
            'startDate' => $input['startDate'],
            'endDate' => $input['endDate'],

            'basicIncome' => $income['basicIncome'],
            'annualIncrementIncentive' => $income['annualIncrementIncentive'],
            'attendanceAllowance' => $income['attendanceAllowance'],
            'mealAllowance' => $income['mealAllowance'],
            'positionalAllowance' => $income['positionalAllowance'],
            'housingAllowance' => $income['housingAllowance'],
            'petshopTurnoverIncentive' => $income['petshopTurnoverIncentive'],
            'salesAchievementBonus' => $income['salesAchievementBonus'],
            'memberAchievementBonus' => $income['memberAchievementBonus'],
            'bpjsHealthAllowance' => $income['bpjsHealthAllowance'],

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
            'lostInventory' => $expense['lostInventory'],

            'totalIncome' => $totalIncome,
            'totalDeduction' => $totalDeduction,
            'netPay' => $netPay,
            'userId' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Payroll created successfully for job title: Cashier',
            'jobTitle' => 'Cashier',
            'data' => $payroll
        ], 201);
    }

    private function createPayrollParamedic(Request $request, $staff)
    {
        $request->validate([
            'staffId' => 'required|integer',
            'name' => 'required|string',
            'locationId' => 'required|integer',
            'payrollDate' => 'required|date',
            'startDate' => 'required|date',
            'endDate' => 'required|date',
            'income' => 'required|array',
            'income.basicIncome' => 'numeric|min:0',
            'income.annualIncrementIncentive' => 'numeric|min:0',
            'expense' => 'array',
        ]);

        if ($staff->jobTitleId !== 4) {
            return response()->json(['message' => 'Staff is not a Paramedic.'], 400);
        }

        $input = $request->all();
        $income = $input['income'] ?? [];
        $expense = $input['expense'] ?? [];

        $structuredIncome = ['labXrayIncentive', 'longShiftReplacement', 'fullShiftReplacement'];
        foreach ($structuredIncome as $field) {
            $income[$field] = $income[$field] ?? ['amount' => 0, 'unitNominal' => 0, 'total' => 0];
        }

        $structuredExpense = ['absent', 'late', 'notWearingAttribute'];
        foreach ($structuredExpense as $field) {
            $expense[$field] = $expense[$field] ?? ['amount' => 0, 'unitNominal' => 0, 'total' => 0];
        }

        $incomeFields = [
            'basicIncome',
            'annualIncrementIncentive',
            'attendanceAllowance',
            'mealAllowance',
            'housingAllowance',
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

        $totalIncome = $income['basicIncome']
            + $income['annualIncrementIncentive']
            + $income['attendanceAllowance']
            + $income['mealAllowance']
            + $income['housingAllowance']
            + $income['clinicTurnoverBonus']
            + $income['bpjsHealthAllowance']
            + $income['labXrayIncentive']['total']
            + $income['longShiftReplacement']['total']
            + $income['fullShiftReplacement']['total'];

        $totalDeduction = $expense['absent']['total']
            + $expense['late']['total']
            + $expense['notWearingAttribute']['total']
            + $expense['currentMonthCashAdvance']
            + $expense['remainingDebtLastMonth']
            + $expense['stockOpnameInventory'];

        $netPay = $totalIncome - $totalDeduction;

        $payroll = StaffPayroll::create([
            'staffId' => $input['staffId'],
            'name' => $input['name'],
            'locationId' => $input['locationId'],
            'payrollDate' => $input['payrollDate'],
            'startDate' => $input['startDate'],
            'endDate' => $input['endDate'],

            'basicIncome' => $income['basicIncome'],
            'annualIncrementIncentive' => $income['annualIncrementIncentive'],
            'attendanceAllowance' => $income['attendanceAllowance'],
            'mealAllowance' => $income['mealAllowance'],
            'housingAllowance' => $income['housingAllowance'],
            'clinicTurnoverBonus' => $income['clinicTurnoverBonus'],
            'bpjsHealthAllowance' => $income['bpjsHealthAllowance'],

            'labXrayIncentiveAmount' => $income['labXrayIncentive']['amount'],
            'labXrayIncentiveUnitNominal' => $income['labXrayIncentive']['unitNominal'],
            'labXrayIncentiveTotal' => $income['labXrayIncentive']['total'],

            'longShiftReplacementAmount' => $income['longShiftReplacement']['amount'],
            'longShiftReplacementUnitNominal' => $income['longShiftReplacement']['unitNominal'],
            'longShiftReplacementTotal' => $income['longShiftReplacement']['total'],

            'fullShiftReplacementAmount' => $income['fullShiftReplacement']['amount'],
            'fullShiftReplacementUnitNominal' => $income['fullShiftReplacement']['unitNominal'],
            'fullShiftReplacementTotal' => $income['fullShiftReplacement']['total'],

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

        return response()->json([
            'message' => 'Payroll created successfully for job title: Paramedic',
            'jobTitle' => 'Paramedic',
            'data' => $payroll
        ], 201);
    }

    private function createPayrollVetDoctor(Request $request, $staff)
    {
        $request->validate([
            'staffId' => 'required|integer',
            'name' => 'required|string',
            'locationId' => 'required|integer',
            'payrollDate' => 'required|date',
            'startDate' => 'required|date',
            'endDate' => 'required|date',
            'basicIncome' => 'numeric|min:0',
            'income' => 'array',
            'expense' => 'array',
        ]);

        if ($staff->jobTitleId !== 17) {
            return response()->json(['message' => 'Staff is not a Veterinary Doctor.'], 400);
        }

        $input = $request->all();
        $income = $input['income'] ?? [];
        $expense = $input['expense'] ?? [];

        $structuredIncome = [
            'patientIncentive',
            'labXrayIncentive',
            'longShiftReplacement',
            'fullShiftReplacement',
        ];
        foreach ($structuredIncome as $field) {
            $income[$field] = $income[$field] ?? ['amount' => 0, 'unitNominal' => 0, 'total' => 0];
        }

        $flatIncomeFields = [
            'attendanceAllowance',
            'mealAllowance',
            'clinicTurnoverBonus',
            'bpjsHealthAllowance',
        ];
        foreach ($flatIncomeFields as $field) {
            $income[$field] = $income[$field] ?? 0;
        }

        $structuredExpense = [
            'absent',
            'late',
            'notWearingAttribute',
        ];
        foreach ($structuredExpense as $field) {
            $expense[$field] = $expense[$field] ?? ['amount' => 0, 'unitNominal' => 0, 'total' => 0];
        }

        $flatExpenseFields = [
            'currentMonthCashAdvance',
            'remainingDebtLastMonth',
            'stockOpnameInventory',
            'stockOpnameLost',
            'stockOpnameExpired',
        ];
        foreach ($flatExpenseFields as $field) {
            $expense[$field] = $expense[$field] ?? 0;
        }

        $totalIncome = $income['basicIncome']
            + $income['attendanceAllowance']
            + $income['mealAllowance']
            + $income['clinicTurnoverBonus']
            + $income['bpjsHealthAllowance']
            + $income['patientIncentive']['total']
            + $income['labXrayIncentive']['total']
            + $income['longShiftReplacement']['total']
            + $income['fullShiftReplacement']['total'];

        $totalDeduction = $expense['absent']['total']
            + $expense['late']['total']
            + $expense['notWearingAttribute']['total']
            + $expense['currentMonthCashAdvance']
            + $expense['remainingDebtLastMonth']
            + $expense['stockOpnameInventory']
            + $expense['stockOpnameLost']
            + $expense['stockOpnameExpired'];

        $netPay = $totalIncome - $totalDeduction;

        $payroll = StaffPayroll::create([
            'staffId' => $input['staffId'],
            'name' => $input['name'],
            'locationId' => $input['locationId'],
            'payrollDate' => $input['payrollDate'],
            'startDate' => $input['startDate'],
            'endDate' => $input['endDate'],

            'basicIncome' => $income['basicIncome'],
            'attendanceAllowance' => $income['attendanceAllowance'],
            'mealAllowance' => $income['mealAllowance'],
            'clinicTurnoverBonus' => $income['clinicTurnoverBonus'],
            'bpjsHealthAllowance' => $income['bpjsHealthAllowance'],

            'patientIncentiveAmount' => $income['patientIncentive']['amount'],
            'patientIncentiveUnitNominal' => $income['patientIncentive']['unitNominal'],
            'patientIncentiveTotal' => $income['patientIncentive']['total'],

            'labXrayIncentiveAmount' => $income['labXrayIncentive']['amount'],
            'labXrayIncentiveUnitNominal' => $income['labXrayIncentive']['unitNominal'],
            'labXrayIncentiveTotal' => $income['labXrayIncentive']['total'],

            'longShiftReplacementAmount' => $income['longShiftReplacement']['amount'],
            'longShiftReplacementUnitNominal' => $income['longShiftReplacement']['unitNominal'],
            'longShiftReplacementTotal' => $income['longShiftReplacement']['total'],

            'fullShiftReplacementAmount' => $income['fullShiftReplacement']['amount'],
            'fullShiftReplacementUnitNominal' => $income['fullShiftReplacement']['unitNominal'],
            'fullShiftReplacementTotal' => $income['fullShiftReplacement']['total'],

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
            'stockOpnameLost' => $expense['stockOpnameLost'],
            'stockOpnameExpired' => $expense['stockOpnameExpired'],

            'totalIncome' => $totalIncome,
            'totalDeduction' => $totalDeduction,
            'netPay' => $netPay,
            'userId' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Payroll created successfully for job title: Veterinary Doctor',
            'jobTitle' => 'Veterinary Doctor',
            'data' => $payroll
        ], 201);
    }

    private function createPayrollQualityControl(Request $request, $staff)
    {
        $request->validate([
            'staffId' => 'required|integer',
            'name' => 'required|string',
            'locationId' => 'required|integer',
            'payrollDate' => 'required|date',
            'startDate' => 'required|date',
            'endDate' => 'required|date',
            'income' => 'required|array',
            'expense' => 'required|array',
        ]);

        if ($staff->jobTitleId !== 18) {
            return response()->json(['message' => 'Staff is not a Quality Control.'], 400);
        }

        $input = $request->all();
        $income = $input['income'];
        $expense = $input['expense'];

        $incomeFields = [
            'basicIncome',
            'annualIncrementIncentive',
            'attendanceAllowance',
            'entertainAllowance',
            'transportAllowance',
            'positionalAllowance',
            'housingAllowance',
            'bpjsHealthAllowance',
            'turnoverAchievementBonus'
        ];
        foreach ($incomeFields as $field) {
            $income[$field] = $income[$field] ?? 0;
        }

        $structuredExpense = ['absent', 'late', 'notWearingAttribute'];
        foreach ($structuredExpense as $field) {
            $expense[$field] = $expense[$field] ?? ['amount' => 0, 'unitNominal' => 0, 'total' => 0];
        }

        $flatExpense = ['currentMonthCashAdvance', 'remainingDebtLastMonth', 'stockOpnameInventory'];
        foreach ($flatExpense as $field) {
            $expense[$field] = $expense[$field] ?? 0;
        }

        $totalIncome = array_sum(array_intersect_key($income, array_flip($incomeFields)));
        $totalDeduction =
            $expense['absent']['total'] +
            $expense['late']['total'] +
            $expense['notWearingAttribute']['total'] +
            $expense['currentMonthCashAdvance'] +
            $expense['remainingDebtLastMonth'] +
            $expense['stockOpnameInventory'];

        $netPay = $totalIncome - $totalDeduction;

        $payroll = StaffPayroll::create([
            'staffId' => $input['staffId'],
            'name' => $input['name'],
            'locationId' => $input['locationId'],
            'payrollDate' => $input['payrollDate'],
            'startDate' => $input['startDate'],
            'endDate' => $input['endDate'],

            'basicIncome' => $income['basicIncome'],
            'annualIncrementIncentive' => $income['annualIncrementIncentive'],
            'attendanceAllowance' => $income['attendanceAllowance'],
            'entertainAllowance' => $income['entertainAllowance'],
            'transportAllowance' => $income['transportAllowance'],
            'positionalAllowance' => $income['positionalAllowance'],
            'housingAllowance' => $income['housingAllowance'],
            'bpjsHealthAllowance' => $income['bpjsHealthAllowance'],
            'turnoverAchievementBonus' => $income['turnoverAchievementBonus'],

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

        return response()->json([
            'message' => 'Payroll created successfully for job title: Quality Control',
            'jobTitle' => 'Quality Control',
            'data' => $payroll
        ], 201);
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
                'sp.payrollDate',
                'l.locationName',
                'sp.basicIncome',
                'sp.annualIncrementIncentive',
                'sp.absentDays',
                'sp.lateDays',
                'sp.totalIncome',
                'sp.totalDeduction',
                'sp.netPay'
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
            $sheet->setCellValue("C{$row}", $item->payrollDate);
            $sheet->setCellValue("D{$row}", $item->locationName);
            $sheet->setCellValue("E{$row}", $item->basicIncome);
            $sheet->setCellValue("F{$row}", $item->annualIncrementIncentive);
            $sheet->setCellValue("G{$row}", $item->absentDays);
            $sheet->setCellValue("H{$row}", $item->lateDays);
            $sheet->setCellValue("I{$row}", $item->totalIncome);
            $sheet->setCellValue("J{$row}", $item->totalDeduction);
            $sheet->setCellValue("K{$row}", $item->netPay);

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
            'Content-Disposition' => 'attachment; filename="Export Staff Payroll.xlsx"',
        ]);
    }

    public function delete(Request $request)
    {
        $user = $request->user();

        $allowedJobTitles = [13, 14, 15];
        if (!in_array($user->jobTitleId, $allowedJobTitles)) {
            return response()->json([
                'result' => 'forbidden',
                'message' => 'You are not authorized to delete payroll records.'
            ], 403);
        }

        $request->validate([
            'id' => 'required|array',
            'id.*' => 'integer|exists:staff_payroll,id'
        ]);

        $count = StaffPayroll::whereIn('id', $request->id)
            ->update(['isDeleted' => 1]);

        return response()->json([
            'result' => 'success',
            'message' => "$count payroll record(s) successfully deleted."
        ], 200);
    }
}
