<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class ActivityLogController extends Controller
{
    public function index()
    {
        $this->authorize('access', 'activity-log.index');

        $logs = ActivityLog::with(['user:id,name,username'])
            ->filter();

        return to_json([
            'collection' => $logs,
        ]);
    }

    public function show($id)
    {
        $this->authorize('access', 'activity-log.show');

        $log = ActivityLog::with(['user:id,name,username,email'])
            ->findOrFail($id);

        return to_json([
            'model' => $log,
            'activity_fields' => $log->activityFields(),
            'display_old_values' => $this->displayValues($log, $log->old_values),
            'display_new_values' => $this->displayValues($log, $log->new_values),
        ]);
    }

    /**
     * Build view-only values: when activityFields is set, keep only those keys
     * and resolve lookup labels. Full row remains stored on the log.
     */
    protected function displayValues(ActivityLog $log, ?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $fields = $log->activityFields();
        if (!$fields) {
            return $values;
        }

        $display = [];

        foreach ($fields as $field) {
            $key = $field['field'] ?? null;
            if (!$key || !array_key_exists($key, $values)) {
                continue;
            }

            if (($field['type'] ?? null) === 'table' && is_array($values[$key])) {
                $display[$key] = array_map(function ($row) use ($field) {
                    if (!is_array($row)) {
                        return $row;
                    }

                    $resolved = $row;
                    foreach ($field['columns'] ?? [] as $col) {
                        $colKey = $col['field'] ?? null;
                        if (!$colKey || !array_key_exists($colKey, $row)) {
                            continue;
                        }

                        $resolved[$colKey] = $this->resolveFieldValue($row[$colKey], $col);
                    }

                    return $resolved;
                }, $values[$key]);
                continue;
            }

            $display[$key] = $this->resolveFieldValue($values[$key], $field);
        }

        return $display;
    }

    protected function resolveFieldValue($value, array $field)
    {
        $type = $field['type'] ?? null;

        if ($value === null || $value === '') {
            return $value;
        }

        if ($type === 'boolean') {
            if (is_bool($value)) {
                return $value ? 'Yes' : 'No';
            }
            if (in_array($value, [1, '1', 'true', 'True', 'yes', 'Yes'], true)) {
                return 'Yes';
            }
            if (in_array($value, [0, '0', 'false', 'False', 'no', 'No'], true)) {
                return 'No';
            }

            return $value;
        }

        if ($type === 'date') {
            try {
                return Carbon::parse($value)->format('m/d/Y');
            } catch (Throwable $e) {
                return $value;
            }
        }

        if ($type === 'number' && is_numeric($value)) {
            return (float) $value == (int) $value
                ? number_format((int) $value)
                : number_format((float) $value, 2);
        }

        if ($type === 'array') {
            if (is_array($value)) {
                return implode(', ', array_map(fn ($item) => is_scalar($item) ? (string) $item : json_encode($item), $value));
            }
            return $value;
        }

        if (!in_array($type, ['lookup', 'comma_separated_lookup'], true)) {
            return $value;
        }

        $table = $field['table'] ?? null;
        $tableField = $field['table_field'] ?? 'name';
        if (!$table) {
            return $value;
        }

        try {
            if ($type === 'comma_separated_lookup') {
                $ids = array_values(array_filter(array_map('trim', explode(',', (string) $value)), 'strlen'));
                if (!$ids) {
                    return $value;
                }

                $labels = DB::table($table)
                    ->whereIn('id', $ids)
                    ->pluck($tableField, 'id');

                return implode(', ', array_map(
                    fn ($id) => $labels[$id] ?? $id,
                    $ids
                ));
            }

            $label = DB::table($table)
                ->where('id', $value)
                ->value($tableField);

            if ($table === 'employee') {
                $employee = DB::table('employee')->where('id', $value)->first(['employee_id', 'pos_name']);
                if ($employee) {
                    $parts = array_filter([$employee->employee_id, $employee->pos_name]);
                    return $parts ? implode(' - ', $parts) : $value;
                }
            }

            return $label !== null ? $label : $value;
        } catch (Throwable $e) {
            return $value;
        }
    }
}
