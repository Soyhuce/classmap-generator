<?php

declare(strict_types=1);

use Composer\Autoload\ClassMapGenerator as ComposerClassMapGenerator;
use Soyhuce\ClassmapGenerator\ClassmapGenerator;

it('Generates same classmap than Composer', function (string $directory): void {
    expect(ClassmapGenerator::createMap($directory))
        ->toBe(ComposerClassMapGenerator::createMap($directory));
})->with([
    __DIR__ . '/../src',
    __DIR__ . '/../src/*',
    __DIR__ . '/../vendor',
]);
