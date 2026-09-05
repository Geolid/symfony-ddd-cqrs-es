<?php

declare(strict_types=1);

namespace Shared\Tests\Support\Double;

use Shared\Application\Uniqueness\Exception\UniqueValueAlreadyTakenException;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;

final class FakeUniqueValueRegistry implements UniqueValueRegistryInterface
{
    /** @var array<string, string> */
    private array $reserved = [];

    public function reserve(UniqueKey $key, string $value, string $ownerId): void
    {
        if ($this->exists($key, $value)) {
            throw UniqueValueAlreadyTakenException::forValue($key, $value);
        }

        $this->reserved[$this->normalize($key, $value)] = $ownerId;
    }

    public function exists(UniqueKey $key, string $value, ?string $excludeOwnerId = null): bool
    {
        $existingOwnerId = $this->reserved[$this->normalize($key, $value)] ?? null;

        if (null === $existingOwnerId) {
            return false;
        }

        return $existingOwnerId !== $excludeOwnerId;
    }

    public function release(UniqueKey $key, string $ownerId): void
    {
        $prefix = $key->toString().':';

        foreach ($this->reserved as $normalized => $existingOwnerId) {
            if (str_starts_with($normalized, $prefix) && $ownerId === $existingOwnerId) {
                unset($this->reserved[$normalized]);
            }
        }
    }

    public function releaseAll(UniqueKey $key): void
    {
        $prefix = $key->toString().':';

        foreach ($this->reserved as $normalized => $existingOwnerId) {
            if (str_starts_with($normalized, $prefix)) {
                unset($this->reserved[$normalized]);
            }
        }
    }

    private function normalize(UniqueKey $key, string $value): string
    {
        return \sprintf('%s:%s', $key->toString(), $value);
    }
}
