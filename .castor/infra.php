<?php

declare(strict_types=1);

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;
use Castor\Fingerprint\FileHashStrategy;
use Symfony\Component\Console\Input\InputOption;

use function Castor\fingerprint;
use function Castor\hasher;

#[AsTask(description: 'Install PHP dependencies (optional: a package to require)')]
function vendor(
    #[AsOption(mode: InputOption::VALUE_NONE, description: 'Require as a dev dependency')]
    bool $dev,
    #[AsArgument(description: 'Composer package to require, e.g. symfony/foo (default: composer install)')]
    ?string $package = null,
): void {
    if (null !== $package) {
        dockerExec(['composer', 'require', ...($dev ? ['--dev'] : []), $package]);
    } else {
        fingerprint(
            callback: static fn () => dockerExec(['composer', 'install', '--prefer-dist', '--no-progress', '--no-interaction']),
            id: 'vendor',
            fingerprint: hasher()->writeFile('composer.lock', FileHashStrategy::Content)->finish(),
        );
    }

    assets();
    warmup();
}

#[AsTask(description: 'Install bundle and AssetMapper assets (optional: a DM name, default: all)')]
function assets(
    #[AsArgument(description: 'Restrict to a single DM (default: every DM)', autocomplete: 'autocompleteApps')]
    ?string $app = null,
): void {
    foreach (resolveApps($app) as $app) {
        console(['assets:install', 'public/', '--no-cleanup', "--appId={$app}"]);

        if (is_file(__DIR__."/../apps/{$app}/importmap.php")) {
            console(['importmap:install', "--appId={$app}"]);
        }
    }
}

#[AsTask(description: 'Warmup cache for all contexts — shared and all DMs')]
function warmup(): void
{
    console(['cache:warmup']);

    foreach (apps() as $app) {
        console(['cache:warmup', "--appId={$app}"]);
    }
}
