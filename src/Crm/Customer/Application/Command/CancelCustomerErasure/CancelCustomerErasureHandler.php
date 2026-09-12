<?php

declare(strict_types=1);

namespace Crm\Customer\Application\Command\CancelCustomerErasure;

use Crm\Customer\Domain\Customer\Exception\CustomerAlreadyExistsException;
use Crm\Customer\Domain\Customer\Exception\CustomerNotFoundException;
use Crm\Customer\Domain\Customer\Repository\CustomerRepositoryInterface;
use Crm\Customer\Domain\Customer\ValueObject\CustomerId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class CancelCustomerErasureHandler
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
    public function __invoke(CancelCustomerErasure $command): void
    {
        $customer = $this->repository->load(CustomerId::fromString($command->id));
        $customer->cancelErasure($this->clock->now());
        $this->repository->save($customer);
    }
}
