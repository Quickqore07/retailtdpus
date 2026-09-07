<?php

namespace App\Http\Controllers\Upload;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Services\SPPermission;

class UploadUserController extends Controller
{
    public function index()
    {
        $this->authorize('access', 'upload-user.index');
        $users = User::where('only_upload', true)->filter();

        return to_json([
            'collection' => $users,
        ]);
    }

    public function getSPPermissions()
    {
        $allPermissions = SPPermission::schema();
        $uploadPortalPermissions = array_filter($allPermissions, function($permission) {
            return $permission['name'] === 'upload-portal'
            || $permission['name'] === 'upload-portal-customer'
            || $permission['name'] === 'upload-portal-invoice'
            || $permission['name'] === 'upload-portal-customer-payment'
            || $permission['name'] === 'upload-portal-ar-email-template'
            || $permission['name'] === 'upload-portal-ar-settings'
            || $permission['name'] === 'upload-portal-ar-aging-report'
            || $permission['name'] === 'upload-portal-ar-customer-balance-report';
        });
        
        return to_json([
            'sp_permissions' => array_values($uploadPortalPermissions)
        ]);
    }

    public function create()
    {
        $this->authorize('access', 'upload-user.create');
        $item = [
            'name' => '',
            'username' => '',
            'password' => '',
            'companies' => '',
            'view_password' => '',
            'folder_access' => [],
            'sp_permission' => [],
            'office' => null,
            'office_id' => null,
            'checkout_time' => null,
            'only_upload' => true,
        ];

        return to_json([
            'form' => $item,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'upload-user.create');

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'current_password' => 'required|string|min:6',
            'companies' => 'nullable',
            'folder_access' => 'nullable|array',
            'sp_permission' => 'nullable|array',
            'office_id' => 'nullable|integer|exists:office,id',
            'checkout_time' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
        ]);

