<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Doctrine\Dbal;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Infrastructure\Doctrine\Dbal\TransactionMessengerMiddleware;
use Support\AbstractIntegrationTestCase;
use Support\Doubles\DummyMessage;
use Support\Doubles\StubNextMiddleware;
use Support\Doubles\StubStack;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class TransactionMessengerMiddlewareTest extends AbstractIntegrationTestCase
{
    private const string TABLE = 'dbal_transaction_middleware_test';

    private Connection $connection;
    private TransactionMessengerMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->serviceAs('doctrine.dbal.event_store_connection', Connection::class);
        $this->connection->executeStatement(\sprintf('CREATE TEMPORARY TABLE IF NOT EXISTS %s (value VARCHAR(255) NOT NULL)', self::TABLE));
        $this->middleware = new TransactionMessengerMiddleware($this->connection);
    }

    #[Test]
    public function itCommitsTheWriteOnSuccess(): void
    {
        // Given
        $value = Uuid::uuid7()->toString();
        $stack = new StubStack(new InsertThenDelegateMiddleware($this->connection, self::TABLE, $value, new StubNextMiddleware()));

        // When
        $this->middleware->handle(new Envelope(new DummyMessage()), $stack);

        // Then
        self::assertSame($value, $this->connection->fetchOne(\sprintf('SELECT value FROM %s WHERE value = ?', self::TABLE), [$value]));
    }

    #[Test]
    public function itRollsBackTheWriteWhenTheStackFails(): void
    {
        // Given
        $value = Uuid::uuid7()->toString();
        $failure = new \RuntimeException('Handler blew up.');
        $stack = new StubStack(new InsertThenDelegateMiddleware($this->connection, self::TABLE, $value, new StubNextMiddleware($failure)));

        // Then
        $this->expectExceptionObject($failure);

        // When
        try {
            $this->middleware->handle(new Envelope(new DummyMessage()), $stack);
        } finally {
            self::assertFalse($this->connection->fetchOne(\sprintf('SELECT value FROM %s WHERE value = ?', self::TABLE), [$value]));
        }
    }
}

final readonly class InsertThenDelegateMiddleware implements MiddlewareInterface
{
    public function __construct(
        private Connection $connection,
        private string $table,
        private string $value,
        private MiddlewareInterface $next,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $this->connection->insert($this->table, ['value' => $this->value]);

        return $this->next->handle($envelope, $stack);
    }
}
