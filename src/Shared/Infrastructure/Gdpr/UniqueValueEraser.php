<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Gdpr;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Message\Message;
use Shared\Application\Processor\Processor;
use Shared\Domain\Gdpr\DataSubjectErasureInterface;
use Shared\Domain\Service\UniqueValueRegistryInterface;

#[Processor('shared.gdpr.unique_value_eraser', sync: true)]
final readonly class UniqueValueEraser
{
    public function __construct(private UniqueValueRegistryInterface $uniqueValues)
    {
    }

    #[Subscribe(Subscribe::ALL)]
    public function __invoke(Message $message): void
    {
        $event = $message->event();

        if (!$event instanceof DataSubjectErasureInterface) {
            return;
        }

        $this->uniqueValues->releaseAllForSubject($event->subjectId());
    }
}
