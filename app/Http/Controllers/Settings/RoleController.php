<?php

namespace App\Http\Controllers\Settings;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\User;
use App\Services\NotificationPermission;
use App\Services\Permission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Models\Settings\Company;
use App\Models\Upload\UploadFolder;
class RoleController extends Controller
{
    private function isAdminRole(Role|string $role): bool
    {
        $name = $role instanceof Role ? $role->name : $role;

        return strtolower($name) === 'admin';
    }

    private function isSuperadminRole(Role|string $role): bool
    {
        $name = $role instanceof Role ? $role->name : $role;

        return strtolower($name) === 'superadmin';
    }

    private function setRoleEditFlags(Role $role): void
    {
        $role->notification_only_edit = $this->isAdminRole($role);
        $role->not_editable = $this->isSuperadminRole($role);
        $role->not_deletable = $role->users_count > 0
            || $this->isAdminRole($role)
            || $this->isSuperadminRole($role);
    }



    public function search()
    {
        $search = request('query');
        $column = request('column') ?? 'name';
        return to_json([
            'collection' => Role::when($search, function ($query) use ($column, $search) {
                return $query->where($column, 'like', '%' . $search . '%');
            })->authorizedWorkgroup()->get()
        ]);
    }

    public function index()
    {
        $this->authorize('access', 'role.index');
        $roles = Role::withCount('users')->authorizedWorkgroup()->filter();
        $roles->each(function ($role) {
            $this->setRoleEditFlags($role);
        });
        return to_json([
            'collection' => $roles,
        ]);
    }

    public function create()
    {
        $this->authorize('access', 'role.create');
        $item = [
            'name' => '',
            // 'permissions' => [],
            'companies' => [],
            'notification_permissions' => [],
            'folder_access' => [],
        ];

        return to_json([
            'form' => $item,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'role.create');
        $workgroup_id = session('workgroup');
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles')->where(function ($query) use ($workgroup_id) {
                    return $query->where('workgroup_id', $workgroup_id);
                }),
                Rule::notIn(['Admin', 'admin', 'Superadmin', 'superadmin'])
            ],
            'permissions' => 'nullable|array',
            'notification_permissions' => 'nullable|array',
            'notification_permissions.*' => ['string', Rule::in(NotificationPermission::keys())],
            'companies' => 'nullable|array'
        ], [
            'name.not_in' => 'The role name "Admin" is reserved and cannot be used.',
        ]);

        $item = new Role;
        $item->name = $request->name;
        $permissions = $request->permissions ?? [];
        $item->permissions = $permissions;
        $item->notification_permissions = array_values($request->notification_permissions ?? []);
        $item->companies = implode(',', $request->companies ?? []);
        $item->folder_access = $request->folder_access ?? [];
        $item->workgroup_id = $workgroup_id;
        $item->save();

        return to_json([
            'saved' => true,
            'id' => $item->id,
            'message' => 'Role created successfully.'
        ]);
    }

    public function show($id)
    {
        $this->authorize('access', 'role.show');
        $item = Role::withCount('users')->findOrFail($id);
        $this->setRoleEditFlags($item);
        $item->companies = $item->companies_array;
        $item->folders = $item->folders();
        return to_json([
            'model' => $item
        ]);
    }

    public function edit($id)
    {
        $this->authorize('access', 'role.update');
        $item = Role::findOrFail($id);
        $all_permissions = Permission::schema();
        
        // Merge existing permissions with all available permissions
        $merged_permissions = [];
        foreach ($all_permissions as $permission) {
            $existing = collect($item->permissions)->firstWhere('name', $permission['name']);
            
            if ($existing) {
                // Merge actions from existing and schema
                $merged_actions = [];
                foreach ($permission['actions'] as $action => $default) {
                    $merged_actions[$action] = $existing['actions'][$action] ?? 0;
                }
                $merged_permissions[] = [
                    'name' => $permission['name'],
                    'actions' => $merged_actions
                ];
            } else {
                // Set all actions to 0 (disabled)
                $merged_actions = [];
                foreach ($permission['actions'] as $action => $default) {
                    $merged_actions[$action] = 0;
                }
                $merged_permissions[] = [
                    'name' => $permission['name'],
                    'actions' => $merged_actions
                ];
            }
        }

        $item->permissions = $merged_permissions;
        $item->notification_permissions = array_values(array_intersect(
            $item->notification_permissions ?? [],
            NotificationPermission::keys()
        ));
        $this->setRoleEditFlags($item);
        $item->companies = Company::with('workgroup')->whereIn('id', $item->companies_array)->select('id', 'name', 'workgroup_id')->get();
        $item->folder_access = UploadFolder::whereIn('id', $item->folder_access ?? [])->select('id', 'name')->get();
        if ($item->not_editable) {
            return to_json([
                'form' => $item,
                'message' => 'Cannot edit or delete Superadmin role.'
            ], 403);
        }
        return to_json([
            'form' => $item
        ]);
    }

    public function update($id, Request $request)
    {
        $this->authorize('access', 'role.update');
        $workgroup_id = session('workgroup');
        $item = Role::findOrFail($id);

        if ($this->isAdminRole($item)) {
            $request->validate([
                'notification_permissions' => 'nullable|array',
                'notification_permissions.*' => ['string', Rule::in(NotificationPermission::keys())],
            ]);

            $item->notification_permissions = array_values($request->notification_permissions ?? []);
            $item->save();

            return to_json([
                'saved' => true,
                'id' => $item->id,
                'message' => 'Notification permissions updated successfully.'
            ]);
        }

        if ($this->isSuperadminRole($item)) {
            return to_json([
                'saved' => false,
                'message' => 'Cannot update Superadmin role.'
            ], 403);
        }

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles')->where(function ($query) use ($workgroup_id) {
                    return $query->where('workgroup_id', $workgroup_id);
                })->ignore($id),
                Rule::notIn(['Admin', 'admin', 'Superadmin', 'superadmin'])
            ],
            'permissions' => 'nullable|array',
            'notification_permissions' => 'nullable|array',
            'notification_permissions.*' => ['string', Rule::in(NotificationPermission::keys())],
            'companies' => 'nullable|array'
        ], [
            'name.not_in' => 'The role name "Admin" is reserved and cannot be used.',
        ]);

        $item->name = $request->name;
        $permissions = $request->permissions ?? [];
        $item->permissions = $permissions;
        $item->notification_permissions = array_values($request->notification_permissions ?? []);
        $item->companies = implode(',', $request->companies ?? []);
        $item->folder_access = $request->folder_access ?? [];
        $item->save();

        return to_json([
            'saved' => true,
            'id' => $item->id,
            'message' => 'Role updated successfully.'
        ]);
    }

    public function destroy($id)
    {
        $this->authorize('access', 'role.delete');
        $item = Role::findOrFail($id);

        // Check if role is assigned to any users
        if ($item->users()->count() > 0) {
            return to_json([
                'deleted' => false,
                'message' => 'Cannot delete role as it is assigned to ' . $item->users()->count() . ' user(s).'
            ]);
        }
        if ($item->name === 'Admin' || $item->name === 'admin') {
            return to_json([
                'deleted' => false,
                'message' => 'Cannot delete Admin role.'
            ]);
        }

        $item->delete();

        return to_json([
            'deleted' => true,
            'message' => 'Role deleted successfully.'
        ]);
    }

    public function getPermissions()
    {
        $this->authorize('access', 'role.index');
        $permissions = Permission::schema();
        return to_json([
            'permissions' => $permissions,
            'notification_permissions_schema' => NotificationPermission::schema(),
        ]);
    }
}
