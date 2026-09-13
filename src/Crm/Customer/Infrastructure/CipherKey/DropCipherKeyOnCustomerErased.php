<?php

declare(strict_types=1);

namespace Crm\Customer\Infrastructure\CipherKey;

use Crm\Customer\Domain\Customer\Event\CustomerErased;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use Shared\Infrastructure\Processor;

#[Processor('crm.customer.drop_cipher_key_on_customer_erased')]
final readonly class DropCipherKeyOnCustomerErased
{
    public function __construct(private CipherKeyStore $cipherKeyStore)
    {
    }

    #[Subscribe(CustomerErased::class)]
    public function __invoke(CustomerErased $event): void
    {
        $this->cipherKeyStore->removeWithSubjectId($event->id->toString());
    }
}
