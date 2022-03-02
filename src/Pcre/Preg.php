<?php

declare(strict_types=1);

namespace Soyhuce\ClassmapGenerator\Pcre;

use InvalidArgumentException;

class Preg
{
    /**
     * @param non-empty-string $pattern
     * @param array<string|null> $matches Set by method
     * @return 0|1
     */
    public static function match(string $pattern, string $subject, ?array &$matches = null, int $flags = 0, int $offset = 0): int
    {
        if (($flags & PREG_OFFSET_CAPTURE) !== 0) {
            throw new InvalidArgumentException('PREG_OFFSET_CAPTURE is not supported as it changes the type of $matches, use matchWithOffsets() instead');
        }

        $result = preg_match($pattern, $subject, $matches, $flags|PREG_UNMATCHED_AS_NULL, $offset);
        if ($result === false) {
            throw PcreException::fromFunction('preg_match', $pattern);
        }

        return $result;
    }

    /**
     * @param non-empty-string $pattern
     * @param array<int|string, list<string|null>> $matches Set by method
     */
    public static function matchAll(string $pattern, string $subject, ?array &$matches = null, int $flags = 0, int $offset = 0): int
    {
        if (($flags & PREG_OFFSET_CAPTURE) !== 0) {
            throw new InvalidArgumentException('PREG_OFFSET_CAPTURE is not supported as it changes the type of $matches, use matchAllWithOffsets() instead');
        }

        if (($flags & PREG_SET_ORDER) !== 0) {
            throw new InvalidArgumentException('PREG_SET_ORDER is not supported as it changes the type of $matches');
        }

        $result = preg_match_all($pattern, $subject, $matches, $flags|PREG_UNMATCHED_AS_NULL, $offset);
        if ($result === false) {
            throw PcreException::fromFunction('preg_match_all', $pattern);
        }

        return $result;
    }

    /**
     * @param array<string>|string $pattern
     * @param array<string>|string $replacement
     */
    public static function replace(array|string $pattern, array|string $replacement, string $subject, int $limit = -1, ?int &$count = null): string
    {
        $result = preg_replace($pattern, $replacement, $subject, $limit, $count);
        if ($result === null) {
            throw PcreException::fromFunction('preg_replace', $pattern);
        }

        return $result;
    }

    /**
     * @param array<string>|string $pattern
     */
    public static function replaceCallback(array|string $pattern, callable $replacement, string $subject, int $limit = -1, ?int &$count = null, int $flags = 0): string
    {
        $result = preg_replace_callback($pattern, $replacement, $subject, $limit, $count, $flags|PREG_UNMATCHED_AS_NULL);
        if ($result === null) {
            throw PcreException::fromFunction('preg_replace_callback', $pattern);
        }

        return $result;
    }

    /**
     * @param non-empty-string $pattern
     * @param array<string|null> $matches Set by method
     */
    public static function isMatch(string $pattern, string $subject, ?array &$matches = null, int $flags = 0, int $offset = 0): bool
    {
        return (bool) static::match($pattern, $subject, $matches, $flags, $offset);
    }
}
