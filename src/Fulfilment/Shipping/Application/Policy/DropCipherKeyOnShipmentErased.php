<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Policy;

use Fulfilment\Shipping\Domain\Event\ShipmentErased;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\CipherKey\CipherKeyDropperInterface;
use Shared\Application\Policy;

#[Policy('fulfilment.shipping.drop_cipher_key_on_shipment_erased')]
final readonly class DropCipherKeyOnShipmentErased
{
    public function __construct(private CipherKeyDropperInterface $cipherKeyDropper)
    {
    }

    #[Subscribe(ShipmentErased::class)]
    public function __invoke(ShipmentErased $event): void
    {
        $this->cipherKeyDropper->drop($event->id);
    }
}
