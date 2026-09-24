<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\DeviceTrust\Repository;

use Iam\Authentication\Domain\DeviceTrust\DeviceTrust;
use Iam\Authentication\Domain\DeviceTrust\Exception\DeviceTrustAlreadyExistsException;
use Iam\Authentication\Domain\DeviceTrust\Exception\DeviceTrustNotFoundException;
use Iam\Authentication\Domain\DeviceTrust\ValueObject\DeviceTrustId;

interface DeviceTrustRepositoryInterface
{
    public function has(DeviceTrustId $id): bool;

    /**
     * @throws DeviceTrustNotFoundException
     */
    public function load(DeviceTrustId $id): DeviceTrust;

    /**
     * @throws DeviceTrustAlreadyExistsException
     */
    public function save(DeviceTrust $deviceTrust): void;
}
