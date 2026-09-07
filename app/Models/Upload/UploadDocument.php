<?php

namespace App\Models\Upload;

use Illuminate\Database\Eloquent\Model;
use App\Models\AP\ApInvoice;
use App\Models\Settings\Company;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class UploadDocument extends Model
{
    protected $fillable = [
        'folder_id',
        'company_id',
        'name',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'ref_id',
        'ref_type',
        'is_invoice',
        'is_sales_invoice',
        'is_check',
        'invoice_status',
        'invoice_approved_at',
        'invoice_approved_by',
        'check_status',
        'check_paid_at',
        'check_paid_by',
        'uploaded_by',
        'editable',
        'approved_amount',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function folder()
    {
        return $this->belongsTo(UploadFolder::class, 'folder_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id')->selectRaw('id, CONCAT(store_number, " - ", name) as name, store_number, workgroup_id');
    }

    public function getCompanyAttribute()
    {
        return $this->company;
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by')->select('id', 'name');
    }

    public function apInvoice()
    {
        return $this->hasOne(ApInvoice::class, 'document_id');
    }

    public function getFileUrlAttribute(): ?string
    {
        if (empty($this->file_path)) {
            return null;
        }
        
        $disk = config('filesystems.default') === 's3' ? 's3' : 'public';

        $adapter = Storage::disk($disk);
        /** @var \Illuminate\Contracts\Filesystem\FilesystemAdapter $adapter */
        if ($disk === 's3') {
            return $adapter->temporaryUrl(
                $this->file_path,
                now()->addMinutes(30)
            );
        }
        return $adapter->url($this->file_path);
    }
}
