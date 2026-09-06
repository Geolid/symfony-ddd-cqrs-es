<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Locking;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Shared\Infrastructure\Locking\Exception\LockNotAcquiredException;
use Shared\Infrastructure\Locking\LockingTrait;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;

final class LockingTraitTest extends TestCase
{
    private LockFactory&Stub $lockFactory;
    private FakeLockHost $host;

    protected function setUp(): void
    {
        $this->lockFactory = $this->createStub(LockFactory::class);
        $this->host = new FakeLockHost($this->lockFactory);
    }

    #[Test]
    public function itRunsCriticalSection(): void
    {
        // Given
        $expected = new \stdClass();
        $lock = $this->createMock(SharedLockInterface::class);
        $lock->method('acquire')->willReturn(true);
        $lock->expects($this->once())->method('release');
        $this->lockFactory->method('createLock')->willReturn($lock);

        // When
        $result = $this->host->run('key', static fn (): \stdClass => $expected);

        // Then
        self::assertSame($expected, $result);
    }

    #[Test]
    public function itThrowsWhenLocked(): void
    {
        // Given
        $lock = $this->createMock(SharedLockInterface::class);
        $lock->method('acquire')->willReturn(false);
        $lock->expects($this->never())->method('release');
        $this->lockFactory->method('createLock')->willReturn($lock);

        // Then
        $this->expectException(LockNotAcquiredException::class);

        // When
        $this->host->run('key', static fn (): never => self::fail('Critical section must not run when the lock is unavailable.'));
    }
}

final readonly class FakeLockHost
{
    use LockingTrait;

    public function __construct(private LockFactory $lockFactory)
    {
    }

    /**
     * @template T
     *
     * @param callable(): T $criticalSection
     *
     * @return T
     */
    public function run(string $resource, callable $criticalSection): mixed
    {
        return $this->withLock($resource, 5.0, $criticalSection);
    }

    /* @phpstan-ignore method.unused (called from LockingTrait, a src/ file tests/phpstan.neon never analyses — invisible to any tests/-scoped check, regardless of width) */
    private function locks(): LockFactory
    {
        return $this->lockFactory;
    }
}
