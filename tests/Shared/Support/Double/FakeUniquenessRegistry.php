<?php

declare(strict_types=1);

namespace Shared\Tests\Support\Double;

use Shared\Application\Uniqueness\Exception\UniquenessViolatedException;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;

final class FakeUniquenessRegistry implements UniquenessRegistryInterface
{
    /** @var array<string, string> */
    private array $claimed = [];

    public function claim(UniqueKey $key, string $value, string $ownerId): void
    {
        if ($this->isClaimed($key, $value)) {
            throw UniquenessViolatedException::forValue($key, $value);
        }

        $this->claimed[$this->normalize($key, $value)] = $ownerId;
    }

    public function isClaimed(UniqueKey $key, string $value, ?string $excludeOwnerId = null): bool
    {
        $existingOwnerId = $this->claimed[$this->normalize($key, $value)] ?? null;

        if (null === $existingOwnerId) {
            return false;
        }

        return $existingOwnerId !== $excludeOwnerId;
    }

    public function release(UniqueKey $key, string $ownerId): void
    {
        $prefix = $key->toString().':';

        foreach ($this->claimed as $normalized => $existingOwnerId) {
            if (str_starts_with($normalized, $prefix) && $ownerId === $existingOwnerId) {
                unset($this->claimed[$normalized]);
            }
        }
    }

    public function releaseAll(UniqueKey $key): void
    {
        $prefix = $key->toString().':';

        foreach ($this->claimed as $normalized => $existingOwnerId) {
            if (str_starts_with($normalized, $prefix)) {
                unset($this->claimed[$normalized]);
            }
        }
    }

    private function normalize(UniqueKey $key, string $value): string
    {
        return \sprintf('%s:%s', $key->toString(), $value);
    }
}
