<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\ValueObject\PhpVersion;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/apps',
        __DIR__.'/bootstrap',
        __DIR__.'/config',
        __DIR__.'/demo',
        __DIR__.'/src',
        __DIR__.'/tests',
        __DIR__.'/tools',
    ]);

    $rectorConfig->skip([
        __DIR__.'/config/reference.php',
        __DIR__.'/tools/PHPStan/Tests/data',
    ]);

    $rectorConfig->phpVersion(PhpVersion::PHP_85);

    $rectorConfig->phpstanConfig(__DIR__.'/phpstan.rector.neon');

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_85,
        SetList::DEAD_CODE,
        SetList::CODE_QUALITY,
        SetList::TYPE_DECLARATION,
        SetList::EARLY_RETURN,
        SetList::PRIVATIZATION,
    ]);

    $rectorConfig->rules([
        ReadOnlyPropertyRector::class,
    ]);

    $rectorConfig->cacheDirectory(__DIR__.'/var/rector');
};
