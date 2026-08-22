<?php

declare(strict_types=1);

namespace Cli\Console;

use Cli\Console\Input\RegisterIdentityInput;
use Iam\Authentication\Application\Credential\ApiKeyIssuerInterface;
use Iam\Identity\Application\Command\RegisterIdentity\RegisterIdentity;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\MapInput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'iam:identity:register', description: 'Bootstrap an Identity with an API key (chicken-and-egg admin setup)')]
final class RegisterIdentityCommand
{
    use LockableTrait;

    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiKeyIssuerInterface $apiKeyIssuer,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function __invoke(SymfonyStyle $io, #[MapInput] RegisterIdentityInput $input): int
    {
        if (!$this->lock()) {
            $io->warning('The command is already running in another process.');

            return Command::SUCCESS;
        }

        try {
            $identityId = Uuid::uuid7()->toString();

            $this->commandBus->dispatch(new RegisterIdentity($identityId));

            $apiKey = $this->apiKeyIssuer->issueFor($identityId, $input->label);

            $io->writeln(\sprintf('API key (shown once, store it securely): %s.%s', $apiKey->keyId, $apiKey->secret));
            $io->success(\sprintf('Identity %s registered.', $identityId));
        } finally {
            $this->release();
        }

        return Command::SUCCESS;
    }
}
