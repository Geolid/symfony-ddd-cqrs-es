<?php

declare(strict_types=1);

namespace Sales\Ordering\Infrastructure\CipherKey;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use Sales\Ordering\Domain\Order\Event\OrderErased;
use Shared\Infrastructure\Processor;

#[Processor('sales.ordering.drop_cipher_key_on_order_erased')]
final readonly class DropCipherKeyOnOrderErased
{
    public function __construct(private CipherKeyStore $cipherKeyStore)
    {
    }

    #[Subscribe(OrderErased::class)]
    public function __invoke(OrderErased $event): void
    {
        $this->cipherKeyStore->removeWithSubjectId($event->id);
    }
}
