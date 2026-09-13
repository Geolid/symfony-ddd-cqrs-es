<?php

declare(strict_types=1);

namespace Shopping\Checkout\Infrastructure\CipherKey;

use Crm\Customer\Application\IntegrationEvent\CustomerErased\CustomerErasedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use Shared\Infrastructure\Processor;
use Shopping\Checkout\Application\Finder\CheckoutSession\CheckoutSessionFinderInterface;

#[Processor('shopping.checkout.drop_checkout_session_cipher_key_on_customer_erased')]
final readonly class DropCheckoutSessionCipherKeyOnCustomerErased
{
    public function __construct(
        private CheckoutSessionFinderInterface $checkoutSessionFinder,
        private CipherKeyStore $cipherKeyStore,
    ) {
    }

    #[Subscribe(CustomerErasedIntegrationEvent::class)]
    public function __invoke(CustomerErasedIntegrationEvent $event): void
    {
        foreach ($this->checkoutSessionFinder->byCustomer($event->customerId) as $checkoutSession) {
            $this->cipherKeyStore->removeWithSubjectId($checkoutSession->id);
        }
    }
}
