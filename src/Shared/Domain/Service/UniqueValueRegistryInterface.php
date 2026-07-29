<?php

declare(strict_types=1);

namespace Shared\Domain\Service;

use Shared\Domain\Exception\UniqueValueAlreadyTakenException;

interface UniqueValueRegistryInterface
{
    /**
     * @throws UniqueValueAlreadyTakenException
     */
    public function reserve(\BackedEnum $type, string $value): void;

    public function release(\BackedEnum $type, string $value): void;

    public function exists(\BackedEnum $type, string $value): bool;
}
