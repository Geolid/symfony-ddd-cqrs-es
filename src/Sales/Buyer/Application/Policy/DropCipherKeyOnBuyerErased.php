<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\Policy;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Buyer\Domain\Event\BuyerErased;
use Shared\Application\CipherKey\CipherKeyDropperInterface;
use Shared\Application\Policy;

#[Policy('sales.buyer.drop_cipher_key_on_buyer_erased')]
final readonly class DropCipherKeyOnBuyerErased
{
    public function __construct(private CipherKeyDropperInterface $cipherKeyDropper)
    {
    }

    #[Subscribe(BuyerErased::class)]
    public function __invoke(BuyerErased $event): void
    {
        $this->cipherKeyDropper->drop($event->id);
    }
}
