<?php

declare(strict_types=1);

use Composer\Autoload\ClassMapGenerator as ComposerClassMapGenerator;
use Soyhuce\ClassmapGenerator\ClassmapGenerator;

it('Generates same classmap than Composer', function (): void {
    expect(ClassmapGenerator::createMap(__DIR__ . '/../src'))
        ->toBe(ComposerClassMapGenerator::createMap(__DIR__ . '/../src'));
});
