<?php

declare(strict_types=1);

namespace Soyhuce\ClassmapGenerator\Util;

use Soyhuce\ClassmapGenerator\Pcre\Preg;
use function count;
use function strlen;

class Filesystem
{
    public function isAbsolutePath(string $path): bool
    {
        return strpos($path, '/') === 0 || substr($path, 1, 1) === ':' || strpos($path, '\\\\') === 0;
    }

    public function normalizePath(string $path): string
    {
        $parts = [];
        $path = strtr($path, '\\', '/');
        $prefix = '';
        $absolute = '';

        // extract windows UNC paths e.g. \\foo\bar
        if (strpos($path, '//') === 0 && strlen($path) > 2) {
            $absolute = '//';
            $path = substr($path, 2);
        }

        // extract a prefix being a protocol://, protocol:, protocol://drive: or simply drive:
        if (Preg::isMatch('{^( [0-9a-z]{2,}+: (?: // (?: [a-z]: )? )? | [a-z]: )}ix', $path, $match)) {
            $prefix = $match[1];
            $path = substr($path, strlen($prefix));
        }

        if (strpos($path, '/') === 0) {
            $absolute = '/';
            $path = substr($path, 1);
        }

        $up = false;
        foreach (explode('/', $path) as $chunk) {
            if ($chunk === '..' && (strlen($absolute) > 0 || $up)) {
                array_pop($parts);
                $up = !(count($parts) === 0 || end($parts) === '..');
            } elseif ($chunk !== '.' && $chunk !== '') {
                $parts[] = $chunk;
                $up = $chunk !== '..';
            }
        }

        // ensure c: is normalized to C:
        $prefix = Preg::replaceCallback('{(^|://)[a-z]:$}i', function (array $m) { return strtoupper($m[0]); }, $prefix);

        return $prefix . $absolute . implode('/', $parts);
    }

    public static function isReadable(string $path): bool
    {
        if (is_readable($path)) {
            return true;
        }

        if (is_file($path)) {
            return Silencer::call('file_get_contents', $path, false, null, 0, 1) !== false;
        }

        if (is_dir($path)) {
            return Silencer::call('opendir', $path) !== false;
        }

        // assume false otherwise
        return false;
    }
}
