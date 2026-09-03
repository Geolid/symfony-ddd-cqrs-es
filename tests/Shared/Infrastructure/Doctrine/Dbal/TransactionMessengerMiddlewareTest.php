<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Doctrine\Dbal;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shared\Infrastructure\Doctrine\Dbal\TransactionMessengerMiddleware;
use Support\Double\DummyMessage;
use Support\Double\StubNextMiddleware;
use Support\Double\StubStack;
use Symfony\Component\Messenger\Envelope;

final class TransactionMessengerMiddlewareTest extends TestCase
{
    private Connection&MockObject $connection;
    private TransactionMessengerMiddleware $middleware;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->middleware = new TransactionMessengerMiddleware($this->connection);
    }

    #[Test]
    public function itCommits(): void
    {
        // Given
        $this->connection->expects(self::once())->method('beginTransaction');
        $this->connection->expects(self::once())->method('commit');
        $this->connection->expects(self::never())->method('rollBack');

        $envelope = new Envelope(new DummyMessage());
        $stack = new StubStack(new StubNextMiddleware());

        // When
        $result = $this->middleware->handle($envelope, $stack);

        // Then
        self::assertSame($envelope, $result);
    }

    #[Test]
    public function itRollsBackWhenStackFails(): void
    {
        // Given
        $failure = new \RuntimeException('Failed');
        $this->connection->expects(self::once())->method('beginTransaction');
        $this->connection->expects(self::never())->method('commit');
        $this->connection->expects(self::once())->method('rollBack');

        $stack = new StubStack(new StubNextMiddleware($failure));

        // When
        $caught = null;
        try {
            $this->middleware->handle(new Envelope(new DummyMessage()), $stack);
        } catch (\RuntimeException $caught) {
        }

        // Then
        self::assertSame($failure, $caught);
    }
}
