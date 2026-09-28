<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\EventStore;

use Iam\Authentication\Domain\DeviceTrust\DeviceTrust;
use Iam\Authentication\Domain\DeviceTrust\Exception\DeviceTrustAlreadyExistsException;
use Iam\Authentication\Domain\DeviceTrust\Exception\DeviceTrustNotFoundException;
use Iam\Authentication\Domain\DeviceTrust\Repository\DeviceTrustRepositoryInterface;
use Iam\Authentication\Domain\DeviceTrust\ValueObject\DeviceTrustId;
use Patchlevel\EventSourcing\Repository\AggregateAlreadyExists;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PatchlevelDeviceTrustRepository implements DeviceTrustRepositoryInterface
{
    /**
     * @param Repository<DeviceTrust> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.iam.authentication.device_trust.repository')]
        private Repository $repository,
    ) {
    }

    public function has(DeviceTrustId $id): bool
    {
        return $this->repository->has($id);
    }

    public function load(DeviceTrustId $id): DeviceTrust
    {
        try {
            return $this->repository->load($id);
        } catch (AggregateNotFound) {
            throw DeviceTrustNotFoundException::forId($id->toString());
        }
    }

    public function save(DeviceTrust $deviceTrust): void
    {
        try {
            $this->repository->save($deviceTrust);
        } catch (AggregateAlreadyExists) {
            throw DeviceTrustAlreadyExistsException::forId($deviceTrust->id->toString());
        }
    }
}
