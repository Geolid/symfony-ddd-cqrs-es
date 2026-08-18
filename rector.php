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

    // Every method here is invoked through reflection, not a traceable PHP call site: a #[Apply]
    // method matched by its event's type-hint (can legitimately have an empty body — the event
    // alone feeds the Read Model, nothing for the aggregate to remember), a Handler/Processor's
    // __invoke() routed by Messenger/the event subscription on its typed parameter, a Translator/
    // Projector's on<Event>() routed by #[Subscribe], a Console command's __invoke(), a webhook
    // Consumer's __invoke(), a Symfony UserInterface method the security component calls regardless
    // of local usage. Static analysis can't see any of those callers.
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
        RemoveEmptyClassMethodRector::class => $reflectionInvokedPaths,

        // Rewrites a null-comparison into an instanceof check — fights this repo's own consistent
        // `null !== $x`/`null === $x` idiom used throughout, everywhere it would fire.
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
