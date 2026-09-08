<?php

declare(strict_types=1);

namespace Sales\Order\Application\Policy;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Domain\Event\OrderErased;
use Shared\Application\CipherKey\CipherKeyDropperInterface;
use Shared\Application\Policy;

#[Policy('sales.order.drop_cipher_key_on_order_erased')]
final readonly class DropCipherKeyOnOrderErased
{
    public function __construct(private CipherKeyDropperInterface $cipherKeyDropper)
    {
    }

    #[Subscribe(OrderErased::class)]
    public function __invoke(OrderErased $event): void
    {
        $this->cipherKeyDropper->drop($event->id);
    }
}
