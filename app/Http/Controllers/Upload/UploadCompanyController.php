<?php

namespace App\Http\Controllers\Upload;

use App\Models\Settings\Company;
use App\Models\Settings\Workgroup;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UploadCompanyController extends Controller
{
    public function index()
    {
        $this->authorize('access', 'upload-company.index');
        $companies = Company::with(['workgroup'])->onlyUpload()->filter();

        return to_json([
            'collection' => $companies,
        ]);
    }

    public function create()
    {
        $this->authorize('access', 'upload-company.create');
        $item = [
            'name' => '',
            'store_number' => '',
            'workgroup_id' => null,
            'workgroup' => null,
            'only_upload' => true,
        ];

        return to_json([
            'form' => $item,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'upload-company.create');
        
        $request->validate([
            'name' => 'required|string|max:255',
            'store_number' => 'required|string|max:255|unique:company,store_number',
            'workgroup_id' => 'required|integer|exists:workgroup,id',
        ]);
        
        $data = $request->all();
        $data['only_upload'] = true;
        
        $item = Company::create($data);

        return to_json([
            'saved' => true,
            'id' => $item->id,
            'message' => 'Upload Company created successfully',
        ]);
    }

    public function show($id)
    {
        $this->authorize('access', 'upload-company.show');
        $item = Company::with(['workgroup'])->onlyUpload()->findOrFail($id);

        return to_json([
            'model' => $item
        ]);
    }

    public function edit($id)
    {
        $this->authorize('access', 'upload-company.update');
        $item = Company::with(['workgroup'])->onlyUpload()->findOrFail($id);

        return to_json([
            'form' => $item
        ]);
    }

    public function update($id, Request $request)
    {
        $this->authorize('access', 'upload-company.update');

        $request->validate([
            'name' => 'required|string|max:255',
            'store_number' => 'required|string|max:255|unique:company,store_number,' . $id,
            'workgroup_id' => 'required|integer|exists:workgroup,id',
        ]);

        $item = Company::onlyUpload()->findOrFail($id);
        $item->fill($request->all());
        $item->only_upload = true;
        $item->save();

        return to_json([
            'saved' => true,
            'id' => $item->id,
            'message' => 'Upload Company updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $this->authorize('access', 'upload-company.delete');
        $item = Company::onlyUpload()->findOrFail($id);
        
        $item->delete();

        return to_json([
            'deleted' => true,
            'message' => 'Upload Company deleted successfully'
        ]);
    }

    public function search()
    {
        $search = request('query');
        $workgroup_id = request('workgroup_id');
        $store_numbers = request('store_numbers');
        $user = Auth::guard('upload-portal')->user();
        if(!$user){
            $user = Auth::user();
        }
        $user = User::with('role')->find($user->id);
        $companyAccess = !isset($user->role) || (isset($user->role) && !in_array($user->role->name, ['admin', 'superadmin'])) ? $user->companiesArray : null;
        $companies = Company::with(['workgroup'])
            ->when($companyAccess, function ($query) use ($companyAccess) {
                return $query->whereIn('id', $companyAccess);
            })
            ->when($search, function ($query) use ($search) {
                return $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('store_number', 'like', '%' . $search . '%');
            })
            ->when($workgroup_id, function ($query) use ($workgroup_id) {
                return $query->where('workgroup_id', $workgroup_id);
            })
            ->when($store_numbers, function ($query) use ($store_numbers) {
                return $query->whereIn('store_number', explode(',', $store_numbers));
            })
            ->selectRaw('id, concat(store_number, " - ", name) as name, store_number, workgroup_id')
            ->get();
            
        return to_json([
            'collection' => $companies
        ]);
    }
}
