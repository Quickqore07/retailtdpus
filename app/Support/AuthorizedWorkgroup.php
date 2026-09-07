<?php

namespace App\Support;

trait AuthorizedWorkgroup
{
    public function scopeAuthorizedWorkgroup($query, string $column = 'workgroup_id')
    {
        $workgroup_id = session('workgroup');
        if (!$workgroup_id) {
            return abort(403, 'Unauthorized action.');
        }

        return $query->where($column, $workgroup_id);
    }
}
