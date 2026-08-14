<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Command\EraseCustomer;

use Psr\Clock\ClockInterface;
use Sales\Customer\Domain\Repository\CustomerRepositoryInterface;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Shared\Application\Command\AsCommandHandler;
use Shared\Domain\Exception\AggregateNotFoundException;

#[AsCommandHandler]
final readonly class EraseCustomerHandler
{
    public function __construct(
        private CustomerRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws AggregateNotFoundException
     */
    public function __invoke(EraseCustomer $command): void
    {
        $customer = $this->repository->load(CustomerId::fromString($command->id));
        $customer->erase($this->clock->now());

        $this->repository->save($customer);
    }
}
