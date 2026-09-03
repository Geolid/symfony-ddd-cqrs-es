<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Messaging;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Application\Command\CommandInterface;
use Shared\Infrastructure\Messaging\SymfonyCommandBus;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

final class SymfonyCommandBusTest extends TestCase
{
    #[Test]
    public function itDispatches(): void
    {
        // Given
        $dispatched = null;
        $bus = $this->createBus(static function (CommandInterface $command) use (&$dispatched): void {
            $dispatched = $command;
        });
        $command = new DummyCommand();

        // When
        $bus->dispatch($command);

        // Then
        self::assertSame($command, $dispatched);
    }

    #[Test]
    public function itUnwraps(): void
    {
        // Given
        $domainException = new \DomainException('Failed');
        $bus = $this->createBus(static function () use ($domainException): never {
            throw $domainException;
        });

        // When
        $caught = null;
        try {
            $bus->dispatch(new DummyCommand());
        } catch (\DomainException $caught) {
        }

        // Then
        self::assertSame($domainException, $caught);
    }

    private function createBus(callable $handler): SymfonyCommandBus
    {
        return new SymfonyCommandBus(new MessageBus([
            new HandleMessageMiddleware(new HandlersLocator([
                DummyCommand::class => [$handler],
            ])),
        ]));
    }
}

final class DummyCommand implements CommandInterface
{
}
