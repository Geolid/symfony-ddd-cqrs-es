<?php

declare(strict_types=1);

namespace Crm\Customer\Application\Policy;

use Compliance\Erasing\Application\IntegrationEvent\ErasureCancelled\ErasureCancelledIntegrationEvent;
use Crm\Customer\Application\Command\CancelCustomerErasure\CancelCustomerErasure;
use Crm\Customer\Domain\Customer\Repository\CustomerRepositoryInterface;
use Crm\Customer\Domain\Customer\ValueObject\CustomerId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('crm.customer.cancel_customer_erasure_on_erasure_cancelled')]
final readonly class CancelCustomerErasureOnErasureCancelled
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
    #[Subscribe(ErasureCancelledIntegrationEvent::class)]
    public function __invoke(ErasureCancelledIntegrationEvent $event): void
    {
        $customerId = CustomerId::forIdentity($event->identityId);

        if (!$this->repository->has($customerId)) {
            return;
        }

        $this->commandBus->dispatch(new CancelCustomerErasure($customerId->toString()));
    }
}
