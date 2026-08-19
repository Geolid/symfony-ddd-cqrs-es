<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPublicMethodParameterRector;
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

    // Reflection-invoked, no traceable PHP call site: #[Apply], Handler/Processor __invoke(),
    // Translator/Projector on<Event>(), Console __invoke(), webhook Consumer __invoke().
    $reflectionInvokedPaths = [
        __DIR__.'/src/*/*/Domain/*.php',
        __DIR__.'/src/*/*/Application/Command/*/*Handler.php',
        __DIR__.'/src/*/*/Application/Query/*/*Handler.php',
        __DIR__.'/src/*/*/Application/Processor/*.php',
        __DIR__.'/src/*/*/Infrastructure/Persistence/EventStore/Translator/*.php',
        __DIR__.'/src/*/*/Infrastructure/Persistence/Projection/Projector/*.php',
        __DIR__.'/apps/*/src/Console/*.php',
        __DIR__.'/apps/*/src/Consumer/*.php',
        __DIR__.'/apps/*/src/Security/*.php',
    ];

    $rectorConfig->skip([
        __DIR__.'/config/reference.php',
        __DIR__.'/tools/PHPStan/Tests/data',

        RemoveUnusedPrivateMethodRector::class => $reflectionInvokedPaths,
        RemoveUnusedPrivateMethodParameterRector::class => $reflectionInvokedPaths,
        RemoveUnusedPublicMethodParameterRector::class => $reflectionInvokedPaths,
        // #[Apply] only — a body-less apply is legit (event feeds the Read Model, nothing to keep).
        RemoveEmptyClassMethodRector::class => [
            __DIR__.'/src/*/*/Domain/*.php',
        ],

        // Fights the null !== $x / null === $x convention.
        FlipTypeControlToUseExclusiveTypeRector::class,
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
