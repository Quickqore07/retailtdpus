<?php

namespace App\Models\Onboarding;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class I9PreparerTranslator extends Model
{
    use HasFactory;

    protected $table = 'i9_preparer_translators';

    protected $fillable = [
        'i9_form_id',
        'signature',
        'signature_date',
        'last_name',
        'first_name',
        'middle_initial',
        'address',
        'city',
        'state',
        'zip_code',
    ];

    public function formI9()
    {
        return $this->belongsTo(FormI9::class, 'i9_form_id');
    }
}
