<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Command\RegisterCustomerBillingAddress;

use Psr\Clock\ClockInterface;
use Sales\Customer\Domain\Exception\CustomerNotFoundException;
use Sales\Customer\Domain\Repository\CustomerRepositoryInterface;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Shared\Application\Command\CommandUseCase;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;

#[CommandUseCase]
final readonly class RegisterCustomerBillingAddressHandler
{
    public function __construct(
        private CustomerRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws CustomerNotFoundException
     */
    public function __invoke(RegisterCustomerBillingAddress $command): void
    {
        $customer = $this->repository->load(CustomerId::fromString($command->customerId));

        $customer->registerBillingAddress(
            PostalAddress::of(
                FullName::of($command->firstName, $command->lastName),
                Address::of($command->street, $command->postalCode, $command->city),
            ),
            $this->clock->now(),
        );

        $this->repository->save($customer);
    }
}
