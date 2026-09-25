<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\TrustedDeviceRevocation;

use Iam\Authentication\Application\Command\RevokeTrustedDevice\RevokeTrustedDevice;
use Iam\Authentication\Application\Finder\TrustedDevice\TrustedDeviceFinderInterface;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;

final readonly class TrustedDeviceRevoker implements TrustedDeviceRevokerInterface
{
    public function __construct(
        private TrustedDeviceFinderInterface $trustedDeviceFinder,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function revokeAllFor(string $identityId): void
    {
        foreach ($this->trustedDeviceFinder->activeByIdentity($identityId) as $trustedDevice) {
            $this->commandBus->dispatch(new RevokeTrustedDevice($trustedDevice->id, $identityId));
        }
    }
}
