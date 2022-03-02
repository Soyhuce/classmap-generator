<?php

declare(strict_types=1);

use Composer\Autoload\ClassMapGenerator as ComposerClassMapGenerator;
use Soyhuce\ClassmapGenerator\ClassMapGenerator;

it('Generates same class map than Composer', function (string $directory): void {
    expect(ClassMapGenerator::createMap($directory))
        ->toBe(ComposerClassMapGenerator::createMap($directory));
})->with([
    __DIR__ . '/../src',
    __DIR__ . '/../src/*',
    __DIR__ . '/../vendor',
]);
