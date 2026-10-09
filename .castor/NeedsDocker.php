<?php

declare(strict_types=1);

use Castor\Attribute\AsListener;
use Castor\Event\BeforeExecuteTaskEvent;
use Symfony\Component\Process\ExecutableFinder;

use function Castor\check;

#[Attribute(Attribute::TARGET_FUNCTION)]
class NeedsDocker
{
}

#[AsListener(event: BeforeExecuteTaskEvent::class)]
function ensure_docker(BeforeExecuteTaskEvent $event): void
{
    if ([] !== $event->task->getAttributes(NeedsDocker::class)) {
        check(
            'Checking Docker is installed',
            'Docker is required — install it from https://docs.docker.com/get-docker/.',
            static fn (): bool => null !== new ExecutableFinder()->find('docker'),
        );
    }
}
