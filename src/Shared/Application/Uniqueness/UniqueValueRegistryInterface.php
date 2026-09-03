<?php

declare(strict_types=1);

namespace Shared\Application\Uniqueness;

use Shared\Application\Exception\UniqueValueAlreadyTakenException;

interface UniqueValueRegistryInterface
{
    /**
     * @throws UniqueValueAlreadyTakenException
     */
    public function reserve(UniqueKey $key, string $value, string $ownerId): void;

    public function exists(UniqueKey $key, string $value, ?string $excludeOwnerId = null): bool;

    public function release(UniqueKey $key, string $ownerId): void;

    public function releaseAll(UniqueKey $key): void;
}
