<?php

declare(strict_types=1);

namespace Finance\Payer\Application\Policy;

use Finance\Payer\Application\Command\ErasePayer\ErasePayer;
use Finance\Payer\Domain\Repository\PayerRepositoryInterface;
use Finance\Payer\Domain\ValueObject\PayerId;
use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('finance.payer.erase_payer_on_identity_erased')]
final readonly class ErasePayerOnIdentityErased
{
    public function __construct(
        private PayerRepositoryInterface $repository,
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
        if (!$this->repository->has(PayerId::fromString($event->identityId))) {
            return;
        }

        $this->commandBus->dispatch(new ErasePayer($event->identityId));
    }
}
