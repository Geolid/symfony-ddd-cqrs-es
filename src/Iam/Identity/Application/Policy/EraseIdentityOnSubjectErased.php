<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Policy;

use Compliance\Erasure\Application\IntegrationEvent\SubjectErased\SubjectErasedIntegrationEvent;
use Iam\Identity\Application\Command\EraseIdentity\EraseIdentity;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('iam.identity.erase_identity_on_subject_erased')]
final readonly class EraseIdentityOnSubjectErased
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(SubjectErasedIntegrationEvent::class)]
    public function __invoke(SubjectErasedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new EraseIdentity($event->subjectId));
    }
}
