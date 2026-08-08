<?php

declare(strict_types=1);

namespace Cli\Console;

use Cli\Console\Input\RegisterIdentityInput;
use Iam\Access\Application\Command\GrantPermission\GrantPermission;
use Iam\Identity\Application\Command\IssueApiTokenCredential\IssueApiTokenCredential;
use Iam\Identity\Application\Command\RegisterIdentity\RegisterIdentity;
use Iam\Identity\Application\Security\ApiKeyGeneratorInterface;
use Psr\Clock\ClockInterface;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\MapInput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'iam:identity:register', description: 'Bootstrap an Identity with an API key and grants (chicken-and-egg admin setup)')]
final class RegisterIdentityCommand
{
    use LockableTrait;

    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ApiKeyGeneratorInterface $apiKeyGenerator,
        private readonly ClockInterface $clock,
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
            $expiresAt = $this->clock->now()->modify(\sprintf('+%d days', $input->expiresInDays));

            $this->commandBus->dispatch(new RegisterIdentity($identityId));

            $apiKey = $this->apiKeyGenerator->generate();
            $this->commandBus->dispatch(new IssueApiTokenCredential(
                id: Uuid::uuid7()->toString(),
                identityId: $identityId,
                identifier: $apiKey->identifier,
                secret: $apiKey->secret,
                expiresAt: $expiresAt->format(\DateTimeInterface::ATOM),
            ));

            foreach ($input->permission as $permission) {
                $this->commandBus->dispatch(new GrantPermission($identityId, $permission));
            }

            $io->success(\sprintf('Identity %s registered.', $identityId));
            $io->writeln(\sprintf('API key (shown once, store it securely): %s.%s', $apiKey->identifier, $apiKey->secret));
        } finally {
            $this->release();
        }

        return Command::SUCCESS;
    }
}
