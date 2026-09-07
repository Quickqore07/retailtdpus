<?php

namespace App\Http\Controllers\Upload;

use App\Models\Settings\Workgroup;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UploadWorkgroupController extends Controller
{
    public function index()
    {
        $this->authorize('access', 'upload-workgroup.index');
        $workgroups = Workgroup::onlyUpload()->filter();

        return to_json([
            'collection' => $workgroups,
        ]);
    }

    public function create()
    {
        $this->authorize('access', 'upload-workgroup.create');
        $item = [
            'name' => '',
            'active' => true,
            'only_upload' => true,
        ];

        return to_json([
            'form' => $item,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'upload-workgroup.create');

        $request->validate([
            'name' => 'required|string|max:255|unique:workgroup,name',
            'active' => 'boolean',
        ]);

        $item = Workgroup::create([
            'name' => $request->name,
            'active' => $request->active ?? true,
            'only_upload' => true,
        ]);

        return to_json([
            'saved' => true,
            'id' => $item->id,
            'message' => 'Upload Workgroup created successfully',
        ]);
    }

    public function show($id)
    {
        $this->authorize('access', 'upload-workgroup.show');
        $item = Workgroup::onlyUpload()->findOrFail($id);

        return to_json([
            'model' => $item
        ]);
    }

    public function edit($id)
    {
        $this->authorize('access', 'upload-workgroup.update');
        $item = Workgroup::onlyUpload()->findOrFail($id);

        return to_json([
            'form' => $item
        ]);
    }

    public function update($id, Request $request)
    {
        $this->authorize('access', 'upload-workgroup.update');

        $request->validate([
            'name' => 'required|string|max:255|unique:workgroup,name,' . $id,
            'active' => 'boolean',
        ]);

        $item = Workgroup::onlyUpload()->findOrFail($id);
        $item->name = $request->name;
        $item->active = $request->active ?? true;
        $item->only_upload = true;
        $item->save();

        return to_json([
            'saved' => true,
            'id' => $item->id,
            'message' => 'Upload Workgroup updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $this->authorize('access', 'upload-workgroup.delete');
        $item = Workgroup::onlyUpload()->findOrFail($id);
        
        if ($item->companies()->where('only_upload', true)->count() > 0) {
            return to_json([
                'deleted' => false,
                'message' => 'Cannot delete workgroup with associated companies'
            ]);
        }

        $item->delete();

        return to_json([
            'deleted' => true,
            'message' => 'Upload Workgroup deleted successfully'
        ]);
    }

    public function search()
    {
        $search = request('query');
        $user = Auth::guard('upload-portal')->user();
        info( 'upload-portal user: ' . $user);
        if(!$user){
            $user = Auth::user();
        }
        info( 'regular user: ' . $user);
        $user = User::with('role')->find($user->id);
        $companyAccess = !isset($user->role) || (isset($user->role) && !in_array($user->role->name, ['admin', 'superadmin'])) ? $user->companiesArray : null;

        $workgroups = Workgroup::leftJoin('company', 'workgroup.id', '=', 'company.workgroup_id')
            ->when($search, function ($query) use ($search) {
                return $query->where('workgroup.name', 'like', '%' . $search . '%');
            })
            ->when($companyAccess, function ($query) use ($companyAccess) {
                return $query->whereIn('company.id', $companyAccess);
            })
            ->where('workgroup.active', true)
            ->where('workgroup.only_upload', true)
            ->select('workgroup.id', 'workgroup.name', DB::raw('COUNT(company.id) as company_count'))
            ->groupBy('workgroup.id', 'workgroup.name')
            ->orderBy('workgroup.name')
            ->get();
        info( 'regular user: ' . Auth::user());

        return to_json([
            'collection' => $workgroups
        ]);
    }

    public function searchDocumentWorkgroups()
    {
        $user = Auth::guard('upload-portal')->user();
        $user = User::with('role')->find($user->id);
        $companyAccess = !isset($user->role) || (isset($user->role) && !in_array($user->role->name, ['admin', 'superadmin'])) ? $user->companiesArray : null;

        $workgroups = Workgroup::join('company', 'workgroup.id', '=', 'company.workgroup_id')
            ->when($companyAccess, function ($query) use ($companyAccess) {
                return $query->whereIn('company.id', $companyAccess);
            })
            ->where('workgroup.active', true)
            ->select('workgroup.id', 'workgroup.name', DB::raw('COUNT(company.id) as company_count'))
            ->groupBy('workgroup.id', 'workgroup.name')
            ->orderBy('workgroup.name')
            ->get();

        return to_json([
            'collection' => $workgroups
        ]);
    }
}
