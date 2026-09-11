<?php

declare(strict_types=1);

namespace Shopping\Checkout\Infrastructure\CipherKey;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use Shared\Infrastructure\Processor;
use Shopping\Checkout\Application\Finder\CheckoutSession\CheckoutSessionFinderInterface;
use Shopping\Checkout\Domain\Event\ShopperErased;

#[Processor('shopping.checkout.drop_checkout_session_cipher_key_on_shopper_erased')]
final readonly class DropCheckoutSessionCipherKeyOnShopperErased
{
    public function __construct(
        private CheckoutSessionFinderInterface $checkoutSessionFinder,
        private CipherKeyStore $cipherKeyStore,
    ) {
    }

    #[Subscribe(ShopperErased::class)]
    public function __invoke(ShopperErased $event): void
    {
        foreach ($this->checkoutSessionFinder->byShopper($event->id) as $checkoutSession) {
            $this->cipherKeyStore->removeWithSubjectId($checkoutSession->id);
        }
    }
}
