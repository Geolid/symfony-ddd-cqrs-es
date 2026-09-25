<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Finder\TrustedDevice;

use Shared\Application\Finder\IterableFinderInterface;

/**
 * @extends IterableFinderInterface<TrustedDeviceResult>
 */
interface TrustedDeviceFinderInterface extends IterableFinderInterface
{
    public function activeByIdentity(string $identityId): static;
}
