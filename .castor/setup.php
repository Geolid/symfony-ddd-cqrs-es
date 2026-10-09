<?php

declare(strict_types=1);

use Castor\Attribute\AsArgsAfterOptionEnd;
use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;
use Castor\Fingerprint\FileHashStrategy;

use function Castor\fingerprint;
use function Castor\fs;
use function Castor\hasher;
use function Castor\io;

#[AsTask(description: 'Install and start the full project')]
function setup(): void
{
    if (!fs()->exists(__DIR__.'/../compose.override.yaml')) {
        fs()->copy(__DIR__.'/../compose.override.yaml.dist', __DIR__.'/../compose.override.yaml');
    }

    docker_up();
    setup_vendor();
    setup_hooks();
    db_create();
}

#[AsTask(name: 'hooks', namespace: 'setup', description: 'Install git hooks (CaptainHook)')]
function setup_hooks(): void
{
    workspace_exec(['vendor/bin/captainhook', 'install', '-f', '-n']);
}

#[AsTask(name: 'vendor', namespace: 'setup', description: 'Install PHP dependencies')]
function setup_vendor(): void
{
    fingerprint(
        callback: static fn () => workspace_exec(['composer', 'install', '--no-progress', '--no-interaction']),
        id: 'vendor',
        fingerprint: hasher()->writeFile('composer.lock', FileHashStrategy::Content)->finish(),
    );

    setup_assets();
    cache_warmup();
}

/**
 * Extra args go to assets:install.
 *
 * @param list<string> $args
 */
#[AsTask(name: 'assets', namespace: 'setup', description: 'Install bundle and AssetMapper assets')]
function setup_assets(
    #[AsOption(name: 'app-id', description: 'Restrict to a single DM (default: all)', autocomplete: 'apps')]
    ?string $appId = null,
    #[AsArgsAfterOptionEnd]
    array $args = [],
): void {
    for_each_app($appId, static function (string $app) use ($args): void {
        io()->comment("DM: {$app}");

        console(['assets:install', 'public/', '--no-cleanup', ...$args]);

        if (is_file(__DIR__."/../apps/{$app}/importmap.php")) {
            console(['importmap:install']);
        }
    });
}

#[AsTask(name: 'playwright', namespace: 'setup', description: 'Install Playwright browsers')]
function setup_playwright(): void
{
    workspace_exec(['vendor/bin/playwright-install', 'chromium']);
}
