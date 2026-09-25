<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\EventStore;

use Iam\Authentication\Domain\TrustedDevice\Exception\TrustedDeviceAlreadyExistsException;
use Iam\Authentication\Domain\TrustedDevice\Exception\TrustedDeviceNotFoundException;
use Iam\Authentication\Domain\TrustedDevice\Repository\TrustedDeviceRepositoryInterface;
use Iam\Authentication\Domain\TrustedDevice\TrustedDevice;
use Iam\Authentication\Domain\TrustedDevice\ValueObject\TrustedDeviceId;
use Patchlevel\EventSourcing\Repository\AggregateAlreadyExists;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PatchlevelTrustedDeviceRepository implements TrustedDeviceRepositoryInterface
{
    /**
     * @param Repository<TrustedDevice> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.iam.authentication.trusted_device.repository')]
        private Repository $repository,
    ) {
    }

    public function has(TrustedDeviceId $id): bool
    {
        return $this->repository->has($id);
    }

    public function load(TrustedDeviceId $id): TrustedDevice
    {
        try {
            return $this->repository->load($id);
        } catch (AggregateNotFound) {
            throw TrustedDeviceNotFoundException::forId($id->toString());
        }
    }

    public function save(TrustedDevice $trustedDevice): void
    {
        try {
            $this->repository->save($trustedDevice);
        } catch (AggregateAlreadyExists) {
            throw TrustedDeviceAlreadyExistsException::forId($trustedDevice->id->toString());
        }
    }
}
