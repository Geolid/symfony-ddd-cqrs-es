<?php

declare(strict_types=1);

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;

use function Castor\context;
use function Castor\io;
use function Castor\with;

#[AsTask(name: 'stan', namespace: 'qa', description: 'Run static analysis on src/, tests/, sandbox/ and all DMs')]
function qa_stan(
    #[AsOption(description: 'Restrict to a single DM (default: src/, tests/ and every DM)', autocomplete: 'autocomplete_apps')]
    ?string $app = null,
    #[AsArgument(description: 'Target file or directory (only src/, or --app if given)')]
    ?string $target = null,
): void {
    resolve_apps($app);

    if (null !== $app) {
        stan_exec($app, $target);

        return;
    }

    if (null !== $target) {
        stan_exec(null, $target);

        return;
    }

    stan_exec(null);

    io()->comment('tests/');
    phpstan_exec('tests/phpstan.neon', null, ['APP_ENV' => 'test', 'APP_ENV_UCFIRST' => 'Test']);

    io()->comment('sandbox/');
    phpstan_exec('phpstan.sandbox.neon', null, ['APP_ENV_UCFIRST' => ucfirst(app_env())]);

    foreach (apps() as $dm) {
        stan_exec($dm);
    }
}

/**
 * Runs PHPStan on src/ (no DM) or on one DM's own config, optionally scoped to a target.
 */
function stan_exec(?string $app, ?string $target = null): void
{
    if (null === $app) {
        $config = 'phpstan.neon';
    } else {
        $config = is_file(__DIR__."/../../apps/{$app}/phpstan.neon") ? "apps/{$app}/phpstan.neon" : 'apps/phpstan.neon';
    }

    io()->comment((null !== $app ? "DM: {$app}" : 'src/')." ({$config})");

    phpstan_exec($config, $target, [...(null !== $app ? ['APP_ID' => $app] : []), 'APP_ENV_UCFIRST' => ucfirst(app_env())]);
}

/**
 * @param array<string, string> $environment
 */
function phpstan_exec(string $config, ?string $target, array $environment): void
{
    with(
        static fn () => docker_exec([
            'vendor/bin/phpstan', 'analyse', '-c', $config,
            ...(null !== $target ? [$target] : []),
        ]),
        environment: $environment,
        context: context(),
    );
}
