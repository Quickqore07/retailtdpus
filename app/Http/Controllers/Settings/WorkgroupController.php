<?php

namespace App\Http\Controllers\Settings;

use App\Models\Settings\Workgroup;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WorkgroupController extends Controller
{
    public function index()
    {
        $this->authorize('access', 'workgroup.index');
        $workgroups = Workgroup::filter();

        return to_json([
            'collection' => $workgroups,
        ]);
    }

    public function create()
    {
        $this->authorize('access', 'workgroup.create');
        $item = [
            'name' => '',
            'active' => true,
        ];

        return to_json([
            'form' => $item,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'workgroup.create');

        $request->validate([
            'name' => 'required|string|max:255|unique:workgroup,name',
            'active' => 'boolean',
        ]);

        $item = Workgroup::create([
            'name' => $request->name,
            'active' => $request->active ?? true,
        ]);

        return to_json([
            'saved' => true,
            'id' => $item->id,
            'message' => 'Workgroup created successfully',
        ]);
    }

    public function show($id)
    {
        $this->authorize('access', 'workgroup.show');
        $item = Workgroup::findOrFail($id);

        return to_json([
            'model' => $item
        ]);
    }

    public function edit($id)
    {
        $this->authorize('access', 'workgroup.edit');   
        $item = Workgroup::findOrFail($id);

        return to_json([
            'form' => $item
        ]);
    }

    public function update($id, Request $request)
    {
        $this->authorize('access', 'workgroup.update');

        $request->validate([
            'name' => 'required|string|max:255|unique:workgroup,name,' . $id,
            'active' => 'boolean',
        ]);

        $item = Workgroup::findOrFail($id);
        $item->name = $request->name;
        $item->active = $request->active ?? true;
        $item->save();

        return to_json([
            'saved' => true,
            'id' => $item->id,
            'message' => 'Workgroup updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $this->authorize('access', 'workgroup.delete');
        $item = Workgroup::findOrFail($id);
        
        // Check if workgroup has companies
        if ($item->companies()->count() > 0) {
            return to_json([
                'deleted' => false,
                'message' => 'Cannot delete workgroup with associated companies'
            ]);
        }

        $item->delete();

        return to_json([
            'deleted' => true,
            'message' => 'Workgroup deleted successfully'
        ]);
    }

    public function search()
    {
        $user = Auth::user();
        $search = request('query') ?? '';
        $column = request('column') ?? 'name';
        $internal = request('internal') ?? false;
        $all = request('all') ?? false;

        $workgroups = Workgroup::leftJoin('company', 'workgroup.id', '=', 'company.workgroup_id')->when($search, function ($query) use ($column, $search) {
            return $query->where('workgroup.name', 'like', '%' . $search . '%');
        })
        ->when($internal, function ($query) {
            return $query->where('workgroup.only_upload', 0);
        })
        ->where(function ($query) use ($user) {
            if(strtolower($user->role->name) == 'admin' || strtolower($user->role->name) == 'superadmin'){
                return $query;
            }
            else{
                return $query->authorizedCompanies('company.id',false);
            }
        })
        ->where('workgroup.active', true)
        ->select('workgroup.id', 'workgroup.name', DB::raw('COUNT(company.id) as company_count'))->groupBy('workgroup.id', 'workgroup.name')->orderBy('workgroup.name')
        ->when(!$all, function ($query) {
            return $query->onlyRegular('workgroup.only_upload');
        })
        ->get();
        return to_json([
            'collection' => $workgroups
        ]);
    }
    public function getWorkgroups()
    {
        $workgroups = Workgroup::all();
        return to_json([
            'collection' => $workgroups
        ]);
    }
}

