<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Doctrine\Dbal;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
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
        $this->connection->executeStatement(\sprintf('CREATE TEMPORARY TABLE %s (value VARCHAR(255) NOT NULL)', self::TABLE));
        $this->middleware = new TransactionMessengerMiddleware($this->connection);
    }

    protected function tearDown(): void
    {
        $this->connection->executeStatement(\sprintf('DROP TEMPORARY TABLE %s', self::TABLE));

        parent::tearDown();
    }

    #[Test]
    public function itCommits(): void
    {
        // Given
        $stack = new StubStack(new StubInsertMiddleware($this->connection, self::TABLE, 'value', new StubNextMiddleware()));

        // When
        $this->middleware->handle(new Envelope(new DummyMessage()), $stack);

        // Then
        self::assertSame('value', $this->storedValue());
    }

    #[Test]
    public function itRollsBackWhenStackFails(): void
    {
        // Given
        $failure = new \RuntimeException('Handler blew up.');
        $stack = new StubStack(new StubInsertMiddleware($this->connection, self::TABLE, 'value', new StubNextMiddleware($failure)));

        // When
        $caught = null;
        try {
            $this->middleware->handle(new Envelope(new DummyMessage()), $stack);
        } catch (\RuntimeException $exception) {
            $caught = $exception;
        }

        // Then
        self::assertSame($failure, $caught);
        self::assertFalse($this->storedValue());
    }

    private function storedValue(): mixed
    {
        return $this->connection->fetchOne('SELECT value FROM '.self::TABLE);
    }
}

final readonly class StubInsertMiddleware implements MiddlewareInterface
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
