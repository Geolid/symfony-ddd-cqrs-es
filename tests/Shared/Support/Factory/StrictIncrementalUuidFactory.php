<?php

declare(strict_types=1);

namespace Shared\Tests\Support\Factory;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;
use Ramsey\Uuid\UuidInterface;

/**
 * Deterministic incremental ids for test ordering, RFC 9562-valid (version 7, variant 8).
 */
final class StrictIncrementalUuidFactory extends UuidFactory
{
    private int $counter = 0;

    public function uuid7(?\DateTimeInterface $dateTime = null): UuidInterface
    {
        $number = ++$this->counter;

        return Uuid::fromString(\sprintf('00000000-0000-7000-8000-%012d', $number));
    }

    public function reset(): void
    {
        $this->counter = 0;
    }
}
