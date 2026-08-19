<?php

declare(strict_types=1);

use Castor\Attribute\AsTask;

use function Castor\with;

#[AsTask(name: 'seed', namespace: 'demo', description: 'Reset the database and seed demo orders')]
function demo_seed(): void
{
    with(static function (): void {
        db_reset();
        demo_exec(['demo:seed']);
    }, environment: ['APP_ENV' => 'demo']);
}

#[AsTask(name: 'list', namespace: 'demo', description: 'List available demo commands')]
function demo_list(): void
{
    with(static fn () => demo_exec(['list', 'demo']), environment: ['APP_ENV' => 'demo']);
}

/**
 * @param array<string> $args
 */
function demo_exec(array $args): void
{
    compose_exec(['php', 'demo/console', ...$args]);
}
