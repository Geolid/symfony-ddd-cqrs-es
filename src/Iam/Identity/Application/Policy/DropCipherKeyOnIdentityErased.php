<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Policy;

use Iam\Identity\Domain\Event\IdentityErased;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\CipherKey\CipherKeyDropperInterface;
use Shared\Application\Policy;

#[Policy('iam.identity.drop_cipher_key_on_identity_erased')]
final readonly class DropCipherKeyOnIdentityErased
{
    public function __construct(private CipherKeyDropperInterface $cipherKeyDropper)
    {
    }

    #[Subscribe(IdentityErased::class)]
    public function __invoke(IdentityErased $event): void
    {
        $this->cipherKeyDropper->drop($event->id);
    }
}
