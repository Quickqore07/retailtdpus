<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Str;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define('access', function ($user, $type) {
            // Ensure role is loaded
            if (!$user->relationLoaded('role')) {
                $user->load('role');
            }

            if(isset($user->role) && (strtolower($user->role->name) == 'admin' || strtolower($user->role->name) == 'superadmin')){
                return true;
            }
            if(strpos($type, '||') !== false){
                return collect(explode('||', $type))->some(function($type) use ($user){
                    return in_array(Str::replace(' ', '', $type), $user->userRolePermissions() ?? []);
                });
            }
            return (in_array($type, $user->userRolePermissions() ?? []));
        });
        Gate::define('sp-access', function ($user, $permission) {
            // Ensure role is loaded
            if (!$user->relationLoaded('role')) {
                $user->load('role');
            }
            
            if(isset($user->role) && (strtolower($user->role->name) == 'admin' || strtolower($user->role->name) == 'superadmin')){
                return true;
            }
            
            return (in_array($permission, $user->spPermissions() ?? []));
        });
        Gate::define('fund-requirement-access', function ($user) {
            $fundRequirementAccess = getSettingValue('fund-requirement-access', null);
            $fundRequirementAccess = explode(',', $fundRequirementAccess);
            return in_array($user->username, $fundRequirementAccess);
        });
    }
    
    
}
