<?php

namespace App\Models\Onboarding;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class I9Reverification extends Model
{
    use HasFactory;

    protected $table = 'i9_reverifications';

    protected $fillable = [
        'i9_form_id',
        'rehire_date',
        'new_last_name',
        'new_first_name',
        'new_middle_initial',
        'document_title',
        'document_number',
        'expiration_date',
        'employer_representative_name',
        'employer_signature',
        'today_date',
        'additional_information',
        'alternative_procedure_dhs',
    ];

    protected $casts = [
        'alternative_procedure_dhs' => 'boolean',
    ];

    public function formI9()
    {
        return $this->belongsTo(FormI9::class, 'i9_form_id');
    }
}
