<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Messaging;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Application\Query\QueryInterface;
use Shared\Infrastructure\Messaging\SymfonyQueryBus;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

final class SymfonyQueryBusTest extends TestCase
{
    #[Test]
    public function itAsks(): void
    {
        // Given
        $expected = new \stdClass();
        $asked = null;
        $bus = $this->createBus(static function (QueryInterface $query) use (&$asked, $expected): \stdClass {
            $asked = $query;

            return $expected;
        });
        $query = new DummyQuery();

        // When
        $result = $bus->ask($query);

        // Then
        self::assertSame($query, $asked);
        self::assertSame($expected, $result);
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
            $bus->ask(new DummyQuery());
        } catch (\DomainException $caught) {
        }

        // Then
        self::assertSame($domainException, $caught);
    }

    private function createBus(callable $handler): SymfonyQueryBus
    {
        return new SymfonyQueryBus(new MessageBus([
            new HandleMessageMiddleware(new HandlersLocator([
                DummyQuery::class => [$handler],
            ])),
        ]));
    }
}

/**
 * @implements QueryInterface<\stdClass>
 */
final class DummyQuery implements QueryInterface
{
}
