<?php

declare(strict_types=1);

use Castor\Attribute\AsArgsAfterOptionEnd;
use Castor\Attribute\AsArgument;
use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;
use Castor\Context;

use function Castor\fs;
use function Castor\with;

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

/**
 * @param array<string> $args
 */
#[AsTask(description: 'Open shell in app container, run a shell-interpreted command, or (after --) exec raw argv')]
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

#[AsTask(name: 'debug-test', description: 'Run a test with Xdebug and a visible browser')]
#[NeedsPlaywright]
function debug_test(
    #[AsOption(description: 'Filter tests by name')]
    ?string $filter = null,
    #[AsOption(description: 'Run a specific test suite')]
    ?string $suite = null,
    #[AsArgument(description: 'Target test file or directory')]
    ?string $target = null,
): void {
    with(static fn () => compose_exec([
        'vendor/bin/phpunit', '--display-all-issues', '--no-coverage',
        ...(null !== $filter ? ['--filter', $filter] : []),
        ...(null !== $suite ? ['--testsuite', $suite] : []),
        ...(null !== $target ? [$target] : []),
    ]), environment: [
        'PLAYWRIGHT_HEADLESS' => 'false',
        'XDEBUG_MODE' => 'debug',
    ], context: new Context());
}
