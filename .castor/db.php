<?php

declare(strict_types=1);

use Castor\Attribute\AsTask;

use function Castor\io;

#[AsTask(name: 'create', namespace: 'db', description: 'Create database and run full setup')]
function dbCreate(): void
{
    console(['event-sourcing:database:create', '--if-not-exists', '--no-interaction']);
    console(['doctrine:database:create', '--connection=read_model', '--if-not-exists', '--no-interaction']);
    console(['doctrine:database:create', '--connection=messenger', '--if-not-exists', '--no-interaction']);
    dbUpdate();
}

#[AsTask(name: 'update', namespace: 'db', description: 'Update schema, register subscriptions and boot catch-up')]
function dbUpdate(): void
{
    console(['event-sourcing:schema:update', '--force', '--no-interaction']);
    console(['event-sourcing:subscription:setup', '--no-interaction']);
    console(['event-sourcing:subscription:boot', '--no-interaction']);
    console(['messenger:setup-transports', '--no-interaction']);
}

#[AsTask(name: 'reset', namespace: 'db', description: 'Drop database and run fresh setup')]
function dbReset(): void
{
    $env = appEnv();

    if (!in_array($env, ['dev', 'demo'], true)) {
        io()->error("Refused: db:reset is destructive and only allowed when APP_ENV is dev or demo (got {$env}).");

        exit(1);
    }

    console(['event-sourcing:database:drop', '--force', '--if-exists', '--no-interaction']);
    console(['doctrine:database:drop', '--connection=read_model', '--force', '--if-exists', '--no-interaction']);
    console(['doctrine:database:drop', '--connection=messenger', '--force', '--if-exists', '--no-interaction']);
    dbCreate();
}
