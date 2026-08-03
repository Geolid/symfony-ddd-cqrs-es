<?php

declare(strict_types=1);

namespace Cli\Console;

use Cli\Console\Input\RegisterIdentityInput;
use Iam\Access\Application\Command\GrantPermission\GrantPermission;
use Iam\Identity\Application\Command\IssueApiTokenCredential\IssueApiTokenCredential;
use Iam\Identity\Application\Command\RegisterIdentity\RegisterIdentity;
use Psr\Clock\ClockInterface;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\MapInput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'iam:identity:register', description: 'Bootstrap an Identity with an API key and grants (chicken-and-egg admin setup, demo/local seeding)')]
final class RegisterIdentityCommand
{
    use LockableTrait;

    public function __construct(
        private readonly CommandBusInterface $commandBus,
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
            $identifier = 'key_'.bin2hex(random_bytes(8));
            $secret = bin2hex(random_bytes(32));

            $this->commandBus->dispatch(new RegisterIdentity($identityId));
            $this->commandBus->dispatch(new IssueApiTokenCredential(
                id: Uuid::uuid7()->toString(),
                identityId: $identityId,
                identifier: $identifier,
                secret: $secret,
                expiresAt: $this->clock->now()->modify(\sprintf('+%d days', $input->expiresInDays))->format('c'),
            ));

            foreach ($input->permission as $permission) {
                $this->commandBus->dispatch(new GrantPermission(Uuid::uuid7()->toString(), $identityId, $permission));
            }

            $io->success(\sprintf('Identity %s registered.', $identityId));
            $io->writeln(\sprintf('API key (shown once, store it securely): %s.%s', $identifier, $secret));
        } finally {
            $this->release();
        }

        return Command::SUCCESS;
    }
}
