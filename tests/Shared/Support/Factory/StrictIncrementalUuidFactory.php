<?php

declare(strict_types=1);

namespace Shared\Tests\Support\Factory;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;
use Ramsey\Uuid\UuidInterface;

/**
 * `Patchlevel\EventSourcing\Test\IncrementalRamseyUuidFactory`'s own `uuid7()` puts the
 * incrementing counter in a group that leaves the version/variant nibbles at `0`, an
 * RFC4122-invalid shape that intermittently fails a strict `Requirement::UUID` route match.
 * Same incremental/deterministic intent, correct nibble placement instead.
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
