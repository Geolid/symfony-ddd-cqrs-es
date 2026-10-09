<?php

declare(strict_types=1);

use Castor\Attribute\AsOption;
use Castor\Attribute\AsPathArgument;
use Castor\Attribute\AsTask;

use function Castor\context;
use function Castor\io;
use function Castor\with;

/**
 * @param list<string> $args
 */
#[AsTask(name: 'stan', namespace: 'qa', description: 'Run static analysis on src/, tests/, sandbox/ and all DMs')]
#[NeedsWarmup]
function qa_stan(
    #[AsOption(name: 'app-id', description: 'Restrict to a single DM (default: src/, tests/ and every DM)', autocomplete: 'apps')]
    ?string $appId = null,
    #[AsPathArgument(description: 'Files or directories to analyse (only src/, or --app-id if given)', filter: '*.php')]
    array $args = [],
): void {
    $analyses = [
        'src/' => ['phpstan.neon', []],
        'tests/' => ['tests/phpstan.neon', ['APP_ENV' => 'test', 'APP_ENV_UCFIRST' => 'Test']],
        'sandbox/' => ['phpstan.sandbox.neon', []],
    ];

    foreach (resolve_apps($appId) as $app) {
        $analyses["DM: {$app}"] = [is_file(__DIR__."/../../apps/{$app}/phpstan.neon") ? "apps/{$app}/phpstan.neon" : 'apps/phpstan.neon', ['APP_ID' => $app]];
    }

    $paths = array_filter($args, static fn (string $arg): bool => file_exists(__DIR__.'/../../'.$arg));
    $testsPaths = array_filter($paths, static fn (string $path): bool => str_starts_with($path, 'tests/') || str_starts_with($path, 'tools/PHPUnit/'));

    $selected = match (true) {
        null !== $appId => ["DM: {$appId}"],
        [] !== $testsPaths => ['tests/'],
        [] !== $paths => ['src/'],
        default => array_keys($analyses),
    };

    foreach ($selected as $name) {
        [$config, $environment] = $analyses[$name];

        io()->comment("{$name} ({$config})");

        with(
            static fn () => workspace_exec(['vendor/bin/phpstan', 'analyse', '-c', $config, ...$args]),
            environment: ['APP_ENV_UCFIRST' => ucfirst(app_env()), ...$environment],
            context: context(),
        );
    }
}
