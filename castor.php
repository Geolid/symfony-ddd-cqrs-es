<?php

declare(strict_types=1);

use Castor\Attribute\AsContext;
use Castor\Attribute\AsTask;
use Castor\Context;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Process\ExecutableFinder;

use function Castor\context;
use function Castor\guard_min_version;
use function Castor\import;
use function Castor\io;
use function Castor\load_dot_env;
use function Castor\run;

guard_min_version('1.7.0');

import(__DIR__.'/.castor');

// Populates $_SERVER/$_ENV (not putenv, by Symfony's own design) from .env -> .env.local ->
// .env.$APP_ENV -> .env.$APP_ENV.local — the same cascade Symfony itself boots with. A real
// env var already present wins over the file values.
load_dot_env();

#[AsTask(description: 'Show quick start guide', default: true)]
function about(): void
{
    io()->title('DDD/CQRS/Event Sourcing showcase, Onion-layered with pluggable Delivery Mechanisms');
    io()->comment('Run <comment>castor list</comment> to display the command list.');
    io()->comment('Run <comment>castor start</comment> to set up the project.');
    io()->comment('Run <comment>castor qa</comment> before opening a PR.');
}

#[AsContext(default: true)]
function defaultContext(): Context
{
    return new Context(environment: ['APP_ENV' => is_string($_SERVER['APP_ENV'] ?? null) ? $_SERVER['APP_ENV'] : 'dev']);
}

/**
 * Runs a command in the app container. The ambient context's environment (see
 * `with(..., environment: [...])`) is forwarded via `-e` — `docker compose exec` doesn't
 * inherit the host process' own env otherwise.
 * Executes bare (without docker-compose) in CI, inside a container, or if Docker is missing.
 *
 * @param array<string> $args
 */
function dockerExec(array $args, ?bool $tty = null): void
{
    $tty ??= context()->supportsInteraction;

    $bare = getenv('CI')
        || file_exists('/.dockerenv')
        || null === new ExecutableFinder()->find('docker');

    if ($bare) {
        run($args, context: context()->withTty($tty)->withPty($tty));

        return;
    }

    $uid = function_exists('posix_getuid') ? posix_getuid().':'.posix_getgid() : '1000:1000';
    $command = ['docker', 'compose', 'exec'];
    if (!$tty) {
        $command[] = '-T';
    }
    foreach (context()->environment as $key => $value) {
        $command = [...$command, '-e', "{$key}=".$value];
    }
    $command = [...$command, '-u', $uid, 'app', ...$args];

    run($command, context: context()->withTty($tty)->withPty($tty));
}

/**
 * Runs a bin/console command in the app container.
 *
 * @param array<string> $args
 */
function console(array $args, ?bool $tty = null): void
{
    dockerExec(['php', 'bin/console', '--ansi', ...$args], $tty);
}

function appEnv(string $default = 'dev'): string
{
    return (string) (context()->environment['APP_ENV'] ?? $default);
}

/**
 * @return list<string>
 */
function apps(): array
{
    return array_map(basename(...), glob(__DIR__.'/apps/*', \GLOB_ONLYDIR) ?: []);
}

/**
 * @return list<string>
 */
function autocompleteApps(CompletionInput $input): array
{
    return apps();
}

/**
 * Validates $app against apps() and resolves it to a one-element list, or every app if null.
 *
 * @return list<string>
 */
function resolveApps(?string $app): array
{
    if (null === $app) {
        return apps();
    }

    if (!in_array($app, apps(), true)) {
        io()->error(sprintf('The DM must be one of: %s.', implode(', ', apps())));

        exit(1);
    }

    return [$app];
}
