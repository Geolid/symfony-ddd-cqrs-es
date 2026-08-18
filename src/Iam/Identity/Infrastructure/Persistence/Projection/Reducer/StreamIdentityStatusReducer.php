<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Persistence\Projection\Reducer;

use Iam\Identity\Application\Status\IdentityStatus;
use Iam\Identity\Domain\Event\IdentityReactivated;
use Iam\Identity\Domain\Event\IdentitySuspended;
use Patchlevel\EventSourcing\Message\Reducer;
use Patchlevel\EventSourcing\Store\Criteria\Criteria;
use Patchlevel\EventSourcing\Store\Criteria\StreamCriterion;
use Patchlevel\EventSourcing\Store\Store;

final readonly class StreamIdentityStatusReducer
{
    public function __construct(private Store $store)
    {
    }

    public function statusFor(string $identityId): IdentityStatus
    {
        $stream = $this->store->load(new Criteria(
            new StreamCriterion('iam.identity.identity-'.$identityId),
        ));

        /** @var array{status: IdentityStatus} $state */
        $state = new Reducer()
            ->initState(['status' => IdentityStatus::ACTIVE])
            ->when(IdentitySuspended::class, static fn (): array => ['status' => IdentityStatus::SUSPENDED])
            ->when(IdentityReactivated::class, static fn (): array => ['status' => IdentityStatus::ACTIVE])
            ->reduce($stream);

        return $state['status'];
    }
}
