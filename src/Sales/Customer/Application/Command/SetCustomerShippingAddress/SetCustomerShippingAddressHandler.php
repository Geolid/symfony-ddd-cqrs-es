<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Command\SetCustomerShippingAddress;

use Psr\Clock\ClockInterface;
use Sales\Customer\Domain\Exception\CustomerNotFoundException;
use Sales\Customer\Domain\Repository\CustomerAddressesRepositoryInterface;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Shared\Application\Command\AsCommandHandler;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;

#[AsCommandHandler]
final readonly class SetCustomerShippingAddressHandler
{
    public function __construct(
        private CustomerAddressesRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws CustomerNotFoundException
     */
    public function __invoke(SetCustomerShippingAddress $command): void
    {
        $customerAddresses = $this->repository->load(CustomerId::fromString($command->customerId));

        $customerAddresses->setShippingAddress(
            PostalAddress::of(
                FullName::of($command->firstName, $command->lastName),
                Address::of($command->street, $command->postalCode, $command->city),
            ),
            $this->clock->now(),
        );

        $this->repository->save($customerAddresses);
    }
}
