<?php

declare(strict_types=1);

namespace Crm\Customer\Application\Command\DefineCustomerBillingAddress;

use Crm\Customer\Domain\Customer\Exception\CustomerAlreadyExistsException;
use Crm\Customer\Domain\Customer\Exception\CustomerNotFoundException;
use Crm\Customer\Domain\Customer\Repository\CustomerRepositoryInterface;
use Crm\Customer\Domain\Customer\ValueObject\CustomerId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Mapper\PostalAddressMapper;

#[CommandHandler]
final readonly class DefineCustomerBillingAddressHandler
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
    public function __invoke(DefineCustomerBillingAddress $command): void
    {
        $customer = $this->repository->load(CustomerId::fromString($command->customerId));

        $customer->defineBillingAddress(
            PostalAddressMapper::fromArray($command->billingAddress),
            $this->clock->now(),
        );

        $this->repository->save($customer);
    }
}
