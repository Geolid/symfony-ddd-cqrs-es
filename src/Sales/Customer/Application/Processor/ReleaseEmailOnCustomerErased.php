<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Processor;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Customer\Domain\Event\CustomerErased;
use Sales\Customer\Domain\Exception\CustomerNotFoundException;
use Sales\Customer\Domain\Repository\CustomerRepositoryInterface;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Sales\Customer\Domain\ValueObject\CustomerUniqueValue;
use Shared\Application\Processor\Processor;
use Shared\Domain\Service\UniqueValueRegistryInterface;

#[Processor('sales.customer.release_email_on_customer_erased', sync: true)]
final readonly class ReleaseEmailOnCustomerErased
{
    public function __construct(
        private CustomerRepositoryInterface $repository,
        private UniqueValueRegistryInterface $uniqueValues,
    ) {
    }

    /**
     * @throws CustomerNotFoundException
     */
    #[Subscribe(CustomerErased::class)]
    public function __invoke(CustomerErased $event): void
    {
        $customer = $this->repository->load(CustomerId::fromString($event->id));

        $this->uniqueValues->release(CustomerUniqueValue::EMAIL, $customer->email()->fingerprint());
    }
}
