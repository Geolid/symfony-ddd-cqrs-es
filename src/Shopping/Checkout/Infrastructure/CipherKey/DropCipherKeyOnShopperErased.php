<?php

declare(strict_types=1);

namespace Shopping\Checkout\Infrastructure\CipherKey;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use Shared\Infrastructure\Processor;
use Shopping\Checkout\Domain\Event\ShopperErased;

#[Processor('shopping.checkout.drop_cipher_key_on_shopper_erased')]
final readonly class DropCipherKeyOnShopperErased
{
    public function __construct(private CipherKeyStore $cipherKeyStore)
    {
    }

    #[Subscribe(ShopperErased::class)]
    public function __invoke(ShopperErased $event): void
    {
        $this->cipherKeyStore->removeWithSubjectId($event->id);
    }
}
