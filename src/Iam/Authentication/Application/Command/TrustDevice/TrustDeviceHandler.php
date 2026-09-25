<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\TrustDevice;

use Iam\Authentication\Domain\TrustedDevice\Exception\TrustedDeviceAlreadyExistsException;
use Iam\Authentication\Domain\TrustedDevice\Repository\TrustedDeviceRepositoryInterface;
use Iam\Authentication\Domain\TrustedDevice\TrustedDevice;
use Iam\Authentication\Domain\TrustedDevice\ValueObject\TrustedDeviceId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class TrustDeviceHandler
{
    public function __construct(
        private TrustedDeviceRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws TrustedDeviceAlreadyExistsException
     */
    public function __invoke(TrustDevice $command): void
    {
        $trustedDevice = TrustedDevice::trust(
            id: TrustedDeviceId::fromString($command->id),
            identityId: $command->identityId,
            version: $command->version,
            userAgent: $command->userAgent,
            ip: $command->ip,
            trustedAt: $this->clock->now(),
        );

        $this->repository->save($trustedDevice);
    }
}
