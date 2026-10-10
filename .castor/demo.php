<?php

declare(strict_types=1);

use Castor\Attribute\AsTask;

use function Castor\with;

#[AsTask(name: 'fixtures', namespace: 'demo', description: 'Reset the database and load the demo fixtures')]
function demo_fixtures(): void
{
    with(static function (): void {
        db_reset();
        console(['foundry:load-fixtures', 'demo', '--no-interaction']);
    }, context: 'demo');
}
