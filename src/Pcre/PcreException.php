<?php

declare(strict_types=1);

namespace Soyhuce\ClassMapGenerator\Pcre;

use RuntimeException;
use function function_exists;
use function is_array;

class PcreException extends RuntimeException
{
    /**
     * @param array<string>|string $pattern
     */
    public static function fromFunction(string $function, array|string $pattern): self
    {
        $code = preg_last_error();

        if (is_array($pattern)) {
            $pattern = implode(', ', $pattern);
        }

        return new self($function . '(): failed executing "' . $pattern . '": ' . self::pcreLastErrorMessage($code), $code);
    }

    private static function pcreLastErrorMessage(int $code): string
    {
        if (function_exists('preg_last_error_msg')) {
            return preg_last_error_msg();
        }

        $constants = get_defined_constants(true);
        if (!isset($constants['pcre'])) {
            return 'UNDEFINED_ERROR';
        }

        foreach ($constants['pcre'] as $const => $val) {
            if ($val === $code && substr($const, -6) === '_ERROR') {
                return $const;
            }
        }

        return 'UNDEFINED_ERROR';
    }
}
