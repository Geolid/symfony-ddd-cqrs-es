<?php

declare(strict_types=1);

namespace Shared\Domain\Service;

use Shared\Domain\Exception\UniqueValueAlreadyTakenException;
use Shared\Domain\ValueObject\UniqueKey;

interface UniqueValueRegistryInterface
{
    /**
     * @throws UniqueValueAlreadyTakenException
     */
    public function reserve(UniqueKey $key, string $value, string $ownerId, ?string $subjectId = null): void;

    public function release(UniqueKey $key, string $value, string $ownerId): void;

    public function exists(UniqueKey $key, string $value, ?string $excludeOwnerId = null): bool;

    public function releaseAllForSubject(string $subjectId): void;
}
