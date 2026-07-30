<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Gdpr;

use Patchlevel\EventSourcing\Attribute\Processor;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use Shared\Domain\Gdpr\DataSubjectErasureInterface;

/**
 * Crypto-shredding: on a DataSubjectErasureInterface event, drops the subject's
 * cipher key so every #[PersonalData] field encrypted under it decrypts to its fallback.
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
