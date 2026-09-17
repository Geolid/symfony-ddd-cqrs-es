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

    public function claim(UniqueKey $key, string $value, string $subjectId): void
    {
        if ($this->isClaimed($key, $value)) {
            throw UniquenessViolatedException::forValue($key, $value);
        }

        $this->claimed[$this->normalize($key, $value)] = $subjectId;
    }

    public function isClaimed(UniqueKey $key, string $value, ?string $excludeSubjectId = null): bool
    {
        $existingSubjectId = $this->claimed[$this->normalize($key, $value)] ?? null;

        if (null === $existingSubjectId) {
            return false;
        }

        return $existingSubjectId !== $excludeSubjectId;
    }

    public function release(UniqueKey $key, string $subjectId): void
    {
        $prefix = $key->toString().':';

        foreach ($this->claimed as $normalized => $existingSubjectId) {
            if (str_starts_with($normalized, $prefix) && $subjectId === $existingSubjectId) {
                unset($this->claimed[$normalized]);
            }
        }
    }

    public function releaseAll(UniqueKey $key): void
    {
        $prefix = $key->toString().':';

        foreach ($this->claimed as $normalized => $existingSubjectId) {
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
