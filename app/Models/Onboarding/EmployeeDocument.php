<?php

namespace App\Models\Onboarding;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\LogsActivity;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class EmployeeDocument extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'employee_documents';

    protected $fillable = [
        'employee_id',
        'document_name',
        'document_path',
        'document_type',
        'created_by',
        'updated_by',
    ];

    protected $activityFields = [
        ['field' => 'employee_id', 'label' => 'Employee', 'table' => 'employee', 'table_field' => 'pos_name', 'type' => 'lookup'],
        ['field' => 'document_name', 'label' => 'Document Name', 'type' => 'string'],
        ['field' => 'document_path', 'label' => 'Document Path', 'type' => 'string'],
        ['field' => 'document_type', 'label' => 'Document Type', 'type' => 'string'],
        ['field' => 'count', 'label' => 'Count', 'type' => 'number'],
        ['field' => 'date_from', 'label' => 'Date From', 'type' => 'date'],
        ['field' => 'date_to', 'label' => 'Date To', 'type' => 'date'],
        ['field' => 'dates', 'label' => 'Dates', 'type' => 'array'],
        ['field' => 'filename', 'label' => 'Filename', 'type' => 'string'],
        ['field' => 'source', 'label' => 'Source', 'type' => 'string'],
        ['field' => 'imported_at', 'label' => 'Imported At', 'type' => 'string'],
        ['field' => 'ids', 'label' => 'Record IDs', 'type' => 'array'],
    ];

    const DOCUMENT_TYPE_I9_FORM = 'i9_form';
    const DOCUMENT_TYPE_I9_FORM_UNSIGNED = 'i9_form_unsigned';
    const DOCUMENT_TYPE_W4_FORM = 'w4_form';
    const DOCUMENT_TYPE_PROFILE_PICTURE = 'profile_picture';
    const DOCUMENT_TYPE_LIST_A = 'list_a';
    const DOCUMENT_TYPE_LIST_B = 'list_b';
    const DOCUMENT_TYPE_LIST_C = 'list_c';
    const DOCUMENT_TYPE_AUTHORIZATION = 'authorization';
    const DOCUMENT_TYPE_TNC = 'tnc';
    const DOCUMENT_TYPE_OTHER = 'other';

    public static function getDocumentTypes(): array
    {
        return [
            self::DOCUMENT_TYPE_I9_FORM,
            self::DOCUMENT_TYPE_I9_FORM_UNSIGNED,
            self::DOCUMENT_TYPE_W4_FORM,
            self::DOCUMENT_TYPE_PROFILE_PICTURE,
            self::DOCUMENT_TYPE_LIST_A,
            self::DOCUMENT_TYPE_LIST_B,
            self::DOCUMENT_TYPE_LIST_C,
            self::DOCUMENT_TYPE_AUTHORIZATION,
            self::DOCUMENT_TYPE_TNC,
            self::DOCUMENT_TYPE_OTHER,
        ];
    }
    
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id')->select('id', 'employee_id', 'pos_name');
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->select('id', 'name');
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by')->select('id', 'name');
    }
    protected $appends = ['document_path_url'];

    public function getDocumentPathUrlAttribute(): ?string
    {
        if (empty($this->document_path)) {
            return null;
        }
    
        $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
    
        $adapter = Storage::disk($disk);
        /** @var \Illuminate\Contracts\Filesystem\FilesystemAdapter $adapter */
        if ($disk === 's3') {
            return $adapter->temporaryUrl(
                $this->document_path,
                now()->addMinutes(30)
            );
        }
    
        return $adapter->url($this->document_path);
    }


    protected static function booted()
    {
        static::creating(function ($model) {
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