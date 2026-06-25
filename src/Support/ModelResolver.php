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
}