        $companyAccess = is_array($request->companies) 
            ? implode(',', array_column($request->companies, 'id'))
            : $request->companies;
            
        
        $item = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'password' => bcrypt($request->current_password),
            'companies' => $companyAccess,
            'current_password' => $request->current_password ?? '',
            'folder_access' => $request->folder_access ?? [],
            'sp_permission' => $request->sp_permission,
            'office_id' => $request->office_id,
            'checkout_time' => $request->checkout_time ? substr((string) $request->checkout_time, 0, 5) : null,
            'only_upload' => true,
        ]);

        return to_json([
            'saved' => true,
            'id' => $item->id,
            'message' => 'Upload User created successfully',
        ]);
    }

    public function show($id)
    {
        $this->authorize('access', 'upload-user.show');
        $item = User::where('only_upload', true)->with('office')->findOrFail($id);

        if ($item->checkout_time) {
            $item->checkout_time = substr((string) $item->checkout_time, 0, 5);
        }
        
        $companyIds = $item->companiesArray ?? [];
        $item->company_access = \App\Models\Settings\Company::whereIn('id', $companyIds)
            ->selectRaw('id, CONCAT(store_number, " - ", name) as name, workgroup_id')
            ->get();
        $item->folder_access = $item->folders();
        
        // Include special permissions
        if (!$item->sp_permission || empty($item->sp_permission)) {
            // Initialize with upload-portal permissions if not set
            $allPermissions = SPPermission::schema();
            $uploadPortalPermissions = array_filter($allPermissions, function($permission) {
                return $permission['name'] === 'upload-portal';
            });
            $item->sp_permission = array_values($uploadPortalPermissions);
        }
        
        // Group companies by workgroup
        $item->companies_by_workgroup = [];
        if (!empty($companyIds)) {
            $rows = \App\Models\Settings\Company::with('workgroup')
                ->whereIn('company.id', $companyIds)
                ->orderBy('company.workgroup_id')
                ->orderBy('company.store_number')
                ->select('company.id', 'company.name', 'company.store_number', 'company.workgroup_id')
                ->get();
            $item->companies_by_workgroup = $rows->groupBy('workgroup_id')->map(function ($group) {
                $first = $group->first();
                return [
                    'workgroup_id' => (int) $first->workgroup_id,
                    'workgroup_name' => $first->workgroup?->name ?? '—',
                    'companies_array' => $group->values()->map(function ($c) {
                        $label = $c->store_number
                            ? ($c->store_number . ' - ' . $c->name)
                            : $c->name;
                        return ['id' => $c->id, 'name' => $label];
                    })->values()->all(),
                ];
            })->values()->all();
        }
        
        return to_json([
            'model' => $item
        ]);
    }

    public function edit($id)
    {
        $this->authorize('access', 'upload-user.update');
        $item = User::where('only_upload', true)->with('office')->findOrFail($id);
        
        $companyIds = $item->companiesArray ?? [];
        $item->company_access = \App\Models\Settings\Company::whereIn('id', $companyIds)
            ->selectRaw('id, CONCAT(store_number, " - ", name) as name, workgroup_id')
            ->get();
        $item->folder_access = $item->folders();
        $item->password = '';

        if ($item->checkout_time) {
            $item->checkout_time = substr((string) $item->checkout_time, 0, 5);
        }
        
        $allPermissions = SPPermission::schema();
        $uploadPortalPermissions = array_filter($allPermissions, function($permission) {
            return $permission['name'] === 'upload-portal'
            || $permission['name'] === 'upload-portal-customer'
            || $permission['name'] === 'upload-portal-invoice'
            || $permission['name'] === 'upload-portal-customer-payment'
            || $permission['name'] === 'upload-portal-ar-email-template'
            || $permission['name'] === 'upload-portal-ar-settings'
            || $permission['name'] === 'upload-portal-ar-aging-report'
            || $permission['name'] === 'upload-portal-ar-customer-balance-report';
        });
        // Include special permissions
        if (!$item->sp_permission || empty($item->sp_permission)) {
            // Initialize with upload-portal permissions if not set
            $item->sp_permission = array_values($uploadPortalPermissions);

        }else{
            $merged_sp = [];
            foreach ($uploadPortalPermissions as $permission) {
                $existing = collect($item->sp_permission)->firstWhere('name', $permission['name']);
                if ($existing) {
                    $merged_actions = [];
                    foreach ($permission['actions'] as $action => $default) {
                        $merged_actions[$action] = $existing['actions'][$action] ?? 0;
                    }
                    $merged_sp[] = ['name' => $permission['name'], 'actions' => $merged_actions];
                } else {
                    $merged_actions = [];
                    foreach ($permission['actions'] as $action => $default) {
                        $merged_actions[$action] = 0;
                    }
                    $merged_sp[] = [
                        'name' => $permission['name'],
                        'actions' => $merged_actions
                    ];
                }
            }
            $item->sp_permission = $merged_sp;
        }

        return to_json([
            'form' => $item
        ]);
    }

    public function update($id, Request $request)
    {
        $this->authorize('access', 'upload-user.update');

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $id,
            'password' => 'nullable|string|min:6',
            'companies' => 'nullable',
            'view_password' => 'nullable|string|max:255',
            'folder_access' => 'nullable|array',
            'sp_permission' => 'nullable|array',
            'office_id' => 'nullable|integer|exists:office,id',
            'checkout_time' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
        ]);

        $item = User::where('only_upload', true)->findOrFail($id);
        $item->name = $request->name;
        $item->username = $request->username;
        
        $companyAccess = is_array($request->companies) 
            ? implode(',', array_column($request->companies, 'id'))
            : $request->companies;
            
        
        if ($request->filled('current_password')) {
            $item->password = bcrypt($request->current_password);
            $item->current_password = $request->current_password;
        }
        
        $item->companies = $companyAccess;
        $item->folder_access = $request->folder_access ?? [];
        $item->office_id = $request->office_id;
        $item->checkout_time = $request->checkout_time ? substr((string) $request->checkout_time, 0, 5) : null;
        
        // Update special permissions
        if ($request->has('sp_permission')) {
            $item->sp_permission = $request->sp_permission;
        }
        
        $item->only_upload = true;
        $item->save();

        return to_json([
            'saved' => true,
            'id' => $item->id,
            'message' => 'Upload User updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $this->authorize('access', 'upload-user.delete');
        $item = User::where('only_upload', true)->findOrFail($id);
        
        $item->delete();

        return to_json([
            'deleted' => true,
            'message' => 'Upload User deleted successfully'
        ]);
    }

    public function search()
    {
        $search = request('query');

        $users = User::where('only_upload', true)
            ->when($search, function ($query) use ($search) {
                return $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('username', 'like', '%' . $search . '%');
            })
            ->select('id', 'name', 'username')
            ->orderBy('name')
            ->get();

        return to_json([
            'collection' => $users
        ]);
    }
}
