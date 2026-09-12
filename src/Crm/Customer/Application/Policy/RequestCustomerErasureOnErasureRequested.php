<?php

declare(strict_types=1);

namespace Crm\Customer\Application\Policy;

use Compliance\Erasing\Application\IntegrationEvent\ErasureRequested\ErasureRequestedIntegrationEvent;
use Crm\Customer\Application\Command\RequestCustomerErasure\RequestCustomerErasure;
use Crm\Customer\Domain\Customer\Repository\CustomerRepositoryInterface;
use Crm\Customer\Domain\Customer\ValueObject\CustomerId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('crm.customer.request_customer_erasure_on_erasure_requested')]
final readonly class RequestCustomerErasureOnErasureRequested
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
    #[Subscribe(ErasureRequestedIntegrationEvent::class)]
    public function __invoke(ErasureRequestedIntegrationEvent $event): void
    {
        $customerId = CustomerId::forIdentity($event->identityId);

        if (!$this->repository->has($customerId)) {
            return;
        }

        $this->commandBus->dispatch(new RequestCustomerErasure($customerId->toString()));
    }
}
