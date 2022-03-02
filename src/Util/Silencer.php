<?php

declare(strict_types=1);

namespace Soyhuce\ClassmapGenerator\Util;

use Exception;

class Silencer
{
    /** @var array<int> Unpop stack */
    private static array $stack = [];

    public static function suppress(?int $mask = null): int
    {
        if (!isset($mask)) {
            $mask = E_WARNING|E_NOTICE|E_USER_WARNING|E_USER_NOTICE|E_DEPRECATED|E_USER_DEPRECATED|E_STRICT;
        }
        $old = error_reporting();
        self::$stack[] = $old;
        error_reporting($old & ~$mask);

        return $old;
    }

    public static function restore(): void
    {
        if (!empty(self::$stack)) {
            error_reporting(array_pop(self::$stack));
        }
    }

    /**
     * @param callable-string $callable function to execute
     * @param mixed $parameters function to execute
     * @throws Exception any exceptions from the callback are rethrown
     * @return mixed return value of the callback
     */
    public static function call(callable $callable, ...$parameters)
    {
        try {
            self::suppress();
            $result = $callable(...$parameters);
            self::restore();

            return $result;
        } catch (Exception $e) {
            // Use a finally block for this when requirements are raised to PHP 5.5
            self::restore();

            throw $e;
        }
    }
}
