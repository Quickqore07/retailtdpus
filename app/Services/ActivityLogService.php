<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    /**
     * Log an activity.
     *
     * @param string $action One of: create, update, delete, approve, bulk_import, printing, review
     * @param string $auditableType Table name or model class (e.g. 'employee_hours', 'App\Models\Employee')
     * @param int|null $auditableId Record ID
     * @param array|null $oldValues Previous record data (for update/delete)
     * @param array|null $newValues New record data (for create/update)
     * @param string|null $description Human-readable description
     * @param string|null $indexValue Human-readable index (name, date, description, etc.)
     */
    public static function log(
        string $action,
        string $auditableType,
        ?int $auditableId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null,
        ?string $indexValue = null
    ): ActivityLog {
        return ActivityLog::create([
            'action' => $action,
            'user_id' => Auth::id(),
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'index_value' => $indexValue ?? self::inferIndexValue($newValues ?? $oldValues ?? []),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Infer a display index from common name/date/description fields.
     * @param array<string, mixed> $values
     */
    protected static function inferIndexValue(array $values): ?string
    {
        foreach (['name', 'pos_name', 'employee_id', 'date', 'description', 'title', 'code'] as $key) {
            if (isset($values[$key]) && $values[$key] !== '' && is_scalar($values[$key])) {
                return (string) $values[$key];
            }
        }

        if (!empty($values['date_from']) && !empty($values['date_to'])) {
            return $values['date_from'] === $values['date_to']
                ? (string) $values['date_from']
                : $values['date_from'] . ' to ' . $values['date_to'];
        }

        if (!empty($values['date_from']) && is_scalar($values['date_from'])) {
            return (string) $values['date_from'];
        }

        return null;
    }

    /**
     * Log create action.
     */
    public static function logCreate(
        string $auditableType,
        ?int $auditableId,
        array $newValues,
        ?string $description = null,
        ?string $indexValue = null
    ): ActivityLog {
        return self::log(ActivityLog::ACTION_CREATE, $auditableType, $auditableId, null, $newValues, $description, $indexValue);
    }

    /**
     * Log update action (with old and new values).
     */
    public static function logUpdate(
        string $auditableType,
        ?int $auditableId,
        array $oldValues,
        array $newValues,
        ?string $description = null,
        ?string $indexValue = null
    ): ActivityLog {
        return self::log(ActivityLog::ACTION_UPDATE, $auditableType, $auditableId, $oldValues, $newValues, $description, $indexValue);
    }

    /**
     * Log delete action (old values preserved).
     */
    public static function logDelete(
        string $auditableType,
        ?int $auditableId,
        array $oldValues,
        ?string $description = null,
        ?string $indexValue = null
    ): ActivityLog {
        return self::log(ActivityLog::ACTION_DELETE, $auditableType, $auditableId, $oldValues, null, $description, $indexValue);
    }

    /**
     * Log approve action.
     */
    public static function logApprove(
        string $auditableType,
        ?int $auditableId,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null,
        ?string $indexValue = null
    ): ActivityLog {
        return self::log(ActivityLog::ACTION_APPROVE, $auditableType, $auditableId, $oldValues, $newValues, $description, $indexValue);
    }

    /**
     * Log bulk import action.
     */
    public static function logBulkImport(
        string $auditableType,
        ?array $newValues = null,
        ?string $description = null,
        ?string $indexValue = null
    ): ActivityLog {
        return self::log(ActivityLog::ACTION_BULK_IMPORT, $auditableType, null, null, $newValues, $description, $indexValue);
    }

    /**
     * Log printing action.
     */
    public static function logPrinting(
        string $auditableType,
        ?int $auditableId = null,
        ?array $newValues = null,
        ?string $description = null,
        ?string $indexValue = null
    ): ActivityLog {
        return self::log(ActivityLog::ACTION_PRINTING, $auditableType, $auditableId, null, $newValues, $description, $indexValue);
    }

    /**
     * Log review action.
     */
    public static function logReview(
        string $auditableType,
        ?int $auditableId,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null,
        ?string $indexValue = null
    ): ActivityLog {
        return self::log(ActivityLog::ACTION_REVIEW, $auditableType, $auditableId, $oldValues, $newValues, $description, $indexValue);
    }

    /**
     * Helper: log from Eloquent model (create/update/delete).
     * Call from model observers or controller.
     */
    public static function logModel(Model $model, string $action, ?array $oldValues = null, ?string $description = null): ActivityLog
    {
        $auditableType = $model->getTable();
        $auditableId = $model->getKey();
        $newValues = $model->getAttributes();
        $indexValue = method_exists($model, 'getIndexValue')
            ? $model->getIndexValue($newValues)
            : self::inferIndexValue($newValues);

        return match ($action) {
            ActivityLog::ACTION_CREATE => self::logCreate($auditableType, $auditableId, $newValues, $description, $indexValue),
            ActivityLog::ACTION_UPDATE => self::logUpdate($auditableType, $auditableId, $oldValues ?? [], $newValues, $description, $indexValue),
            ActivityLog::ACTION_DELETE => self::logDelete($auditableType, $auditableId, $oldValues ?? $newValues, $description, $indexValue),
            ActivityLog::ACTION_APPROVE => self::logApprove($auditableType, $auditableId, $oldValues, $newValues, $description, $indexValue),
            ActivityLog::ACTION_REVIEW => self::logReview($auditableType, $auditableId, $oldValues, $newValues, $description, $indexValue),
            default => self::log($action, $auditableType, $auditableId, $oldValues, $newValues, $description, $indexValue),
        };
    }
}
