<?php

declare(strict_types=1);

use Castor\Attribute\AsListener;
use Castor\Event\BeforeExecuteTaskEvent;

use function Castor\with;

#[Attribute(Attribute::TARGET_FUNCTION)]
class NeedsWarmup
{
}

#[AsListener(event: BeforeExecuteTaskEvent::class)]
function ensure_warmup(BeforeExecuteTaskEvent $event): void
{
    if ([] !== $event->task->getAttributes(NeedsWarmup::class)) {
        cache_warmup();
        with(static fn () => cache_warmup(), context: 'test');
    }
}
