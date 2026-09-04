<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Command\RegisterCustomerBillingAddress;

use Psr\Clock\ClockInterface;
use Sales\Customer\Domain\Exception\CustomerAlreadyExistsException;
use Sales\Customer\Domain\Exception\CustomerNotFoundException;
use Sales\Customer\Domain\Repository\CustomerRepositoryInterface;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Shared\Application\Command\CommandHandler;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;

#[CommandHandler]
final readonly class RegisterCustomerBillingAddressHandler
{
    public function __construct(
        private CustomerRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws CustomerNotFoundException
     * @throws CustomerAlreadyExistsException
     */
    public function __invoke(RegisterCustomerBillingAddress $command): void
    {
        $customer = $this->repository->load(CustomerId::fromString($command->customerId));

        $customer->registerBillingAddress(
            PostalAddress::of(
                $command->recipientName,
                Address::of($command->street, $command->postalCode, $command->city, $command->countryCode),
            ),
            $this->clock->now(),
        );

        $this->repository->save($customer);
    }
}
