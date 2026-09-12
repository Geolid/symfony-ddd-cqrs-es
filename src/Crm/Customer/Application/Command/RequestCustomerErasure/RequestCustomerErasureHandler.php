<?php

declare(strict_types=1);

namespace Crm\Customer\Application\Command\RequestCustomerErasure;

use Crm\Customer\Domain\Customer\Exception\CustomerAlreadyExistsException;
use Crm\Customer\Domain\Customer\Exception\CustomerNotFoundException;
use Crm\Customer\Domain\Customer\Repository\CustomerRepositoryInterface;
use Crm\Customer\Domain\Customer\ValueObject\CustomerId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class RequestCustomerErasureHandler
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
    public function __invoke(RequestCustomerErasure $command): void
    {
        $customer = $this->repository->load(CustomerId::fromString($command->id));
        $customer->requestErasure($this->clock->now());
        $this->repository->save($customer);
    }
}
