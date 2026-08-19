<?php

declare(strict_types=1);

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;

use function Castor\context;
use function Castor\with;

#[AsTask(name: 'stan', namespace: 'qa', description: 'Run static analysis on src/, tests/ and all DMs (optional: --app=<dm>, default: all)')]
function qaStan(
    #[AsOption(description: 'Restrict to a single DM (default: src/, tests/ and every DM)', autocomplete: 'autocompleteApps')]
    ?string $app = null,
    #[AsArgument(description: 'Target file or directory (only src/, or --app if given)')]
    ?string $target = null,
): void {
    resolveApps($app);

    if (null !== $app) {
        stanExec($app, $target);

        return;
    }

    if (null !== $target) {
        stanExec(null, $target);

        return;
    }

    stanExec(null);
    with(
        static fn () => dockerExec(['vendor/bin/phpstan', 'analyse', '-c', 'tests/phpstan.neon']),
        environment: ['APP_ENV' => 'test', 'APP_ENV_UCFIRST' => 'Test'],
    );
    foreach (apps() as $dm) {
        stanExec($dm);
    }
}

/**
 * Runs PHPStan on src/ (no DM) or on one DM's own config, optionally scoped to a target.
 */
function stanExec(?string $app, ?string $target = null): void
{
    if (null === $app) {
        $config = 'phpstan.neon';
    } else {
        $config = is_file(__DIR__."/../../apps/{$app}/phpstan.neon") ? "apps/{$app}/phpstan.neon" : 'apps/phpstan.neon';
    }

    with(
        static fn () => dockerExec([
            'vendor/bin/phpstan', 'analyse', '-c', $config,
            ...(null !== $target ? [$target] : []),
        ]),
        environment: [...(null !== $app ? ['APP_ID' => $app] : []), 'APP_ENV_UCFIRST' => ucfirst(appEnv())],
        context: context(),
    );
}
