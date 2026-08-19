<?php

declare(strict_types=1);

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsTask;

use function Castor\fs;

#[AsTask(description: 'Install and start the full project')]
function start(): void
{
    if (!fs()->exists(__DIR__.'/../compose.override.yaml')) {
        fs()->copy(__DIR__.'/../compose.override.yaml.dist', __DIR__.'/../compose.override.yaml');
    }

    dockerUp();
    vendor();
    hooks();
    dbCreate();
    assets();
}

#[AsTask(description: 'Open shell in app container')]
function sh(
    #[AsArgument(description: 'Command to run instead of an interactive shell')]
    ?string $cmd = null,
): void {
    dockerExec(null !== $cmd ? ['/bin/sh', '-c', $cmd] : ['/bin/sh']);
}

#[AsTask(description: 'Clear all caches')]
function cc(): void
{
    fs()->remove(glob(__DIR__.'/../var/cache/*') ?: []);
    warmup();
}

#[AsTask(name: 'dump', description: 'Start Symfony VarDumper server')]
function dumpServer(): void
{
    console(['server:dump']);
}
