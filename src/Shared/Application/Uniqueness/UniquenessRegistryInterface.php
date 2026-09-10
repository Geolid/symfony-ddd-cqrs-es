<?php

declare(strict_types=1);

namespace Shared\Application\Uniqueness;

use Shared\Application\Uniqueness\Exception\UniquenessViolatedException;

interface UniquenessRegistryInterface
{
    /**
     * @throws UniquenessViolatedException
     */
    public function claim(UniqueKey $key, string $value, string $ownerId): void;

    public function isClaimed(UniqueKey $key, string $value, ?string $excludeOwnerId = null): bool;

    public function release(UniqueKey $key, string $ownerId): void;

    public function releaseAll(UniqueKey $key): void;
}
