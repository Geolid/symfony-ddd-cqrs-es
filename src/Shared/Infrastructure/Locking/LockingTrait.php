<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Locking;

use Shared\Infrastructure\Locking\Exception\LockNotAcquiredException;
use Symfony\Component\Lock\LockFactory;

trait LockingTrait
{
    abstract private function locks(): LockFactory;

    /**
     * @template T
     *
     * @param callable(): T $criticalSection
     *
     * @return T
     *
     * @throws LockNotAcquiredException
     */
    private function withLock(string $resource, float $ttl, callable $criticalSection): mixed
    {
        $lock = $this->locks()->createLock($resource, $ttl);

        if (!$lock->acquire()) {
            throw LockNotAcquiredException::forResource($resource);
        }

        try {
            return $criticalSection();
        } finally {
            $lock->release();
        }
    }
}
