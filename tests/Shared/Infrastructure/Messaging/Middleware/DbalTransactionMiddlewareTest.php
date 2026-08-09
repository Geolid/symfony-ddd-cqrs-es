<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Messaging\Middleware;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
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
    private const string TABLE = 'dbal_transaction_middleware_test';

    private Connection $connection;
    private DbalTransactionMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->serviceAs('doctrine.dbal.event_store_connection', Connection::class);
        $this->connection->executeStatement(\sprintf('CREATE TEMPORARY TABLE IF NOT EXISTS %s (value VARCHAR(255) NOT NULL)', self::TABLE));
        $this->middleware = new DbalTransactionMiddleware($this->connection);
    }

    #[Test]
    public function itCommitsTheWriteOnSuccess(): void
    {
        // Given
        $value = Uuid::uuid7()->toString();
        $stack = new DummyStack(new InsertThenDelegateMiddleware($this->connection, self::TABLE, $value, new DummyNextMiddleware()));

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
        $stack = new DummyStack(new InsertThenDelegateMiddleware($this->connection, self::TABLE, $value, new DummyNextMiddleware($failure)));

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
