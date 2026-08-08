<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Persistence\Projection\Reducer;

use Iam\Identity\Application\Enum\AppIdentityStatus;
use Iam\Identity\Domain\Event\IdentityReactivated;
use Iam\Identity\Domain\Event\IdentitySuspended;
use Patchlevel\EventSourcing\Message\Reducer;
use Patchlevel\EventSourcing\Store\Criteria\Criteria;
use Patchlevel\EventSourcing\Store\Criteria\StreamCriterion;
use Patchlevel\EventSourcing\Store\Store;

final readonly class IdentityStatusReducer
{
    public function __construct(private Store $store)
    {
    }

    public function statusFor(string $identityId): AppIdentityStatus
    {
        $stream = $this->store->load(new Criteria(
            new StreamCriterion('iam.identity.identity-'.$identityId),
        ));

        /** @var array{status: AppIdentityStatus} $state */
        $state = (new Reducer())
            ->initState(['status' => AppIdentityStatus::ACTIVE])
            ->when(IdentitySuspended::class, static fn (): array => ['status' => AppIdentityStatus::SUSPENDED])
            ->when(IdentityReactivated::class, static fn (): array => ['status' => AppIdentityStatus::ACTIVE])
            ->reduce($stream);

        return $state['status'];
    }
}
