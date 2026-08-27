<?php

declare(strict_types=1);

use Castor\Attribute\AsArgsAfterOptionEnd;
use Castor\Attribute\AsArgument;
use Castor\Attribute\AsTask;

use function Castor\fs;

#[AsTask(description: 'Install and start the full project')]
function start(): void
{
    if (!fs()->exists(__DIR__.'/../compose.override.yaml')) {
        fs()->copy(__DIR__.'/../compose.override.yaml.dist', __DIR__.'/../compose.override.yaml');
    }

    docker_up();
    vendor();
    hooks();
    db_create();
    assets();
}

#[AsTask(description: 'Open shell in app container, run a shell-interpreted command, or (after --) exec raw argv for tooling like captainhook')]
function sh(
    #[AsArgument(description: 'Command to run instead of an interactive shell (shell-interpreted, e.g. "ls | grep x")')]
    ?string $cmd = null,
    #[AsArgsAfterOptionEnd]
    array $args = [],
): void {
    if ([] !== $args) {
        compose_exec($args);

        return;
    }

    compose_exec(null !== $cmd ? ['/bin/sh', '-c', $cmd] : ['/bin/sh']);
}

#[AsTask(description: 'Clear all caches')]
function cc(
    #[AsArgument(description: 'Restrict to a single DM (default: all)', autocomplete: 'autocomplete_apps')]
    ?string $app = null,
): void {
    if (null !== $app) {
        resolve_apps($app);
        fs()->remove(sprintf('%s/../var/cache/%s/%s', __DIR__, app_env(), $app));
    } else {
        fs()->remove(glob(__DIR__.'/../var/cache/*') ?: []);
    }

    warmup($app);
}

#[AsTask(name: 'dump', description: 'Start Symfony VarDumper server')]
function dump_server(): void
{
    console(['server:dump']);
}
