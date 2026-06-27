<?php

namespace Jguapin\ApprovalMapping\Support;

use Illuminate\Database\Eloquent\Builder;

class ModelResolver
{
    public static function modelClass(string $key): ?string
    {
        $class = config("approval-mapping.models.{$key}");

        return is_string($class) && class_exists($class) ? $class : null;
    }

    public static function query(string $key): ?Builder
    {
        $class = self::modelClass($key);

        return $class ? $class::query() : null;
    }

    public static function connection(string $key): ?string
    {
        $class = self::modelClass($key);
        if (! $class) {
            return null;
        }

        return (new $class)->getConnectionName();
    }

    /**
     * Check whether an organizational feature is enabled in config.
     * Falls back to true if the key does not exist (safe default).
     */
    public static function isEnabled(string $key): bool
    {
        return (bool) config("approval-mapping.features.{$key}", true);
    }

    /**
     * Resolve a mapped column name for a given model key and field role.
     * e.g. fieldMap('company', 'code') => 'company_code'
     */
    public static function fieldMap(string $modelKey, string $field, string $default = ''): string
    {
        $value = config("approval-mapping.field_maps.{$modelKey}.{$field}");

        return is_string($value) ? $value : $default;
    }
}
