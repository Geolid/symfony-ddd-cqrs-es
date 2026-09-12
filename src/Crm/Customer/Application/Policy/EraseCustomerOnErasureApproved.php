<?php

declare(strict_types=1);

namespace Crm\Customer\Application\Policy;

use Compliance\Erasing\Application\IntegrationEvent\ErasureApproved\ErasureApprovedIntegrationEvent;
use Crm\Customer\Application\Command\EraseCustomer\EraseCustomer;
use Crm\Customer\Domain\Customer\Repository\CustomerRepositoryInterface;
use Crm\Customer\Domain\Customer\ValueObject\CustomerId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('crm.customer.erase_customer_on_erasure_approved')]
final readonly class EraseCustomerOnErasureApproved
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
    #[Subscribe(ErasureApprovedIntegrationEvent::class)]
    public function __invoke(ErasureApprovedIntegrationEvent $event): void
    {
        $customerId = CustomerId::forIdentity($event->identityId);

        if (!$this->repository->has($customerId)) {
            return;
        }

        $this->commandBus->dispatch(new EraseCustomer($customerId->toString()));
    }
}
