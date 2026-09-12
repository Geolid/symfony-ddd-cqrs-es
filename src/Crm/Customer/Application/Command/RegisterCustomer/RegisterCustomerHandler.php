<?php

declare(strict_types=1);

namespace Crm\Customer\Application\Command\RegisterCustomer;

use Crm\Customer\Domain\Customer\Customer;
use Crm\Customer\Domain\Customer\Exception\CustomerAlreadyExistsException;
use Crm\Customer\Domain\Customer\Repository\CustomerRepositoryInterface;
use Crm\Customer\Domain\Customer\ValueObject\CustomerId;
use Crm\Customer\Domain\Customer\ValueObject\Email;
use Crm\Customer\Domain\Customer\ValueObject\Name;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class RegisterCustomerHandler
{
    public function __construct(
        private CustomerRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws CustomerAlreadyExistsException
     */
    public function __invoke(RegisterCustomer $command): void
    {
        $customer = Customer::register(
            id: CustomerId::forIdentity($command->identityId),
            firstName: Name::fromString($command->firstName),
            lastName: Name::fromString($command->lastName),
            email: Email::fromString($command->email),
            registeredAt: $this->clock->now(),
        );

        $this->repository->save($customer);
    }
}
