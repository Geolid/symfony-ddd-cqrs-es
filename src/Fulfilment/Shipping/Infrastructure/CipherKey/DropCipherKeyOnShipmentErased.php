<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Infrastructure\CipherKey;

use Fulfilment\Shipping\Domain\Event\ShipmentErased;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use Shared\Infrastructure\Processor;

#[Processor('fulfilment.shipping.drop_cipher_key_on_shipment_erased')]
final readonly class DropCipherKeyOnShipmentErased
{
    public function __construct(private CipherKeyStore $cipherKeyStore)
    {
    }

    #[Subscribe(ShipmentErased::class)]
    public function __invoke(ShipmentErased $event): void
    {
        $this->cipherKeyStore->removeWithSubjectId($event->id);
    }
}
