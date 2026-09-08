<?php

declare(strict_types=1);

namespace Sales\Buyer\Infrastructure\CipherKey;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use Sales\Buyer\Domain\Event\BuyerErased;
use Shared\Infrastructure\Processor;

#[Processor('sales.buyer.drop_cipher_key_on_buyer_erased')]
final readonly class DropCipherKeyOnBuyerErased
{
    public function __construct(private CipherKeyStore $cipherKeyStore)
    {
    }

    #[Subscribe(BuyerErased::class)]
    public function __invoke(BuyerErased $event): void
    {
        $this->cipherKeyStore->removeWithSubjectId($event->id);
    }
}
