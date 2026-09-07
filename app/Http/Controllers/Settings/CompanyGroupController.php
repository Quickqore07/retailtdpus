<?php

namespace App\Http\Controllers\Settings;

use App\Models\Settings\CompanyGroup;
use App\Models\Settings\Company;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CompanyGroupController extends Controller
{
    public function index()
    {
        $this->authorize('access', 'company-group.index');
        $companyGroups = CompanyGroup::select('id', 'name', 'description', 'active','companies','created_at','updated_at')->filter();
        foreach($companyGroups as $companyGroup){
            if($companyGroup->companies){
                $companyGroup->company_count = count($companyGroup->company_array);
            }
            else{
                $companyGroup->company_count = 0;
            }
            unset($companyGroup->companies);
        }

        return to_json([
            'collection' => $companyGroups,
        ]);
    }

    public function create()
    {
        $this->authorize('access', 'company-group.create');
        $item = [
            'name' => '',
            'description' => '',
            'active' => true,
        ];

        return to_json([
            'form' => $item,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'company-group.create');
        
        $request->validate([
            'name' => 'required|string|max:255|unique:company_groups,name',
            'description' => 'nullable|string',
            'active' => 'boolean',
            'companies' => 'nullable|array',
            'companies.*' => 'exists:company,id'
        ]);
        
        DB::beginTransaction();
        try {
            $companyGroup = CompanyGroup::create([
                'name' => $request->name,
                'description' => $request->description,
                'active' => $request->active ?? true,
            ]);
            $companyGroup->companies = $request->companies ? array_column($request->companies, 'id') : [];
            $companyGroup->save();
            DB::commit();
            
            return to_json([
                'saved' => true,
                'id' => $companyGroup->id,
                'message' => 'Company group created successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => 'Company group creation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $this->authorize('access', 'company-group.show');
        $item = CompanyGroup::findOrFail($id);
        $item->companies_details = $item->companiesDetails();

        $item->companies_by_workgroup = $item->companiesDetails()->groupBy('workgroup_id')->map(function ($group) {
            return [
                'workgroup_id' => $group->first()->workgroup_id,
                'workgroup_name' => $group->first()->workgroup?->name,
                'companies_array' => $group->map(function ($company) {
                    return ['id' => $company->id, 'name' => $company->name];
                    })->values()->all(),
                ];
            })->values()->all();
        return to_json([
            'model' => $item
        ]);
    }

    public function edit($id)
    {
        $this->authorize('access', 'company-group.update');
        $item = CompanyGroup::findOrFail($id);
        $item->companies_details = $item->companiesDetails();
        return to_json([
            'form' => $item
        ]);
    }

    public function update($id, Request $request)
    {
        $this->authorize('access', 'company-group.update');

        $request->validate([
            'name' => 'required|string|max:255|unique:company_groups,name,' . $id,
            'description' => 'nullable|string',
            'active' => 'boolean',
            'companies' => 'nullable|array',
            'companies.*' => 'exists:company,id'
        ]);

        $companyGroup = CompanyGroup::findOrFail($id);

        DB::beginTransaction();
        try {
            $companyGroup->update([
                'name' => $request->name,
                'description' => $request->description,
                'active' => $request->active ?? true,
            ]);

            $companyGroup->companies = $request->companies ? array_column($request->companies, 'id') : [];
            $companyGroup->save();
            DB::commit();

            return to_json([
                'saved' => true,
                'id' => $companyGroup->id,
                'message' => 'Company group updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => 'Company group update failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $this->authorize('access', 'company-group.delete');
        $companyGroup = CompanyGroup::findOrFail($id);

        DB::beginTransaction();
        try {
            
            $companyGroup->delete();
            
            DB::commit();

            return to_json([
                'deleted' => true,
                'message' => 'Company group deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'deleted' => false,
                'message' => 'Company group deletion failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function search()
    {
        $search = is_array(request('query')) ? null : request('query');
        $column = request('column') ?? 'name';
        
        $companyGroups = CompanyGroup::where(function ($query) use ($column, $search) {
                if($search && is_string($search) && is_string($column)){
                    return $query->where($column, 'like', '%' . $search . '%');
                }
                return $query;
            })
            ->select('id', 'name')
            ->get();
            
        return to_json([
            'collection' => $companyGroups
        ]);
    }

    /**
     * Get companies grouped by company groups for dropdown display
     */
    public function getCompaniesByGroups()
    {
        $groups = CompanyGroup::active()->get();

        $result = [];
        
        foreach ($groups as $group) {
            if ($group->companies->count() > 0 && count($group->companies) > 0) {
                $result[] = [
                    'group_id' => $group->id,
                    'group_name' => $group->name,
                    'companies' => $group->companies
                ];
            }
        }

        return to_json([
            'collection' => $result
        ]);
    }
}