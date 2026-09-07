<?php

namespace App\Models\Traits;

use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Model;

/**
 * Add this trait to any model that should be automatically logged for create/update/delete.
 * Override getLoggableAttributesList() to control which fields are stored in old/new values.
 */
trait LogsActivity
{

    /**
     * Storage for old values during updates (keyed by object id to avoid persisting to DB).
     * @var array<int, array>
     */
    protected static array $pendingLogOldValues = [];

    public static function bootLogsActivity(): void
    {
        static::created(function (Model $model) {
            if ($model->shouldLogActivity()) {
                $attributes = $model->getLoggableAttributes($model->getAttributes());
                ActivityLogService::logCreate(
                    $model->getTable(),
                    $model->getKey(),
                    $attributes,
                    null,
                    $model->getIndexValue($attributes)
                );
            }
        });

        static::updating(function (Model $model) {
            if ($model->shouldLogActivity()) {
                self::$pendingLogOldValues[spl_object_id($model)] = $model->getLoggableAttributes($model->getRawOriginal());
            }
        });

        static::updated(function (Model $model) {
            if ($model->shouldLogActivity()) {
                $objectId = spl_object_id($model);
                if (isset(self::$pendingLogOldValues[$objectId])) {
                    $old = self::$pendingLogOldValues[$objectId];
                    $new = $model->getLoggableAttributes($model->getAttributes());

                    // Remove unchanged fields
                    $diffNew = array_diff_assoc($new, $old);

                    if (empty($diffNew)) {
                        return;
                    }

                    ActivityLogService::logUpdate(
                        $model->getTable(),
                        $model->getKey(),
                        $old,
                        $new,
                        null,
                        $model->getIndexValue($new)
                    );
                    unset(self::$pendingLogOldValues[$objectId]);
                }
            }
        });

        static::deleting(function (Model $model) {
            if ($model->shouldLogActivity()) {
                $attributes = $model->getLoggableAttributes($model->getAttributes());
                ActivityLogService::logDelete(
                    $model->getTable(),
                    $model->getKey(),
                    $attributes,
                    null,
                    $model->getIndexValue($attributes)
                );
            }
        });
    }

    protected function shouldLogActivity(): bool
    {
        return true;
    }

    /**
     * Override to limit which attributes are logged. Return null to log all.
     * @return array<string>|null
     */
    protected function getLoggableAttributesList(): ?array
    {
        return null;
    }

    protected function getLoggableAttributes(array $attributes): array
    {
        $loggable = $this->getLoggableAttributesList();
        if ($loggable !== null) {
            return array_intersect_key($attributes, array_flip($loggable));
        }
        return $attributes;
    }

    /**
     * Fields shown on the activity log view page (does not affect storage).
     * Define $activityFields on the model to limit the view.
     * @return array<int, array<string, mixed>>|null
     */
    public function getActivityFields(): ?array
    {
        return property_exists($this, 'activityFields') ? $this->activityFields : null;
    }

    /**
     * Attribute used as the human-readable index on the activity list.
     */
    public function getIndexField(): ?string
    {
        return property_exists($this, 'indexField') ? $this->indexField : null;
    }

    /**
     * Resolve the index value from attributes (or common name/date/description fields).
     * @param array<string, mixed>|null $attributes
     */
    public function getIndexValue(?array $attributes = null): ?string
    {
        $attrs = $attributes ?? $this->getAttributes();
        $field = $this->getIndexField();

        if ($field) {
            $value = $attrs[$field] ?? null;
            if ($value !== null && $value !== '' && is_scalar($value)) {
                return (string) $value;
            }
        }

        foreach (['name', 'pos_name', 'employee_id', 'date', 'description', 'title', 'code'] as $fallback) {
            $value = $attrs[$fallback] ?? null;
            if ($value !== null && $value !== '' && is_scalar($value)) {
                return (string) $value;
            }
        }

        return null;
    }
}
