<?php

namespace App\Support;

trait AuthorizedCompanies
{
    public function scopeAuthorizedCompanies($query,$column = 'company_id', $checkWorkgroup = true)
    {
        $authorized_companies = authorizedCompanies($checkWorkgroup);
        return $query->whereIn($column, $authorized_companies);
    }
}