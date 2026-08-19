<?php

declare(strict_types=1);

use Castor\Attribute\AsArgument;
use Castor\Attribute\AsTask;

#[AsTask(description: 'Rebuild a read model by replaying its events')]
function replay(#[AsArgument(description: 'Subscription id to replay')] string $id): void
{
    console(['event-sourcing:subscription:remove', "--id={$id}", '--no-interaction']);
    console(['event-sourcing:subscription:setup', "--id={$id}", '--no-interaction']);
    console(['event-sourcing:subscription:boot', "--id={$id}", '--no-interaction']);
}
