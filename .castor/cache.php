<?php

declare(strict_types=1);

use Castor\Attribute\AsArgsAfterOptionEnd;
use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;

use function Castor\context;
use function Castor\fs;
use function Castor\io;
use function Castor\with;

/**
 * @param list<string> $args
 */
#[AsTask(name: 'warmup', namespace: 'cache', description: 'Warm up all caches')]
function cache_warmup(
    #[AsOption(name: 'app-id', description: 'Restrict to a single DM (default: all)', autocomplete: 'apps')]
    ?string $appId = null,
    #[AsArgsAfterOptionEnd]
    array $args = [],
): void {
    // Forced: Symfony only dumps the container XML phpstan-symfony needs when APP_DEBUG=1.
    $environment = ['APP_DEBUG' => '1'];

    io()->comment('Shared');
    with(static fn () => console(['cache:warmup', ...$args]), environment: $environment, context: context());

    for_each_app($appId, static function (string $app) use ($args): void {
        io()->comment("DM: {$app}");
        console(['cache:warmup', ...$args]);
    }, $environment);
}

/**
 * @param list<string> $args
 */
#[AsTask(name: 'clear', namespace: 'cache', description: 'Clear all caches', aliases: ['cc'])]
function cache_clear(
    #[AsOption(name: 'app-id', description: 'Restrict to a single DM (default: all)', autocomplete: 'apps')]
    ?string $appId = null,
    #[AsArgsAfterOptionEnd]
    array $args = [],
): void {
    if (null !== $appId) {
        resolve_apps($appId);
        fs()->remove(sprintf('%s/../var/cache/%s/%s', __DIR__, app_env(), $appId));
    } else {
        fs()->remove(glob(__DIR__.'/../var/cache/*') ?: []);
    }

    cache_warmup($appId, $args);
}
