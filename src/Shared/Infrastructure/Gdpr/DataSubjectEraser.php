<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Gdpr;

use Patchlevel\EventSourcing\Attribute\Processor;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use Shared\Domain\Gdpr\DataSubjectErasureInterface;

/**
 * Crypto-shredding: dropping the subject's cipher key makes every `#[PersonalData]` field ever
 * encrypted under it decrypt to its declared fallback, so an append-only store forgets without
 * rewriting a single event.
 */
#[Processor('shared.gdpr.data_subject_eraser')]
final readonly class DataSubjectEraser
{
    public function __construct(private CipherKeyStore $cipherKeyStore)
    {
    }

    #[Subscribe(Subscribe::ALL)]
    public function onEvent(Message $message): void
    {
        $event = $message->event();

        if (!$event instanceof DataSubjectErasureInterface) {
            return;
        }

        $this->cipherKeyStore->removeWithSubjectId($event->subjectId());
    }
}
