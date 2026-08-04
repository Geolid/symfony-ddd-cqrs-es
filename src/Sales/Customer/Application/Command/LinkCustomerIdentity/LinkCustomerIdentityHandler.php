<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Command\LinkCustomerIdentity;

use Sales\Customer\Domain\Exception\CustomerAlreadyLinkedToIdentityException;
use Sales\Customer\Domain\Exception\CustomerNotFoundException;
use Sales\Customer\Domain\Repository\CustomerRepositoryInterface;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Shared\Application\Command\AsCommandHandler;

#[AsCommandHandler]
final readonly class LinkCustomerIdentityHandler
{
    public function __construct(private CustomerRepositoryInterface $repository)
    {
    }

    /**
     * @throws CustomerNotFoundException
     * @throws CustomerAlreadyLinkedToIdentityException
     */
    public function __invoke(LinkCustomerIdentity $command): void
    {
        $customer = $this->repository->load(CustomerId::fromString($command->id));
        $customer->linkIdentity($command->identityId);

        $this->repository->save($customer);
    }
}
