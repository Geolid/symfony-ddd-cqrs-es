<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\RevokeDeviceTrust;

use Iam\Authentication\Domain\DeviceTrust\DeviceTrust;
use Iam\Authentication\Domain\DeviceTrust\Exception\DeviceTrustAlreadyExistsException;
use Iam\Authentication\Domain\DeviceTrust\Exception\DeviceTrustNotFoundException;
use Iam\Authentication\Domain\DeviceTrust\Repository\DeviceTrustRepositoryInterface;
use Iam\Authentication\Domain\DeviceTrust\ValueObject\DeviceTrustId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class RevokeDeviceTrustHandler
{
    public function __construct(
        private DeviceTrustRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws DeviceTrustAlreadyExistsException
     */
    public function __invoke(RevokeDeviceTrust $command): void
    {
        $id = DeviceTrustId::forIdentity($command->identityId);

        try {
            $deviceTrust = $this->repository->load($id);
            $deviceTrust->revoke($command->identityId, $this->clock->now());
        } catch (DeviceTrustNotFoundException) {
            $deviceTrust = DeviceTrust::establish($id, $command->identityId, $this->clock->now());
        }

        $this->repository->save($deviceTrust);
    }
}
