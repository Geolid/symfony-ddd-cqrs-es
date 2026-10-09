<?php

declare(strict_types=1);

use Castor\Attribute\AsTask;

use function Castor\with;

#[AsTask(name: 'seed', namespace: 'demo', description: 'Reset the database and seed demo orders')]
function demo_seed(): void
{
    with(static function (): void {
        db_reset();
        workspace_exec(['php', 'demo/console', 'demo:seed']);
    }, context: 'demo');
}

#[AsTask(name: 'list', namespace: 'demo', description: 'List available demo commands')]
function demo_list(): void
{
    with(static fn () => workspace_exec(['php', 'demo/console', 'list', 'demo']), context: 'demo');
}
