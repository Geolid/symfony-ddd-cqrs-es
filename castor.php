<?php

declare(strict_types=1);

defined('CASTOR_USE_CHDIR') || define('CASTOR_USE_CHDIR', true);

use Castor\Attribute\AsContext;
use Castor\Attribute\AsTask;
use Castor\Context;
use Castor\Exception\ProblemException;
use Symfony\Component\Process\ExecutableFinder;

use function Castor\context;
use function Castor\guard_min_version;
use function Castor\import;
use function Castor\io;
use function Castor\load_dot_env;
use function Castor\run;
use function Castor\with;

guard_min_version('1.7.0');

import(__DIR__.'/.castor');

load_dot_env();

#[AsTask(description: 'Show quick start guide', default: true)]
function about(): void
{
    io()->title('DDD/CQRS/Event Sourcing showcase, Onion-layered with Ports & Adapters and pluggable Delivery Mechanisms');

    io()->section('Quick Start');
    io()->listing([
        'Run <comment>castor setup</comment> to set up the project.',
        'Run <comment>castor qa</comment> before opening a PR.',
        'Run <comment>castor list</comment> to display the command list.',
    ]);
}

#[AsContext(name: 'dev', default: true)]
function dev_context(): Context
{
    return new Context(environment: [
        'APP_ENV' => is_string($_SERVER['APP_ENV'] ?? null) ? $_SERVER['APP_ENV'] : 'dev',
        'APP_DEBUG' => is_string($_SERVER['APP_DEBUG'] ?? null) ? $_SERVER['APP_DEBUG'] : '1',
    ]);
}

#[AsContext(name: 'test')]
function test_context(): Context
{
    return new Context(environment: [
        'APP_ENV' => getenv('APP_ENV') ?: 'test',
        ...array_filter(['FAKER_SEED' => getenv('FAKER_SEED'), 'FOUNDRY_FAKER_SEED' => getenv('FOUNDRY_FAKER_SEED')], is_string(...)),
    ]);
}

#[AsContext(name: 'debug')]
function debug_context(): Context
{
    return test_context()->withEnvironment([
        'ACTIVE_BROWSER' => 'playwright',
        'PLAYWRIGHT_HEADLESS' => 'false',
        'XDEBUG_MODE' => 'debug',
    ]);
}

#[AsContext(name: 'demo')]
function demo_context(): Context
{
    return dev_context()->withEnvironment(['APP_ENV' => 'demo']);
}

function app_env(string $default = 'dev'): string
{
    return (string) (context()->environment['APP_ENV'] ?? $default);
}

/**
 * Forwards the context's environment via `-e`. Runs bare in CI, inside a container, or without Docker.
 *
 * @param list<string> $args
 */
function workspace_exec(array $args, ?bool $tty = null): void
{
    $tty ??= context()->supportsInteraction;

    run(workspace_command($args, $tty), context: context()->withTty($tty)->withPty($tty));
}

/**
 * @param list<string> $args
 *
 * @return list<string>
 */
function workspace_command(array $args, bool $tty = false): array
{
    $bare = getenv('CI')
        || file_exists('/.dockerenv')
        || null === new ExecutableFinder()->find('docker');

    if ($bare) {
        return $args;
    }

    $uid = function_exists('posix_getuid') ? posix_getuid().':'.posix_getgid() : '1000:1000';
    $command = ['docker', 'compose', 'exec'];
    if (!$tty) {
        $command[] = '-T';
    }
    foreach (context()->environment as $key => $value) {
        $command = [...$command, '-e', "{$key}=".$value];
    }

    return [...$command, '-u', $uid, 'app', ...$args];
}

/**
 * @param list<string> $args
 */
function console(array $args, ?bool $tty = null): void
{
    workspace_exec(['php', 'bin/console', '--ansi', ...$args], $tty);
}

/**
 * @param callable(string): void $callback
 * @param array<string, string>  $environment
 */
function for_each_app(?string $appId, callable $callback, array $environment = [], ?string $context = null): void
{
    foreach (resolve_apps($appId) as $app) {
        with(static fn () => $callback($app), environment: ['APP_ID' => $app, ...$environment], context: null !== $context ? context($context) : context());
    }
}

/**
 * One-element list for a valid $appId, every DM if null.
 *
 * @return list<string>
 */
function resolve_apps(?string $appId): array
{
    $all = apps();

    assert_one_of($appId, $all, 'DM');

    return null !== $appId ? [$appId] : $all;
}

/**
 * @return list<string>
 */
function apps(): array
{
    return array_map(basename(...), glob(__DIR__.'/apps/*', \GLOB_ONLYDIR) ?: []);
}

/**
 * @param list<string> $allowed
 */
function assert_one_of(?string $value, array $allowed, string $label): void
{
    if (null !== $value && !in_array($value, $allowed, true)) {
        throw new ProblemException(sprintf('Invalid %s "%s". Allowed values are: %s.', $label, $value, implode(', ', $allowed)));
    }
}
