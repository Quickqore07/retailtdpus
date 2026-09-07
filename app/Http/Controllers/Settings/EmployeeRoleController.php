<?php

namespace App\Http\Controllers\Settings;

use App\Models\Settings\EmployeeRoles;
use App\Models\Settings\EmployeeSubRoles;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Models\Payroll\EmployeeHours;
use App\Models\Payroll\EmployeeRates;
use App\Services\WeeklySummaryRecalculationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeRoleController extends Controller
{
    public function search()
    {
        $search = request('query');
        $column = request('column') ?? 'name';
        return to_json([
            'collection' => EmployeeRoles::when($search, function ($query) use ($column, $search) {
                return $query->where($column, 'like', '%' . $search . '%')->orWhere('code', 'like', '%' . $search . '%');
            })->selectRaw("id, CONCAT(code, ' - ', name) as name, code, active") ->authorizedWorkgroup()->get()
        ]);
    }

    public function index()
    {
        $employeeRoles = EmployeeRoles::authorizedWorkgroup()->filter();
        return to_json([
            'collection' => $employeeRoles,
        ]);
    }

    public function create()
    {
        $item = [
            'name' => '',
            'code' => '',
            'active' => true,
            'tipped' => false,
        ];

        return to_json([
            'form' => $item,
        ]);
    }

    public function store(Request $request)
    {
        $workgroup_id = $request->session()->get('workgroup');
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('employee_roles')->where(function ($query) use ($workgroup_id) {
                    return $query->where('workgroup_id', $workgroup_id);
                }),
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('employee_roles')->where(function ($query) use ($workgroup_id) {
                    return $query->where('workgroup_id', $workgroup_id);
                }),
            ],
            'active' => 'nullable|boolean',
            'tipped' => 'nullable|boolean'
        ]);

        if (!$workgroup_id) {
            return to_json([
                'success' => false,
                'saved' => false,
                'message' => 'Workgroup is required'
            ]);
        }
        $item = new EmployeeRoles;
        $item->name = $request->name;
        $item->code = $request->code;
        $item->active = $request->active ?? true;
        $item->tipped = $request->tipped ?? false;
        $item->workgroup_id = $workgroup_id;
        $subroles = [];
        foreach ($request->sub_roles as $subRole) {
            $subroles[] = [
                'code' => $subRole,
                'role_id' => $item->id,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ];
        }
        
        $roleCods = array_map(function ($subRole) {
            return $subRole['code'];
        }, $subroles);

        if(in_array($item->code, $roleCods)){
            return to_json([
                'saved' => false,
                'message' => 'You cannot add the same code in sub roles.',
            ], 500);
        }


        $item->save();


        


        $nonDeletableRoleIds = [];
        $roleCodeIds = [];
        $roleCodeIds = EmployeeRoles::whereIn('code', $roleCods)->with('subRoles')->get();
         foreach ($roleCodeIds as $roleCodeId) {
            if(count($roleCodeId->subRoles)!=0){
                $nonDeletableRoleIds[] = $roleCodeId->id;
            }else{
                $roleCodeIds[] = $roleCodeId->id;
            }
         }
         if(count($nonDeletableRoleIds)!=0){
            return to_json([
                'saved' => false,
                'message' => 'Employee role has sub roles, cannot add. ' . implode(', ', $nonDeletableRoleIds),
            ], 500);
        }
        EmployeeHours::whereIn('role_id', $roleCodeIds)->update(['role_id' => $item->id]);
        EmployeeRates::whereIn('role_id', $roleCodeIds)->update(['role_id' => $item->id]);
        EmployeeRoles::whereIn('id', $roleCodeIds)->delete();
        
        WeeklySummaryRecalculationService::recalculateForEmployee(['role_id' => $item->id]);
        EmployeeSubRoles::insert($subroles);   
        return to_json([
            'saved' => true,
            'id' => $item->id,
            'message' => 'Employee role created successfully.'
        ]);
    }

    public function show($id)
    {
        $item = EmployeeRoles::with('subRoles')->authorizedWorkgroup()->findOrFail($id);
        return to_json([
            'model' => $item
        ]);
    }

    public function edit($id)
    {
        $item = EmployeeRoles::with('subRoles')->authorizedWorkgroup()->findOrFail($id);
        return to_json([
            'form' => $item
        ]);
    }

    public function update($id, Request $request)
    {
        $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('employee_roles')->ignore($id),
            ],
            'active' => 'nullable|boolean'
        ]);
        DB::beginTransaction();
        try {

            $item = EmployeeRoles::authorizedWorkgroup()->findOrFail($id);
            $item->name = $request->name;
            $item->code = $request->code;
            $item->active = $request->active ? 1 : 0;
            $item->tipped = $request->tipped ?? false;
            $item->save();

            EmployeeSubRoles::where('role_id', $id)->delete();
            $subroles = [];
            foreach ($request->sub_roles as $subRole) {
                $subroles[] = [
                    'code' => $subRole,
                    'role_id' => $id,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ];
            }


            $roleCods = array_map(function ($subRole) {
                return $subRole['code'];
            }, $subroles);

            if(in_array($item->code, $roleCods)){
                return to_json([
                    'saved' => false,
                    'message' => 'You cannot add the same code in sub roles.',
                ], 500);
            }


            $nonDeletableRoleIds = [];
            $roleCodeIds = [];
            $roleData = EmployeeRoles::whereIn('code', array_filter($roleCods))->with('subRoles')->get();
            foreach ($roleData as $role) {
                if(count($role->subRoles)!=0){
                    $nonDeletableRoleIds[] = $role->id;
                }else{
                    $roleCodeIds[] = $role->id;
                }
            }
            if(count($nonDeletableRoleIds)!=0){
                return to_json([
                    'saved' => false,
                    'message' => 'Employee role has sub roles, cannot add. ' . implode(', ', $nonDeletableRoleIds),
                ], 500);
            }

            EmployeeHours::whereIn('role_id', $roleCodeIds)->update(['role_id' => $id]);
            EmployeeRates::whereIn('role_id', $roleCodeIds)->update(['role_id' => $id]);
            EmployeeRoles::whereIn('id', $roleCodeIds)->delete();
            WeeklySummaryRecalculationService::recalculateForEmployee(['role_id' => $id]);


            EmployeeSubRoles::insert($subroles);
            DB::commit();
            return to_json([
                'saved' => true,
                'id' => $item->id,
                    'message' => 'Employee role updated successfully.'
            ]);
        } catch (\Exception $e) {
            dd($e);
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => 'Employee role update failed.',
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $item = EmployeeRoles::authorizedWorkgroup()->findOrFail($id);

            if(EmployeeHours::where('role_id', $id)->count() > 0 || EmployeeRates::where('role_id', $id)->count() > 0){
                return to_json([
                    'deleted' => false,
                    'message' => 'Employee role has hours records or rates records, cannot delete.',
                ], 500);
            }
            EmployeeSubRoles::where('role_id', $id)->delete();
            $item->delete();
            DB::commit();
            return to_json([
                'deleted' => true,
                'message' => 'Employee role deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'deleted' => false,
                'message' => 'Employee role deletion failed.',
            ], 500);
        }
    }
}

