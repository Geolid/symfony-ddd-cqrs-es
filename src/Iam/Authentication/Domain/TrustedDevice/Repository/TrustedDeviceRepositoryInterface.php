<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\TrustedDevice\Repository;

use Iam\Authentication\Domain\TrustedDevice\Exception\TrustedDeviceAlreadyExistsException;
use Iam\Authentication\Domain\TrustedDevice\Exception\TrustedDeviceNotFoundException;
use Iam\Authentication\Domain\TrustedDevice\TrustedDevice;
use Iam\Authentication\Domain\TrustedDevice\ValueObject\TrustedDeviceId;

interface TrustedDeviceRepositoryInterface
{
    public function has(TrustedDeviceId $id): bool;

    /**
     * @throws TrustedDeviceNotFoundException
     */
    public function load(TrustedDeviceId $id): TrustedDevice;

    /**
     * @throws TrustedDeviceAlreadyExistsException
     */
    public function save(TrustedDevice $trustedDevice): void;
}
