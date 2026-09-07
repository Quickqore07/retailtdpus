<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Services\MailService;
use App\Services\Permission;
use App\Services\SPPermission;
use App\Models\Settings\Company;
use App\Models\Upload\UploadFolder;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function search(Request $request)
    {
        $role = $request->role;
        $workgroupCheck = $request->workgroup_check;
        $companies = null;
        $workgroups = $request->workgroups;
        if ($workgroups) {
            $workgroups = explode(',', $workgroups);
            $companies = Company::whereIn('workgroup_id', $workgroups)->authorizedCompanies('id',false)->pluck('id')->toArray();
        }else if($request->has('workgroups') && $workgroups == null){
            return to_json([
                'collection' => [],
            ]);
        }
        
        if ($role) {
            $users = User::onlyRegular()->whereHas('role', function ($query) use ($role) {
                $query->where('name', $role);
            })->when($workgroupCheck, function ($query)  {
                $query->where('workgroup_id', session('workgroup'));
            })->where( function ($query) use ($companies) {
                if ($companies) {
                    foreach ($companies as $company) {
                        $query->orWhereRaw("FIND_IN_SET(?, companies)", [$company]);
                    }
                }
                return $query;
            })->search();
        } else {
            $users = User::onlyRegular()->when($workgroupCheck, function ($query)  {
                $query->where('workgroup_id', session('workgroup'));
            })->search();
        }
        return to_json([
            'collection' => $users,
        ]);
    }

    public function index()
    {
        $this->authorize('access', 'user.index');
        $currentWorkgroup = request()->session()->get('workgroup');
        $companies = Company::where('workgroup_id', $currentWorkgroup)->pluck('id')->toArray();
        $users = User::onlyRegular()->with(['role'])
            ->select(
                'id',
                'name',
                'username',
                'email',
                'phone',
                'role_id',
                'created_at',
                'updated_at',
                'only_upload',
                'user_code',
                DB::raw("(CASE WHEN companies IS NULL OR TRIM(companies) = '' THEN 0 ELSE (LENGTH(companies) - LENGTH(REPLACE(companies, ',', '')) + 1) END) as companies_count")
            )
            ->Where(function ($q) use ($companies, $currentWorkgroup) {
                foreach ($companies as $companyId) {
                    $q->orWhereRaw("FIND_IN_SET(?, companies)", [$companyId]);
                }
                $q->orWhereHas('role', function ($q) {
                    $q->where('name', 'admin')->orWhere('name', 'superadmin');
                })->orWhere('workgroup_id', $currentWorkgroup);
            })



            ->filter();

        $users->each(function ($user) {
            $user->deletable = !$user->only_upload &&  $user->id != Auth::user()->id && strtolower($user->role->name) != 'admin' && strtolower($user->role->name) != 'superadmin';
        });
        return to_json([
            'collection' => $users,
        ]);
    }

    public function create()
    {
        $this->authorize('access', 'user.create');
        $item = [
            'name' => '',
            'email' => '',
            'password' => '',
            'role' => null,
            'role_id' => null,
            'permissions' => [],
            'sp_permission' => [],
            'companies' => [],
            'folder_access' => [],
            'office' => null,
            'office_id' => null,
            'checkout_time' => null,
        ];

        return to_json([
            'form' => $item,
        ]);
    }

    public function store(Request $request, MailService $mailService)
    {
        $this->authorize('access', 'user.create');

        $request->validate([
            'name' => 'required',
            'username' => 'required|unique:users,username',
            // 'email' => 'required|unique:users,email',
            'role_id' => 'required|integer|exists:roles,id',
            'office_id' => 'nullable|integer|exists:office,id',
            'checkout_time' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            // 'current_password' => 'nullable|min:8',
        ]);
        DB::beginTransaction();
        try {

            $role = Role::find($request->role_id);
            $role_companies = explode(',', $role->companies);

            $user = User::find(Auth::user()->id);
            $workgroup_id = $request->session()->get('workgroup');
            if (!$workgroup_id) {
                return to_json([
                    'success' => false,
                    'saved' => false,
                    'message' => 'Workgroup is required'
                ]);
            }

            if (!empty($role_companies)) {

                $item = new User;
                $item->name = $request->name;
                $item->username = $request->username;
                $item->role_id = $request->role_id;
                $item->email = $request->email ?? "";
                $item->phone = $request->phone ?? "";
                $item->registration_flag = 1;
                $item->permissions = $request->permissions ?? [];
                $item->sp_permission = $request->sp_permission ?? [];
                $item->companies = implode(',', array_values(array_unique(array_map('intval', $request->companies ?? []))));
                $item->folder_access = $request->folder_access ?? [];
                $item->workgroup_id = $workgroup_id;
                $item->office_id = $request->office_id;
                $item->checkout_time = $request->checkout_time ? substr((string) $request->checkout_time, 0, 5) : null;
                $item->current_password = $request->current_password ?? "";
                $item->password = bcrypt($request->current_password);
                $item->save();
                // Generate password reset token
                // $passwordLinkService = new PasswordLinkService();
                // $passwordLink = $passwordLinkService->generatePasswordLink($item,'setup_user', 24 );

                // // Send Email with password setup link
                // $mail = new SetPasswordMail($item, $passwordLink->token);
                // $mailService->sendMail($item->email, "Set Password", $mail->render());

                DB::commit();
                return to_json([
                    'success' => true,
                    'saved' => true,
                    'id' => $item->id,
                    'message' => 'User created successfully',
                ]);
            }
            DB::rollBack();
            return to_json([
                'success' => false,
                'saved' => false,
                'message' => 'There is no company in this user'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            dd($th);
            return to_json([
                'success' => false,
                'saved' => false,
                'message' => $th->getMessage()
            ]);
        }
    }


    public function show($id)
    {
        $this->authorize('access', 'user.show');
        $item = User::with(['role', 'office'])->findOrFail($id);
        $item->permissions = $item->permissions ? explode(',', $item->permissions) : [];
        $item->sp_permission = $item->sp_permission;
        $item->folders = $item->folders();
        $item->not_deletable = $item->id == Auth::user()->id || $item->role->name == 'admin' || $item->role->name == 'superadmin';
        
        // Get TDPUS companies
        $companyIds = $item->companies_array;
        $item->companies_by_workgroup = [];
        if (!empty($companyIds)) {
            $rows = Company::with('workgroup')
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
                    'type' => 'tdpus',
                    'companies_array' => $group->values()->map(function ($c) {
                        $label = $c->store_number
                            ? ($c->store_number . ' - ' . $c->name)
                            : $c->name;

                        return ['id' => $c->id, 'name' => $label];
                    })->values()->all(),
                ];
            })->values()->all();
        }


        $user = User::find(Auth::user()->id);
        if (!in_array('user.password', $user->spPermissions()) && strtolower($user->role->name) != 'admin' && strtolower($user->role->name) != 'superadmin') {
            unset($item->current_password);
        }
        unset($item->companies_array);
        unset($item->companies);
        return to_json([
            'model' => $item
        ]);
    }

    public function edit($id)
    {
        $this->authorize('access', 'user.update');
        $item = User::with(['role', 'office'])->findOrFail($id);
        $item->permissions = $item->permissions ? json_decode($item->permissions, true) : [];
        $existing_sp = $item->sp_permission;
        $all_sp = SPPermission::schema();
        $merged_sp = [];


        $user = User::find(Auth::user()->id);
        if (!in_array('user.password', $user->spPermissions()) && strtolower($user->role->name) != 'admin' && strtolower($user->role->name) != 'superadmin') {
            unset($item->current_password);
        }
        foreach ($all_sp as $permission) {
            $existing = collect($existing_sp)->firstWhere('name', $permission['name']);
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
        
        // Load TDPUS companies
        $item->companies = Company::whereIn('id', $item->companies_array)->select('id', 'name', 'workgroup_id')->get();
        
        
        $item->folder_access = UploadFolder::whereIn('id', $item->folder_access ?? [])->select('id', 'name')->get();
        if ($item->checkout_time) {
            $item->checkout_time = substr((string) $item->checkout_time, 0, 5);
        }
        return to_json([
            'form' => $item
        ]);
    }

    public function UpdatePassword($id, Request $request)
    {
        $this->authorize('access', 'user.update');
        $request->validate([
            'password' => 'required|min:8'
        ]);

        $user = User::findOrFail($id);
        if ($request->has('password')) {
            $user->password = bcrypt($request->password);
        }

        $user->save();
        return to_json([
            'saved' => true,
            'id' => $user->id
        ]);
    }


    public function update($id, Request $request)
    {
        $this->authorize('access', 'user.update');

        $request->validate([
            'name' => 'required',
            'username' => 'required|unique:users,username,' . $id . ',id',
            // 'email' => 'required|unique:users,email,' . $id . ',id',
            'role_id' => 'required|integer|exists:roles,id',
            'office_id' => 'nullable|integer|exists:office,id',
            'checkout_time' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            // 'current_password' => 'nullable|min:8',
        ]);

        $user = User::find(Auth::user()->id);

        $item = User::findOrFail($id);
        $item->name = $request->name;
        $item->username = $request->username;
        $item->email = $request->email;
        $item->phone = $request->phone;
        $item->role_id = $request->role_id;
        $item->office_id = $request->office_id;
        $item->checkout_time = $request->checkout_time ? substr((string) $request->checkout_time, 0, 5) : null;
        $item->permissions = $request->permissions ?? [];
        $item->sp_permission = $request->sp_permission ?? [];
        $item->companies = implode(',', array_values(array_unique(array_map('intval', $request->companies ?? []))));
        $item->folder_access = $request->folder_access ?? [];
        $item->current_password = $request->current_password ?? "";
        $item->password = bcrypt($request->current_password);

        $item->save();

        return to_json([
            'saved' => true,
            'id' => $item->id
        ]);
    }

    public function getUserPermissions()
    {
        $permissions = Permission::schema();
        return to_json([
            'permissions' => $permissions
        ]);
    }

    public function getSPPermissions()
    {
        $permissions = SPPermission::schema();
        return to_json([
            'sp_permissions' => $permissions
        ]);
    }

    public function destroy($id)
    {
        $this->authorize('access', 'user.delete');
        $item = User::findOrFail($id);

        // cannot delete self
        if (Auth::user()->id == $item->id) {
            return to_json([
                'deleted' => false,
                'message' => 'Cannot delete self'
            ]);
        }

        $item->delete();

        return to_json([
            'deleted' => true
        ]);
    }
}
