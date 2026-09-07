<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\Policy;

use Compliance\Erasure\Application\CipherKey\CipherKeyDropperInterface;
use Compliance\Erasure\Domain\Event\SubjectErased;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Policy;

#[Policy('compliance.erasure.drop_cipher_key_on_subject_erased')]
final readonly class DropCipherKeyOnSubjectErased
{
    public function __construct(private CipherKeyDropperInterface $cipherKeyDropper)
    {
    }

    #[Subscribe(SubjectErased::class)]
    public function __invoke(SubjectErased $event): void
    {
        $this->cipherKeyDropper->drop($event->id);
    }
}
