<?php

namespace App\Models\Upload;

use App\Models\AR\CustomerPayment;
use App\Models\AR\SalesInvoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UploadPortalCustomer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'upload_portal_customers';

    protected $fillable = [
        'qq_id',
        'qq_company_id',
        'name',
        'email',
        'mobile',
        'fax',
        'primary_person_name',
        'email_notes',
        'address_line_1',
        'address_line_2',
        'address_line_3',
        'city',
        'state',
        'country',
        'zip_code',
        'bank_name',
        'account_number',
        'routing_number',
        'credit_card_number',
        'cvv',
        'card_type',
        'card_expiry',
        'name_on_card',
        'financial_zip_code',
        'invoice_due',
        'invoice_condition',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'invoice_due' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->select('id', 'name');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by')->select('id', 'name');
    }

    public function salesInvoices()
    {
        return $this->hasMany(SalesInvoice::class, 'customer_id');
    }

    public function customerPayments()
    {
        return $this->hasMany(CustomerPayment::class, 'customer_id');
    }

    /**
     * Customer-facing name for PDFs and emails (strips internal " - {id}" suffix).
     */
    public function documentDisplayName(): string
    {
        return self::stripInternalNameSuffix($this->name);
    }

    public static function stripInternalNameSuffix(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '';
        }

        $stripped = preg_replace('/\s*-\s*\d+\s*$/', '', $name);
        $stripped = trim((string) $stripped);

        return $stripped !== '' ? $stripped : $name;
    }
}
