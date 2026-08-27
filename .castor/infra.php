<?php

declare(strict_types=1);

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;
use Castor\Fingerprint\FileHashStrategy;
use Symfony\Component\Console\Input\InputOption;

use function Castor\context;
use function Castor\fingerprint;
use function Castor\hasher;
use function Castor\io;
use function Castor\with;

#[AsTask(description: 'Install PHP dependencies')]
function vendor(
    #[AsArgument(description: 'Composer package to require, e.g. symfony/foo (omit to run composer install)')]
    ?string $package = null,
    #[AsOption(mode: InputOption::VALUE_NONE, description: 'Require as a dev dependency')]
    ?bool $dev = null,
): void {
    if (null !== $package) {
        compose_exec(['composer', 'require', ...($dev ? ['--dev'] : []), $package]);
    } else {
        fingerprint(
            callback: static fn () => compose_exec(['composer', 'install', '--prefer-dist', '--no-progress', '--no-interaction']),
            id: 'vendor',
            fingerprint: hasher()->writeFile('composer.lock', FileHashStrategy::Content)->finish(),
        );
    }

    assets();
    warmup();
}

#[AsTask(description: 'Install bundle and AssetMapper assets')]
function assets(
    #[AsArgument(description: 'Restrict to a single DM (default: all)', autocomplete: 'autocomplete_apps')]
    ?string $app = null,
): void {
    foreach (resolve_apps($app) as $app) {
        io()->comment("DM: {$app}");

        console(['assets:install', 'public/', '--no-cleanup', "--appId={$app}"]);

        if (is_file(__DIR__."/../apps/{$app}/importmap.php")) {
            console(['importmap:install', "--appId={$app}"]);
        }
    }
}

#[AsTask(description: 'Warmup all caches')]
function warmup(
    #[AsArgument(description: 'Restrict to a single DM (default: all)', autocomplete: 'autocomplete_apps')]
    ?string $app = null,
): void {
    // Forced: Symfony only dumps the container XML phpstan-symfony needs when debug=true.
    io()->comment('shared');
    with(static fn () => console(['cache:warmup']), environment: ['APP_DEBUG' => '1'], context: context());

    foreach (resolve_apps($app) as $app) {
        io()->comment("DM: {$app}");
        with(static fn () => console(['cache:warmup', "--appId={$app}"]), environment: ['APP_DEBUG' => '1'], context: context());
    }
}

#[AsTask(description: 'Install git hooks (CaptainHook)')]
function hooks(): void
{
    compose_exec(['vendor/bin/captainhook', 'install', '-f', '-n']);
}
