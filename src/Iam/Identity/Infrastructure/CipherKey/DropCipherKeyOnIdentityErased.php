<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\CipherKey;

use Iam\Identity\Domain\Event\IdentityErased;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use Shared\Infrastructure\Processor;

#[Processor('iam.identity.drop_cipher_key_on_identity_erased')]
final readonly class DropCipherKeyOnIdentityErased
{
    public function __construct(private CipherKeyStore $cipherKeyStore)
    {
    }

    #[Subscribe(IdentityErased::class)]
    public function __invoke(IdentityErased $event): void
    {
        $this->cipherKeyStore->removeWithSubjectId($event->id);
    }
}
