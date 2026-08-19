<?php

declare(strict_types=1);

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;
use Castor\Fingerprint\FileHashStrategy;
use Symfony\Component\Console\Input\InputOption;

use function Castor\fingerprint;
use function Castor\hasher;
use function Castor\io;

#[AsTask(description: 'Install PHP dependencies')]
function vendor(
    #[AsArgument(description: 'Composer package to require, e.g. symfony/foo (omit to run composer install)')]
    ?string $package = null,
    #[AsOption(mode: InputOption::VALUE_NONE, description: 'Require as a dev dependency')]
    ?bool $dev = null,
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

#[AsTask(description: 'Install bundle and AssetMapper assets')]
function assets(
    #[AsArgument(description: 'Restrict to a single DM (default: all)', autocomplete: 'autocompleteApps')]
    ?string $app = null,
): void {
    foreach (resolveApps($app) as $app) {
        io()->comment("DM: {$app}");

        console(['assets:install', 'public/', '--no-cleanup', "--appId={$app}"]);

        if (is_file(__DIR__."/../apps/{$app}/importmap.php")) {
            console(['importmap:install', "--appId={$app}"]);
        }
    }
}

#[AsTask(description: 'Warmup cache for all contexts — shared and all DMs')]
function warmup(): void
{
    io()->comment('shared');
    console(['cache:warmup']);

    foreach (apps() as $app) {
        io()->comment("DM: {$app}");
        console(['cache:warmup', "--appId={$app}"]);
    }
}

#[AsTask(description: 'Install git hooks (CaptainHook)')]
function hooks(): void
{
    dockerExec(['vendor/bin/captainhook', 'install', '-f', '-n']);
}
