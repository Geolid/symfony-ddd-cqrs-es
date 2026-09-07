<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\Policy;

use Compliance\Erasure\Application\Command\RegisterSubject\RegisterSubject;
use Iam\Identity\Application\IntegrationEvent\IdentityRegistered\IdentityRegisteredIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('compliance.erasure.register_subject_on_identity_registered')]
final readonly class RegisterSubjectOnIdentityRegistered
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(IdentityRegisteredIntegrationEvent::class)]
    public function __invoke(IdentityRegisteredIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new RegisterSubject(
            id: $event->identityId,
            registeredAt: $event->registeredAt,
        ));
    }
}
