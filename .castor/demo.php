<?php

declare(strict_types=1);

use Castor\Attribute\AsTask;

use function Castor\with;

#[AsTask(name: 'seed', namespace: 'demo', description: 'Reset the database and seed demo orders')]
function demoSeed(): void
{
    with(static function (): void {
        dbReset();
        demoExec(['demo:seed']);
    }, environment: ['APP_ENV' => 'demo']);
}

#[AsTask(name: 'list', namespace: 'demo', description: 'List available demo commands')]
function demoList(): void
{
    with(static fn () => demoExec(['list', 'demo']), environment: ['APP_ENV' => 'demo']);
}

/**
 * @param array<string> $args
 */
function demoExec(array $args): void
{
    dockerExec(['php', 'demo/console', ...$args]);
}
