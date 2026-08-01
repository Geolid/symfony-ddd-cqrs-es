<?php

declare(strict_types=1);

namespace Cli\Console;

use Cli\Console\Input\PlaceOrderInput;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\PlaceOrder\PlaceOrder;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\MapInput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'sales:order:place', description: 'Place an Order from the command line (demo/local seeding)')]
final class PlaceOrderCommand
{
    use LockableTrait;

    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function __invoke(SymfonyStyle $io, #[MapInput] PlaceOrderInput $input): int
    {
        if (!$this->lock()) {
            $io->warning('The command is already running in another process.');

            return Command::SUCCESS;
        }

        try {
            $id = Uuid::uuid7()->toString();

            $this->commandBus->dispatch(new PlaceOrder(
                id: $id,
                customerId: $input->customerId,
                lines: self::toLines($input->line),
            ));

            $io->success(\sprintf('Order %s placed.', $id));
        } finally {
            $this->release();
        }

        return Command::SUCCESS;
    }

    /**
     * @param list<string> $specifications
     *
     * @return list<array{label: string, quantity: int, unitAmountInCents: int}>
     */
    private static function toLines(array $specifications): array
    {
        return array_values(array_map(
            static function (string $specification): array {
                if (1 !== preg_match(PlaceOrderInput::LINE_PATTERN, $specification, $line)) {
                    throw new \InvalidArgumentException(\sprintf('An order line is formatted "<label>:<quantity>:<unit amount in euros, e.g. 17.50>", "%s" given.', $specification));
                }

                return [
                    'label' => $line['label'],
                    'quantity' => (int) $line['quantity'],
                    'unitAmountInCents' => ((int) $line['euros']) * 100 + (int) ($line['cents'] ?? 0),
                ];
            },
            $specifications,
        ));
    }
}
