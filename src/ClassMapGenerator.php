<?php

declare(strict_types=1);

namespace Soyhuce\ClassMapGenerator;

use RuntimeException;
use Soyhuce\ClassMapGenerator\Pcre\Preg;
use Soyhuce\ClassMapGenerator\Util\Filesystem;
use Soyhuce\ClassMapGenerator\Util\Platform;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use function count;
use function defined;
use function in_array;

class ClassMapGenerator
{
    /**
     * @throws RuntimeException When the path is neither an existing file nor directory
     * @return array<class-string, string> A class map array
     */
    public static function createMap(string $path): array
    {
        if (is_file($path)) {
            $path = [new SplFileInfo($path)];
        } elseif (is_dir($path) || strpos($path, '*') !== false) {
            $path = Finder::create()->files()->followLinks()->name('/\.(php|inc|hh)$/')->in($path);
        } else {
            throw new RuntimeException(
                'Could not scan for classes inside "' . $path .
                '" which does not appear to be a file nor a folder'
            );
        }

        $map = [];
        $filesystem = new Filesystem();
        $cwd = realpath(Platform::getCwd());

        foreach ($path as $file) {
            $filePath = $file->getPathname();
            if (!in_array(pathinfo($filePath, PATHINFO_EXTENSION), ['php', 'inc', 'hh'])) {
                continue;
            }

            if (!$filesystem->isAbsolutePath($filePath)) {
                $filePath = $cwd . '/' . $filePath;
                $filePath = $filesystem->normalizePath($filePath);
            } else {
                $filePath = Preg::replace('{[\\\\/]{2,}}', '/', $filePath);
            }

            $classes = self::findClasses($filePath);

            foreach ($classes as $class) {
                if (!isset($map[$class])) {
                    $map[$class] = $filePath;
                }
            }
        }

        return $map;
    }

    /**
     * @throws RuntimeException
     * @return array<int, class-string> The found classes
     */
    private static function findClasses(string $path): array
    {
        $extraTypes = self::getExtraTypes();

        /**
         * Use @ here instead of Silencer to actively suppress 'unhelpful' output.
         * @see https://github.com/composer/composer/pull/4886
         */
        $contents = @php_strip_whitespace($path);
        if (!$contents) {
            if (!file_exists($path)) {
                $message = 'File at "%s" does not exist, check your classmap definitions';
            } elseif (!Filesystem::isReadable($path)) {
                $message = 'File at "%s" is not readable, check its permissions';
            } elseif (trim((string) file_get_contents($path)) === '') {
                // The input file was really empty and thus contains no classes
                return [];
            } else {
                $message = 'File at "%s" could not be parsed as PHP, it may be binary or corrupted';
            }
            $error = error_get_last();
            if (isset($error['message'])) {
                $message .= PHP_EOL . 'The following message may be helpful:' . PHP_EOL . $error['message'];
            }

            throw new RuntimeException(sprintf($message, $path));
        }

        // return early if there is no chance of matching anything in this file
        Preg::matchAll('{\b(?:class|interface|trait' . $extraTypes . ')\s}i', $contents, $matches);
        if (!$matches) {
            return [];
        }

        $p = new PhpFileCleaner($contents, count($matches[0]));
        $contents = $p->clean();
        unset($p);

        Preg::matchAll('{
            (?:
                 \b(?<![\$:>])(?P<type>class|interface|trait' . $extraTypes . ') \s++ (?P<name>[a-zA-Z_\x7f-\xff:][a-zA-Z0-9_\x7f-\xff:\-]*+)
               | \b(?<![\$:>])(?P<ns>namespace) (?P<nsname>\s++[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+(?:\s*+\\\\\s*+[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+)*+)? \s*+ [\{;]
            )
        }ix', $contents, $matches);

        $classes = [];
        $namespace = '';

        for ($i = 0, $len = count($matches['type']); $i < $len; $i++) {
            if (!empty($matches['ns'][$i])) {
                $namespace = str_replace([' ', "\t", "\r", "\n"], '', (string) $matches['nsname'][$i]) . '\\';
            } else {
                $name = $matches['name'][$i];
                // skip anon classes extending/implementing
                if ($name === 'extends' || $name === 'implements') {
                    continue;
                }
                if ($name[0] === ':') {
                    // This is an XHP class, https://github.com/facebook/xhp
                    $name = 'xhp' . substr(str_replace(['-', ':'], ['_', '__'], $name), 1);
                } elseif (strtolower($matches['type'][$i]) === 'enum') {
                    // something like:
                    //   enum Foo: int { HERP = '123'; }
                    // The regex above captures the colon, which isn't part of
                    // the class name.
                    // or:
                    //   enum Foo:int { HERP = '123'; }
                    // The regex above captures the colon and type, which isn't part of
                    // the class name.
                    $colonPos = strrpos($name, ':');
                    if ($colonPos !== false) {
                        $name = substr($name, 0, $colonPos);
                    }
                }
                $classes[] = ltrim($namespace . $name, '\\');
            }
        }

        /** @var array<int, class-string> $classes */
        return $classes;
    }

    private static function getExtraTypes(): string
    {
        static $extraTypes = null;

        if ($extraTypes === null) {
            $extraTypes = '';
            if (PHP_VERSION_ID >= 80100 || (defined('HHVM_VERSION') && version_compare(HHVM_VERSION, '3.3', '>='))) {
                $extraTypes .= '|enum';
            }

            PhpFileCleaner::setTypeConfig(array_merge(['class', 'interface', 'trait'], array_filter(explode('|', $extraTypes))));
        }

        return $extraTypes;
    }
}
