<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\RevokeApiTokenCredentialsForIdentity;

use Iam\Identity\Application\Command\RevokeApiTokenCredential\RevokeApiTokenCredential;
use Iam\Identity\Application\Finder\ApiTokenCredential\ApiTokenCredentialFinderInterface;
use Shared\Application\Command\AsCommandHandler;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;

#[AsCommandHandler]
final readonly class RevokeApiTokenCredentialsForIdentityHandler
{
    public function __construct(
        private ApiTokenCredentialFinderInterface $finder,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function __invoke(RevokeApiTokenCredentialsForIdentity $command): void
    {
        foreach ($this->finder->byIdentity($command->identityId)->active() as $credential) {
            $this->commandBus->dispatch(new RevokeApiTokenCredential($credential->id));
        }
    }
}
