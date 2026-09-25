<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\RevokeTrustedDevice;

use Iam\Authentication\Domain\TrustedDevice\Exception\TrustedDeviceAlreadyExistsException;
use Iam\Authentication\Domain\TrustedDevice\Exception\TrustedDeviceNotFoundException;
use Iam\Authentication\Domain\TrustedDevice\Exception\TrustedDeviceOwnedByAnotherIdentityException;
use Iam\Authentication\Domain\TrustedDevice\Repository\TrustedDeviceRepositoryInterface;
use Iam\Authentication\Domain\TrustedDevice\ValueObject\TrustedDeviceId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class RevokeTrustedDeviceHandler
{
    public function __construct(
        private TrustedDeviceRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws TrustedDeviceNotFoundException
     * @throws TrustedDeviceOwnedByAnotherIdentityException
     * @throws TrustedDeviceAlreadyExistsException
     */
    public function __invoke(RevokeTrustedDevice $command): void
    {
        $trustedDevice = $this->repository->load(TrustedDeviceId::fromString($command->id));
        $trustedDevice->revoke($command->identityId, $this->clock->now());

        $this->repository->save($trustedDevice);
    }
}
