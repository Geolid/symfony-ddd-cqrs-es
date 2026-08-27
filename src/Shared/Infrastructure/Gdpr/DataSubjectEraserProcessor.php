<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Gdpr;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use Shared\Application\Processor\Processor;
use Shared\Domain\Gdpr\DataSubjectErasureInterface;

#[Processor('shared.gdpr.data_subject_eraser')]
final readonly class DataSubjectEraserProcessor
{
    public function __construct(private CipherKeyStore $cipherKeyStore)
    {
    }

    #[Subscribe(Subscribe::ALL)]
    public function __invoke(Message $message): void
    {
        $event = $message->event();

        if (!$event instanceof DataSubjectErasureInterface) {
            return;
        }

        $this->cipherKeyStore->removeWithSubjectId($event->subjectId());
    }
}
