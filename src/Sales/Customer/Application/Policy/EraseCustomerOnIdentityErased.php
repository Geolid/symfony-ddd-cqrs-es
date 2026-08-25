<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Policy;

use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Customer\Application\Command\EraseCustomer\EraseCustomer;
use Sales\Customer\Domain\Repository\CustomerRepositoryInterface;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy\Policy;

#[Policy('sales.customer.erase_customer_on_identity_erased')]
final readonly class EraseCustomerOnIdentityErased
{
    public function __construct(
        private CustomerRepositoryInterface $repository,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(IdentityErasedIntegrationEvent::class)]
    public function __invoke(IdentityErasedIntegrationEvent $event): void
    {
        if (!$this->repository->has(CustomerId::fromString($event->identityId))) {
            return;
        }

        $this->commandBus->dispatch(new EraseCustomer($event->identityId));
    }
}
