<?php

declare(strict_types=1);

namespace Demo\Sales;

use Demo\Sales\Input\SeedCustomersInput;
use Iam\Identity\Application\Command\RegisterIdentity\RegisterIdentity;
use Iam\Identity\Application\Command\SetPasswordCredential\SetPasswordCredential;
use Ramsey\Uuid\Uuid;
use Sales\Customer\Application\Command\LinkCustomerIdentity\LinkCustomerIdentity;
use Sales\Customer\Application\Command\RegisterCustomer\RegisterCustomer;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Shared\Application\Command\CommandBusInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\MapInput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'demo:sales:customers', description: 'Seed customers')]
final readonly class SeedCustomersCommand
{
    private const string DEMO_PASSWORD = 'password';

    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[MapInput]
        SeedCustomersInput $input,
    ): int {
        $io->progressStart($input->count);

        for ($i = 1; $i <= $input->count; ++$i) {
            $customerId = CustomerId::generate()->toString();
            $login = \sprintf('buyer-%d', $i);
            $email = \sprintf('buyer-%d@%s', $i, $input->domain);
            $identityId = Uuid::uuid7()->toString();

            $this->commandBus->dispatch(new RegisterCustomer($customerId, $email));
            $this->commandBus->dispatch(new RegisterIdentity($identityId));
            $this->commandBus->dispatch(new SetPasswordCredential($identityId, $login, self::DEMO_PASSWORD));
            $this->commandBus->dispatch(new LinkCustomerIdentity($customerId, $identityId));

            $io->progressAdvance();
        }

        $io->progressFinish();
        $io->success(\sprintf('%d customer(s) seeded. Log in with buyer-<n> / %s.', $input->count, self::DEMO_PASSWORD));

        return Command::SUCCESS;
    }
}
