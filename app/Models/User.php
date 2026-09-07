<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Settings\Company;
use App\Models\Settings\Office;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Support\Filterable;
use Illuminate\Support\Facades\Auth;
use App\Models\Traits\LogsActivity;
use App\Support\AuthorizedWorkgroup;
use App\Models\AppNotification;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, Filterable, LogsActivity, AuthorizedWorkgroup;

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['role'];
    protected $indexField = 'name';

    protected $activityFields = [
        ['field' => 'name', 'label' => 'Name', 'type' => 'string'],
        ['field' => 'username', 'label' => 'Username', 'type' => 'string'],
        ['field' => 'user_code', 'label' => 'User Code', 'type' => 'string'],
        ['field' => 'role_id', 'label' => 'Role', 'table' => 'roles', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'email', 'label' => 'Email', 'type' => 'string'],
        ['field' => 'phone', 'label' => 'Phone', 'type' => 'string'],
        ['field' => 'mfa', 'label' => 'MFA', 'type' => 'boolean'],
        ['field' => 'workgroup_id', 'label' => 'Workgroup', 'table' => 'workgroup', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'office_id', 'label' => 'Office', 'table' => 'office', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'checkout_time', 'label' => 'Checkout Time', 'type' => 'string'],
        ['field' => 'only_upload', 'label' => 'Only Upload', 'type' => 'boolean'],
        ['field' => 'count', 'label' => 'Count', 'type' => 'number'],
        ['field' => 'date_from', 'label' => 'Date From', 'type' => 'date'],
        ['field' => 'date_to', 'label' => 'Date To', 'type' => 'date'],
        ['field' => 'dates', 'label' => 'Dates', 'type' => 'array'],
        ['field' => 'filename', 'label' => 'Filename', 'type' => 'string'],
        ['field' => 'source', 'label' => 'Source', 'type' => 'string'],
        ['field' => 'imported_at', 'label' => 'Imported At', 'type' => 'string'],
        ['field' => 'ids', 'label' => 'Record IDs', 'type' => 'array'],
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'user_code',
        'role_id',
        'email',
        'phone',
        'registration_flag',
        'mfa',
        'password',
        'current_password',
        'permissions',
        'companies',
        'sp_permission',
        'workgroup_id',
        'office_id',
        'checkout_time',
        'folder_access',
        'only_upload',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected $sortable = [
        'name',
        'username',
        'email',
        'phone',
        'created_at',
        'companies_count',
    ];

    protected $searchable = [
        'name',
        'email',
        'phone',
        'username',
        'created_at',
        'role_id'
    ];

    protected $allowedFilters = [
        'name',
        'email',
        'phone',
        'username',
        'created_at',
        'role_id'
    ];

    protected $searchableColumns = [
        'name',
        'email',
        'phone',
        'username',
    ];
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
            'folder_access' => 'array',
            'only_upload' => 'boolean',
        ];
    }

    /**
     * Get sp_permission attribute - returns user's own permissions or inherits from role
     */
    public function getSpPermissionAttribute($value)
    {
        // If user has their own sp_permission, return it
        if ($value) {
            return json_decode($value, true);
        }

        // Otherwise, inherit from role's sp_permissions
        if ($this->role && isset($this->role->sp_permissions)) {
            return $this->role->sp_permissions;
        }

        return [];
    }

    /**
     * Set sp_permission attribute
     */
    public function setSpPermissionAttribute($value)
    {
        $this->attributes['sp_permission'] = is_array($value) ? json_encode($value) : $value;
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    public function appNotifications()
    {
        return $this->hasMany(AppNotification::class);
    }
    public function scopeOnlyRegular($query,$field_name = 'only_upload')
    {
        return $query->where($field_name, 0);
    }



    public function userRolePermissions()
    {
        $items = [];
        if(!isset($this->role) || !$this->role->permissions ) {
            return [];
        }
        foreach ($this->role->permissions as $group) {
            foreach ($group['actions'] as $key => $value) {
                if ($value) {
                    $items[] = $group['name'] . '.' . $key;
                }
            }
        }

        return $items;
    }
    public function spPermissions()
    {
        $items = [];

        if(empty($this->sp_permission)) {
            return [];
        }
        foreach ($this->sp_permission as $permission) {
            foreach ($permission['actions'] as $action => $value) {
                if ($value) {
                    $items[] = $permission['name'] . '.' . $action;
                }
            }
        }
        return $items;
    }

    public function getCompaniesArrayAttribute()
    {
        if (!$this->companies) {
            return [];
        }
        return array_map('intval', explode(',', $this->companies));
    }

    public function folders()
    {
        if (!$this->folder_access) {
            return [];
        }

        return \App\Models\Upload\UploadFolder::whereIn('id', $this->folder_access)->selectRaw('id, name')->get();
    }


    public static function generateUniqueUserCode(): string
    {
        do {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (static::where('user_code', $code)->exists());

        return $code;
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->user_code)) {
                $model->user_code = static::generateUniqueUserCode();
            }

            if (empty($model->created_by)) {
                $model->created_by = Auth::id();
            }

            if (empty($model->updated_by)) {
                $model->updated_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (empty($model->updated_by)) {
                $model->updated_by = Auth::id();
            }
        });
    }
   

}
