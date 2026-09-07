<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Payroll\EmployeeInactive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\Payroll\WeeklySummaryRecalculator;
class EmployeeInactiveController extends Controller
{
    public function index(Request $request)
    {
        $employeeInactive = EmployeeInactive::with('employee','company')->authorizedCompanies('company_id')->filter();
        return to_json([
            'collection' => $employeeInactive,
        ]);
    }
    public function create()
    {
        $item = [
            'employee_id' => '',
            'company_id' => '',
            'from_date' => '',
            'to_date' => '',
        ];
        return to_json([
            'form' => $item,
        ]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required',
            'company_id' => 'required',
            'from_date' => 'required',
            'to_date' => 'required',
        ]);
        DB::beginTransaction();
        try {
            $employeeInactive = EmployeeInactive::create($request->all());
            WeeklySummaryRecalculator::run([
                'employee_id' => $employeeInactive->employee_id, 
                'company_id' => $employeeInactive->company_id, 
                'from_date' => $employeeInactive->from_date, 
                'to_date' => $employeeInactive->to_date,
                'clear' => true
            ]);
            DB::commit();
            return to_json([
                'id' => $employeeInactive->id,
                'employeeInactive' => $employeeInactive,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e);
            return to_json([
                'saved' => false,
                'message' => 'Employee inactive creation failed',
            ],500);
        }
    }
    public function show($id)
    {
        $employeeInactive = EmployeeInactive::with('employee','company')->findOrFail($id);
        return to_json([
            'model' => $employeeInactive,
        ]);
    }
    public function edit($id)
    {
        $employeeInactive = EmployeeInactive::with('employee','company')->findOrFail($id);
        return to_json([
            'form' => $employeeInactive,
        ]);
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'employee_id' => 'required',
            'company_id' => 'required',
            'from_date' => 'required',
            'to_date' => 'required',
        ]);
        
        DB::beginTransaction();
        try {
            $employeeInactive = EmployeeInactive::findOrFail($id);
            $employeeInactive->update($request->all());

            WeeklySummaryRecalculator::run([
                'employee_id' => $employeeInactive->employee_id, 
                'company_id' => $employeeInactive->company_id, 
                'from_date' => $employeeInactive->from_date, 
                'to_date' => $employeeInactive->to_date,
                'clear' => true
            ]);

            DB::commit();
            return to_json([
                'id' => $employeeInactive->id,
                'employeeInactive' => $employeeInactive,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => 'Employee inactive update failed',
            ]);
        }
    }
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
        $employeeInactive = EmployeeInactive::findOrFail($id);
            $employeeInactive->delete();
            WeeklySummaryRecalculator::run([
                'employee_id' => $employeeInactive->employee_id, 
                'company_id' => $employeeInactive->company_id, 
                'from_date' => $employeeInactive->from_date, 
                'to_date' => $employeeInactive->to_date,
                'clear' => true
            ]);
            DB::commit();
            return to_json([
                'deleted' => true,
                'message' => 'Employee inactive deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'deleted' => false,
                'message' => 'Employee inactive deletion failed',
            ],500);
        }
    }
    
    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:employee_inactive,id',
        ]);
        
        DB::beginTransaction();
        try {
            EmployeeInactive::whereIn('id', $request->ids)->delete();
            foreach($request->ids as $id){
                $employeeInactive = EmployeeInactive::findOrFail($id);
                WeeklySummaryRecalculator::run([
                    'employee_id' => $employeeInactive->employee_id, 
                    'company_id' => $employeeInactive->company_id, 
                    'from_date' => $employeeInactive->from_date, 
                    'to_date' => $employeeInactive->to_date,
                    'clear' => true
                ]);
            }
            DB::commit();
            return to_json([
                'deleted' => true,
                'message' => 'Employee inactive records deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e);
            return to_json([
                'deleted' => false,
                'message' => 'Failed to delete employee inactive records',
            ],500);
        }
    }
}