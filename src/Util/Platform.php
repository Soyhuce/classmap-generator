<?php

declare(strict_types=1);

namespace Soyhuce\ClassmapGenerator\Util;

use RuntimeException;

class Platform
{
    public static function getCwd(bool $allowEmpty = false): string
    {
        $cwd = getcwd();

        // fallback to realpath('') just in case this works but odds are it would break as well if we are in a case where getcwd fails
        if ($cwd === false) {
            $cwd = realpath('');
        }

        // crappy state, assume '' and hopefully relative paths allow things to continue
        if ($cwd === false) {
            if ($allowEmpty) {
                return '';
            }

            throw new RuntimeException('Could not determine the current working directory');
        }

        return $cwd;
    }
}
