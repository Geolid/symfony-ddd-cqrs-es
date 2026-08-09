<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Messaging\Middleware;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Shared\Infrastructure\Messaging\Middleware\DbalTransactionMiddleware;
use Support\AbstractIntegrationTestCase;
use Support\Stub\DummyMessage;
use Support\Stub\DummyNextMiddleware;
use Support\Stub\DummyStack;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class DbalTransactionMiddlewareTest extends AbstractIntegrationTestCase
{
    private UniqueValueRegistryInterface $uniqueValues;
    private DbalTransactionMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uniqueValues = $this->service(UniqueValueRegistryInterface::class);
        $connection = $this->serviceAs('doctrine.dbal.event_store_connection', Connection::class);
        $this->middleware = new DbalTransactionMiddleware($connection);
    }

    #[Test]
    public function itCommitsTheWriteOnSuccess(): void
    {
        // Given
        $value = Uuid::uuid7()->toString();
        $stack = new DummyStack(new ReserveThenDelegateMiddleware($this->uniqueValues, $value, new DummyNextMiddleware()));

        // When
        $this->middleware->handle(new Envelope(new DummyMessage()), $stack);

        // Then
        self::assertTrue($this->uniqueValues->exists(DummyUniqueValueType::TEST, $value));
    }

    #[Test]
    public function itRollsBackTheWriteWhenTheStackFails(): void
    {
        // Given
        $value = Uuid::uuid7()->toString();
        $failure = new \RuntimeException('Handler blew up.');
        $stack = new DummyStack(new ReserveThenDelegateMiddleware($this->uniqueValues, $value, new DummyNextMiddleware($failure)));

        // Then
        $this->expectExceptionObject($failure);

        // When
        try {
            $this->middleware->handle(new Envelope(new DummyMessage()), $stack);
        } finally {
            self::assertFalse($this->uniqueValues->exists(DummyUniqueValueType::TEST, $value));
        }
    }
}

enum DummyUniqueValueType: string
{
    case TEST = 'shared.test.dummy';
}

final readonly class ReserveThenDelegateMiddleware implements MiddlewareInterface
{
    public function __construct(
        private UniqueValueRegistryInterface $uniqueValues,
        private string $value,
        private MiddlewareInterface $next,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $this->uniqueValues->reserve(DummyUniqueValueType::TEST, $this->value);

        return $this->next->handle($envelope, $stack);
    }
}
