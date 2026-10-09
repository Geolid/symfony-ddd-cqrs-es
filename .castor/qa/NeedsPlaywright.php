<?php

declare(strict_types=1);

use Castor\Attribute\AsListener;
use Castor\Event\BeforeExecuteTaskEvent;

#[Attribute(Attribute::TARGET_FUNCTION)]
class NeedsPlaywright
{
}

#[AsListener(event: BeforeExecuteTaskEvent::class)]
function ensure_playwright(BeforeExecuteTaskEvent $event): void
{
    if ([] !== $event->task->getAttributes(NeedsPlaywright::class)) {
        setup_playwright();
    }
}
