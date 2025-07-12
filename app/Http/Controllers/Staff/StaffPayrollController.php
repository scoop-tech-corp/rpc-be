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
use Barryvdh\DomPDF\Facade\Pdf;

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
        $allowedToCreateAll = ['Finance', 'Director', 'Komisaris'];

        $staff = User::with('jobTitle')->findOrFail($request->staffId);

        if (!$staff->jobTitle || !isset($staff->jobTitle->jobName)) {
            return response()->json(['message' => 'Job title for this staff not found.'], 400);
        }

        $staffJobTitle = $staff->jobTitle->jobName;

        if (!in_array($user->jobTitle->jobName, $allowedToCreateAll) && $user->id !== $staff->id) {
            return response()->json(['message' => 'You are not authorized to create payroll for this staff.'], 403);
        }

        switch ($staff->jobTitle->id) {
            case 1:
                return $this->createPayrollCashier($request, $staff);
            case 3:
                return $this->createPayrollVetNurseGroomer($request, $staff);
            case 4:
                return $this->createPayrollParamedic($request, $staff);
            case 5:
                return $this->createPayrollVetNurseHelper($request, $staff);
            case 16:
                return $this->createPayrollManager($request, $staff);
            case 17:
                return $this->createPayrollVetDoctor($request, $staff);
            case 18:
                return $this->createPayrollQualityControl($request, $staff);
            case 19:
                return $this->createPayrollOfficeStaff($request, $staff);
            default:
                return response()->json(['message' => 'Job title not supported for payroll creation.'], 400);
        }
    }


    private function createPayrollVetNurseHelper(Request $request, $staff)
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

        if ($staff->jobTitleId !== "5") {
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

    private function createPayrollVetNurseGroomer(Request $request, $staff)
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

        if (strtolower($staff->jobTitle->name) !== 'vet nurse groomer') {
            return response()->json(['message' => 'Staff is not Vet Nurse Groomer.'], 400);
        }

        $input = $request->all();
        $income = $input['income'];
        $expense = $input['expense'];

        $incomeFields = [
            'basicIncome',
            'annualIncrementIncentive',
            'attendanceAllowance',
            'mealAllowance',
            'positionalAllowance',
            'labXrayIncentiveTotal',
            'groomingIncentiveTotal',
            'bonusGroomingAchievement',
            'bonusSalesAchievement',
            'replacementDaysTotal',
            'bpjsHealthAllowance',
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
            'mealAllowance' => $income['mealAllowance'],
            'positionalAllowance' => $income['positionalAllowance'],

            'labXrayIncentiveAmount' => $income['labXrayIncentiveAmount'] ?? 0,
            'labXrayIncentiveUnitNominal' => $income['labXrayIncentiveUnitNominal'] ?? 0,
            'labXrayIncentiveTotal' => $income['labXrayIncentiveTotal'],

            'groomingIncentiveAmount' => $income['groomingIncentiveAmount'] ?? 0,
            'groomingIncentiveUnitNominal' => $income['groomingIncentiveUnitNominal'] ?? 0,
            'groomingIncentiveTotal' => $income['groomingIncentiveTotal'],

            'bonusGroomingAchievement' => $income['bonusGroomingAchievement'],
            'bonusSalesAchievement' => $income['bonusSalesAchievement'],
            'replacementDaysAmount' => $income['replacementDaysAmount'] ?? 0,
            'replacementDaysUnitNominal' => $income['replacementDaysUnitNominal'] ?? 0,
            'replacementDaysTotal' => $income['replacementDaysTotal'],

            'bpjsHealthAllowance' => $income['bpjsHealthAllowance'],

            'absentAmount' => $expense['absent']['amount'],
            'absentUnitNominal' => $expense['absent']['unitNominal'],
            'absentTotal' => $expense['absent']['total'],

            'notWearingAttributeAmount' => $expense['notWearingAttribute']['amount'],
            'notWearingAttributeUnitNominal' => $expense['notWearingAttribute']['unitNominal'],
            'notWearingAttributeTotal' => $expense['notWearingAttribute']['total'],

            'lateAmount' => $expense['late']['amount'],
            'lateUnitNominal' => $expense['late']['unitNominal'],
            'lateTotal' => $expense['late']['total'],

            'currentMonthCashAdvance' => $expense['currentMonthCashAdvance'],
            'remainingDebtLastMonth' => $expense['remainingDebtLastMonth'],
            'stockOpnameInventory' => $expense['stockOpnameInventory'],

            'totalIncome' => $totalIncome,
            'totalDeduction' => $totalDeduction,
            'netPay' => $netPay,
            'userId' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Payroll created successfully for Veterinary Nurse (Groomer)',
            'jobTitle' => 'Veterinary Nurse (Groomer)',
            'data' => $payroll
        ], 201);
    }

    private function createPayrollOfficeStaff(Request $request, $staff)
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

        if (strtolower($staff->jobTitle->jobName) !== 'office staff') {
            return response()->json(['message' => 'Staff is not Office Staff.'], 400);
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
            'hardshipAllowance',
            'familyAllowance',
            'bpjsHealthAllowance',
            'clinicTurnoverBonus'
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
            'hardshipAllowance' => $income['hardshipAllowance'],
            'familyAllowance' => $income['familyAllowance'],
            'bpjsHealthAllowance' => $income['bpjsHealthAllowance'],
            'clinicTurnoverBonus' => $income['clinicTurnoverBonus'],

            'absentAmount' => $expense['absent']['amount'],
            'absentUnitNominal' => $expense['absent']['unitNominal'],
            'absentTotal' => $expense['absent']['total'],

            'notWearingAttributeAmount' => $expense['notWearingAttribute']['amount'],
            'notWearingAttributeUnitNominal' => $expense['notWearingAttribute']['unitNominal'],
            'notWearingAttributeTotal' => $expense['notWearingAttribute']['total'],

            'lateAmount' => $expense['late']['amount'],
            'lateUnitNominal' => $expense['late']['unitNominal'],
            'lateTotal' => $expense['late']['total'],

            'currentMonthCashAdvance' => $expense['currentMonthCashAdvance'],
            'remainingDebtLastMonth' => $expense['remainingDebtLastMonth'],
            'stockOpnameInventory' => $expense['stockOpnameInventory'],

            'totalIncome' => $totalIncome,
            'totalDeduction' => $totalDeduction,
            'netPay' => $netPay,
            'userId' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Payroll created successfully for job title: Office Staff',
            'jobTitle' => 'Office Staff',
            'data' => $payroll
        ], 201);
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

        if ($staff->jobTitleId !== "1") {
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

        if ($staff->jobTitleId !== "4") {
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

        if ($staff->jobTitleId !== "17") {
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

        if ($staff->jobTitleId !== "18") {
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

    private function createPayrollManager(Request $request, $staff)
    {
        $validated = $request->validate([
            'payrollDate' => 'required|date',
            'startDate' => 'required|date',
            'endDate' => 'required|date',
            'locationId' => 'required|exists:location,id',
            'income' => 'required|array',
            'expense' => 'required|array',
        ]);

        $input = $request->all();
        $income = $request->income;
        $expense = $request->expense;

        $totalIncome =
            ($income['basicIncome'] ?? 0) +
            ($income['annualIncrementIncentive'] ?? 0) +
            ($income['attendanceAllowance'] ?? 0) +
            ($income['entertainAllowance'] ?? 0) +
            ($income['transportAllowance'] ?? 0) +
            ($income['functionalLeaderAllowance'] ?? 0) +
            ($income['hardshipAllowance'] ?? 0) +
            ($income['familyAllowance'] ?? 0) +
            ($income['bpjsHealthAllowance'] ?? 0) +
            ($income['clinicTurnoverBonus'] ?? 0);

        $totalDeduction =
            ($expense['absent']['total'] ?? 0) +
            ($expense['notWearingAttribute']['total'] ?? 0) +
            ($expense['late']['total'] ?? 0) +
            ($expense['currentMonthCashAdvance'] ?? 0) +
            ($expense['remainingDebtLastMonth'] ?? 0) +
            ($expense['stockOpnameInventory'] ?? 0);

        $payroll = StaffPayroll::create([
            'staffId' => $input['staffId'],
            'name' => $input['name'],
            'locationId' => $input['locationId'],
            'payrollDate' => $input['payrollDate'],
            'startDate' => $input['startDate'],
            'endDate' => $input['endDate'],

            'basicIncome' => $income['basicIncome'] ?? 0,
            'annualIncrementIncentive' => $income['annualIncrementIncentive'] ?? 0,
            'attendanceAllowance' => $income['attendanceAllowance'] ?? 0,
            'entertainAllowance' => $income['entertainAllowance'] ?? 0,
            'transportAllowance' => $income['transportAllowance'] ?? 0,
            'functionalLeaderAllowance' => $income['functionalLeaderAllowance'] ?? 0,
            'hardshipAllowance' => $income['hardshipAllowance'] ?? 0,
            'familyAllowance' => $income['familyAllowance'] ?? 0,
            'bpjsHealthAllowance' => $income['bpjsHealthAllowance'] ?? 0,
            'clinicTurnoverBonus' => $income['clinicTurnoverBonus'] ?? 0,

            'absentAmount' => $expense['absent']['amount'] ?? 0,
            'absentUnitNominal' => $expense['absent']['unitNominal'] ?? 0,
            'absentTotal' => $expense['absent']['total'] ?? 0,
            'notWearingAttributeAmount' => $expense['notWearingAttribute']['amount'] ?? 0,
            'notWearingAttributeUnitNominal' => $expense['notWearingAttribute']['unitNominal'] ?? 0,
            'notWearingAttributeTotal' => $expense['notWearingAttribute']['total'] ?? 0,
            'lateAmount' => $expense['late']['amount'] ?? 0,
            'lateUnitNominal' => $expense['late']['unitNominal'] ?? 0,
            'lateTotal' => $expense['late']['total'] ?? 0,
            'currentMonthCashAdvance' => $expense['currentMonthCashAdvance'] ?? 0,
            'remainingDebtLastMonth' => $expense['remainingDebtLastMonth'] ?? 0,
            'stockOpnameInventory' => $expense['stockOpnameInventory'] ?? 0,

            'totalIncome' => $totalIncome,
            'totalDeduction' => $totalDeduction,
            'netPay' => $totalIncome - $totalDeduction,
            'userId' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Payroll created successfully for job title: Manager',
            'jobTitle' => 'Manager',
            'data' => $payroll
        ]);
    }

    public function detail(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:staff_payroll,id',
        ]);

        $payroll = StaffPayroll::with('user.jobTitle', 'location')
            ->find($request->id);

        if (!$payroll) {
            return response()->json([
                'message' => 'Payroll data not found.'
            ], 404);
        }

        return response()->json([
            'message' => 'Payroll detail retrieved successfully.',
            'data' => [
                'id' => $payroll->id,
                'staffId' => $payroll->staffId,
                'name' => $payroll->name,
                'jobTitleId' => $payroll->user->jobTitle->id ?? null,
                'jobTitleName' => $payroll->user->jobTitle->jobName ?? null,
                'locationId' => $payroll->location->id ?? null,
                'locationName' => $payroll->location->locationName ?? null,
                'payrollDate' => $payroll->payrollDate,
                'startDate' => $payroll->startDate,
                'endDate' => $payroll->endDate,

                'income' => [
                    'basicIncome' => $payroll->basicIncome,
                    'annualIncrementIncentive' => $payroll->annualIncrementIncentive,
                    'attendanceAllowance' => $payroll->attendanceAllowance,
                    'mealAllowance' => $payroll->mealAllowance,
                    'entertainAllowance' => $payroll->entertainAllowance ?? $payroll->entertainmentAllowance,
                    'transportAllowance' => $payroll->transportAllowance ?? $payroll->transportationAllowance,
                    'positionalAllowance' => $payroll->positionalAllowance,
                    'functionalLeaderAllowance' => $payroll->functionalLeaderAllowance,
                    'hardshipAllowance' => $payroll->hardshipAllowance,
                    'familyAllowance' => $payroll->familyAllowance,
                    'housingAllowance' => $payroll->housingAllowance,
                    'bpjsHealthAllowance' => $payroll->bpjsHealthAllowance,
                    'clinicTurnoverBonus' => $payroll->clinicTurnoverBonus,
                    'turnoverAchievementBonus' => $payroll->turnoverAchievementBonus,
                    'bonusGroomingAchievement' => $payroll->bonusGroomingAchievement,
                    'bonusSalesAchievement' => $payroll->bonusSalesAchievement,

                    'labXrayIncentive' => [
                        'amount' => $payroll->labXrayIncentiveAmount,
                        'unitNominal' => $payroll->labXrayIncentiveUnitNominal,
                        'total' => $payroll->labXrayIncentiveTotal,
                    ],
                    'groomingIncentive' => [
                        'amount' => $payroll->groomingIncentiveAmount,
                        'unitNominal' => $payroll->groomingIncentiveUnitNominal,
                        'total' => $payroll->groomingIncentiveTotal,
                    ],
                    'replacementDays' => [
                        'amount' => $payroll->replacementDaysAmount,
                        'unitNominal' => $payroll->replacementDaysUnitNominal,
                        'total' => $payroll->replacementDaysTotal,
                    ],
                    'longShiftReplacement' => [
                        'amount' => $payroll->longShiftReplacementAmount,
                        'unitNominal' => $payroll->longShiftReplacementUnitNominal,
                        'total' => $payroll->longShiftReplacementTotal,
                    ],
                    'fullShiftReplacement' => [
                        'amount' => $payroll->fullShiftReplacementAmount,
                        'unitNominal' => $payroll->fullShiftReplacementUnitNominal,
                        'total' => $payroll->fullShiftReplacementTotal,
                    ],
                    'patientIncentive' => [
                        'amount' => $payroll->patientIncentiveAmount,
                        'unitNominal' => $payroll->patientIncentiveUnitNominal,
                        'total' => $payroll->patientIncentiveTotal,
                    ],
                ],

                'expense' => [
                    'absent' => [
                        'amount' => $payroll->absentAmount,
                        'unitNominal' => $payroll->absentUnitNominal,
                        'total' => $payroll->absentTotal,
                    ],
                    'notWearingAttribute' => [
                        'amount' => $payroll->notWearingAttributeAmount,
                        'unitNominal' => $payroll->notWearingAttributeUnitNominal,
                        'total' => $payroll->notWearingAttributeTotal,
                    ],
                    'late' => [
                        'amount' => $payroll->lateAmount,
                        'unitNominal' => $payroll->lateUnitNominal,
                        'total' => $payroll->lateTotal,
                    ],
                    'currentMonthCashAdvance' => $payroll->currentMonthCashAdvance,
                    'remainingDebtLastMonth' => $payroll->remainingDebtLastMonth,
                    'stockOpnameInventory' => $payroll->stockOpnameInventory,
                    'stockOpnameLost' => $payroll->stockOpnameLost,
                    'stockOpnameExpired' => $payroll->stockOpnameExpired,
                ],

                'totalIncome' => $payroll->totalIncome,
                'totalDeduction' => $payroll->totalDeduction,
                'netPay' => $payroll->netPay,
            ]
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:staff_payroll,id',
            'staffId' => 'required|integer',
            'name' => 'required|string',
            'locationId' => 'required|integer',
            'payrollDate' => 'required|date',
            'startDate' => 'required|date',
            'endDate' => 'required|date',
            'income' => 'required|array',
            'expense' => 'required|array',
        ]);

        $payroll = StaffPayroll::findOrFail($request->id);

        $income = $request->input('income');
        $expense = $request->input('expense');

        $numericIncomeFields = [
            'basicIncome',
            'annualIncrementIncentive',
            'attendanceAllowance',
            'mealAllowance',
            'positionalAllowance',
            'entertainAllowance',
            'transportAllowance',
            'housingAllowance',
            'turnoverAchievementBonus',
            'bpjsHealthAllowance',
            'bonusGroomingAchievement',
            'bonusSalesAchievement',
            'clinicTurnoverBonus',
            'functionalLeaderAllowance',
            'hardshipAllowance',
            'familyAllowance'
        ];

        foreach ($numericIncomeFields as $field) {
            $income[$field] = $income[$field] ?? 0;
        }

        $structuredIncome = ['labXrayIncentive', 'groomingIncentive', 'replacementDays', 'longShiftReplacement', 'fullShiftReplacement', 'patientIncentive'];
        foreach ($structuredIncome as $field) {
            $income[$field] = $income[$field] ?? ['amount' => 0, 'unitNominal' => 0, 'total' => 0];
        }

        $structuredExpense = ['absent', 'late', 'notWearingAttribute'];
        foreach ($structuredExpense as $field) {
            $expense[$field] = $expense[$field] ?? ['amount' => 0, 'unitNominal' => 0, 'total' => 0];
        }

        $flatExpense = ['currentMonthCashAdvance', 'remainingDebtLastMonth', 'stockOpnameInventory', 'stockOpnameLost', 'stockOpnameExpired'];
        foreach ($flatExpense as $field) {
            $expense[$field] = $expense[$field] ?? 0;
        }

        $totalIncome = array_sum(array_intersect_key($income, array_flip($numericIncomeFields))) +
            $income['labXrayIncentive']['total'] +
            $income['groomingIncentive']['total'] +
            $income['replacementDays']['total'] +
            $income['longShiftReplacement']['total'] +
            $income['fullShiftReplacement']['total'] +
            $income['patientIncentive']['total'];

        $totalDeduction =
            $expense['absent']['total'] +
            $expense['late']['total'] +
            $expense['notWearingAttribute']['total'] +
            $expense['currentMonthCashAdvance'] +
            $expense['remainingDebtLastMonth'] +
            $expense['stockOpnameInventory'] +
            $expense['stockOpnameLost'] +
            $expense['stockOpnameExpired'];

        $netPay = $totalIncome - $totalDeduction;

        $payroll->update([
            'staffId' => $request->staffId,
            'name' => $request->name,
            'locationId' => $request->locationId,
            'payrollDate' => $request->payrollDate,
            'startDate' => $request->startDate,
            'endDate' => $request->endDate,

            // Income
            'basicIncome' => $income['basicIncome'],
            'annualIncrementIncentive' => $income['annualIncrementIncentive'],
            'attendanceAllowance' => $income['attendanceAllowance'],
            'mealAllowance' => $income['mealAllowance'],
            'positionalAllowance' => $income['positionalAllowance'],
            'entertainAllowance' => $income['entertainAllowance'],
            'transportAllowance' => $income['transportAllowance'],
            'housingAllowance' => $income['housingAllowance'],
            'turnoverAchievementBonus' => $income['turnoverAchievementBonus'],
            'bpjsHealthAllowance' => $income['bpjsHealthAllowance'],
            'bonusGroomingAchievement' => $income['bonusGroomingAchievement'],
            'bonusSalesAchievement' => $income['bonusSalesAchievement'],
            'clinicTurnoverBonus' => $income['clinicTurnoverBonus'],
            'functionalLeaderAllowance' => $income['functionalLeaderAllowance'],
            'hardshipAllowance' => $income['hardshipAllowance'],
            'familyAllowance' => $income['familyAllowance'],

            // Structured Income
            'labXrayIncentiveAmount' => $income['labXrayIncentive']['amount'],
            'labXrayIncentiveUnitNominal' => $income['labXrayIncentive']['unitNominal'],
            'labXrayIncentiveTotal' => $income['labXrayIncentive']['total'],

            'groomingIncentiveAmount' => $income['groomingIncentive']['amount'],
            'groomingIncentiveUnitNominal' => $income['groomingIncentive']['unitNominal'],
            'groomingIncentiveTotal' => $income['groomingIncentive']['total'],

            'replacementDaysAmount' => $income['replacementDays']['amount'],
            'replacementDaysUnitNominal' => $income['replacementDays']['unitNominal'],
            'replacementDaysTotal' => $income['replacementDays']['total'],

            'longShiftReplacementAmount' => $income['longShiftReplacement']['amount'],
            'longShiftReplacementUnitNominal' => $income['longShiftReplacement']['unitNominal'],
            'longShiftReplacementTotal' => $income['longShiftReplacement']['total'],

            'fullShiftReplacementAmount' => $income['fullShiftReplacement']['amount'],
            'fullShiftReplacementUnitNominal' => $income['fullShiftReplacement']['unitNominal'],
            'fullShiftReplacementTotal' => $income['fullShiftReplacement']['total'],

            'patientIncentiveAmount' => $income['patientIncentive']['amount'],
            'patientIncentiveUnitNominal' => $income['patientIncentive']['unitNominal'],
            'patientIncentiveTotal' => $income['patientIncentive']['total'],

            // Expense
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
            'message' => 'Payroll updated successfully',
            'data' => $payroll
        ], 200);
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
        $sheet->setCellValue('B1', 'Nama');
        $sheet->setCellValue('C1', 'Tanggal Penggajian');
        $sheet->setCellValue('D1', 'Cabang');
        $sheet->setCellValue('E1', 'Penghasilan Pokok');
        $sheet->setCellValue('F1', 'Insentif Kenaikan Tahunan');
        $sheet->setCellValue('G1', 'Tidak Masuk Kerja');
        $sheet->setCellValue('H1', 'Keterlambatan');
        $sheet->setCellValue('I1', 'Total Penghasilan');
        $sheet->setCellValue('J1', 'Total Pengurangan');
        $sheet->setCellValue('K1', 'Penerimaan Bersih');

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


    public function generatePayrollSlip(Request $request)
    {
        setlocale(LC_TIME, 'id_ID');
        Carbon::setLocale('id');
        ini_set('memory_limit', '256M');
        set_time_limit(120);

        $request->validate([
            'id' => 'required|integer|exists:staff_payroll,id'
        ]);

        try {

            $payroll = StaffPayroll::select([
                'id',
                'staffId',
                'payrollDate',
                'totalIncome',
                'totalDeduction',
                'netPay',
                'userId',
                'basicIncome',
                'annualIncrementIncentive',
                'attendanceAllowance',
                'mealAllowance',
                'entertainAllowance',
                'transportAllowance',
                'positionalAllowance',
                'functionalLeaderAllowance',
                'hardshipAllowance',
                'familyAllowance',
                'bpjsHealthAllowance',
                'clinicTurnoverBonus',
                'turnoverAchievementBonus',
                'bonusGroomingAchievement',
                'bonusSalesAchievement',
                'replacementDaysTotal',
                'longShiftReplacementTotal',
                'fullShiftReplacementTotal',
                'patientIncentiveTotal',
                'labXrayIncentiveTotal',
                'groomingIncentiveTotal',
                'absentTotal',
                'notWearingAttributeTotal',
                'lateTotal',
                'currentMonthCashAdvance',
                'remainingDebtLastMonth',
                'stockOpnameInventory',
                'stockOpnameLost',
                'stockOpnameExpired'
            ])->with(['staff' => function ($query) {
                $query->select('id', 'firstName', 'registrationNo', 'startDate', 'jobTitleId')
                    ->with(['jobTitle' => function ($q) {
                        $q->select('id', 'jobName');
                    }]);
            }])->findOrFail($request->id);

            $staff = $payroll->staff;

            $creator = User::select('id', 'firstName')
                ->findOrFail($payroll->userId);

            $payrollDate = Carbon::parse($payroll->payrollDate);
            $period = $payrollDate->translatedFormat('F Y');
            $slipDate = $payrollDate->translatedFormat('d F Y');

            $incomeFields = $this->buildIncomeFields($payroll);
            $expenseFields = $this->buildExpenseFields($payroll);

            $pdf = Pdf::loadView('payroll.salary-slip', [
                'payroll' => $payroll,
                'user' => $staff,
                'userId' => $creator,
                'period' => $period,
                'slipDate' => $slipDate,
                'incomeFields' => $incomeFields,
                'expenseFields' => $expenseFields,
            ])
                ->setPaper('A4');
            // ->setOptions([
            //     'isHtml5ParserEnabled' => true,
            //     'isPhpEnabled' => true,
            //     'defaultFont' => 'Arial',
            //     'dpi' => 96,
            //     'defaultPaperSize' => 'A4',
            //     'isRemoteEnabled' => true,
            // ]);

            $filename = 'Slip_Gaji_' . str_replace(' ', '_', $staff->fullname) . '_' . str_replace(' ', '_', $period) . '.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            \Log::error('Error generating payroll slip: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal generate slip gaji: ' . $e->getMessage()], 500);
        }
    }

    private function buildIncomeFields($payroll)
    {
        $incomeKeys = [
            'basicIncome' => 'Penghasilan Pokok',
            'annualIncrementIncentive' => 'Insentif Kenaikan Tahunan',
            'attendanceAllowance' => 'Tunjangan Kehadiran',
            'mealAllowance' => 'Tunjangan Makan',
            'entertainAllowance' => 'Tunjangan Entertain',
            'transportAllowance' => 'Tunjangan Transportasi',
            'transportationAllowance' => 'Tunjangan Transportasi',
            'positionalAllowance' => 'Tunjangan Jabatan',
            'functionalLeaderAllowance' => 'Tunjangan Fungsional Leader',
            'hardshipAllowance' => 'Tunjangan Hardship',
            'familyAllowance' => 'Tunjangan Keluarga',
            'bpjsHealthAllowance' => 'Tunjangan BPJS Kesehatan',
            'clinicTurnoverBonus' => 'Bonus Omset Klinik',
            'turnoverAchievementBonus' => 'Bonus Omset',
            'bonusGroomingAchievement' => 'Bonus Pencapaian Grooming',
            'bonusSalesAchievement' => 'Bonus Penjualan Barang',
            'replacementDaysTotal' => 'Upah Pengganti Hari',
            'longShiftReplacementTotal' => 'Upah Longshift',
            'fullShiftReplacementTotal' => 'Upah Fullshift',
            'patientIncentiveTotal' => 'Insentif Pasien',
            'labXrayIncentiveTotal' => 'Insentif Lab/Xray',
            'groomingIncentiveTotal' => 'Insentif Grooming',
        ];

        $incomeFields = [];
        foreach ($incomeKeys as $key => $label) {
            $value = $payroll->$key ?? 0;
            if ($value > 0) {
                $incomeFields[$label] = $value;
            }
        }

        return $incomeFields;
    }

    private function buildExpenseFields($payroll)
    {
        $expenseKeys = [
            'absentTotal' => 'Tidak Masuk Kerja',
            'notWearingAttributeTotal' => 'Tidak Mengenakan Atribut',
            'lateTotal' => 'Keterlambatan',
            'currentMonthCashAdvance' => 'Kasbon Bulan Berjalan',
            'remainingDebtLastMonth' => 'Sisa Hutang Bulan Lalu',
            'stockOpnameInventory' => 'Stock Opname Inventory',
            'stockOpnameLost' => 'Stock Opname Hilang',
            'stockOpnameExpired' => 'Stock Opname Expired',
        ];

        $expenseFields = [];
        foreach ($expenseKeys as $key => $label) {
            $value = $payroll->$key ?? 0;
            if ($value > 0) {
                $expenseFields[$label] = $value;
            }
        }

        return $expenseFields;
    }
}
