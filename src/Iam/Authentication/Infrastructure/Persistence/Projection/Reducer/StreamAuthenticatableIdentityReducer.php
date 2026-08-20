<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Persistence\Projection\Reducer;

use Iam\Identity\Application\Event\IdentityReactivatedIntegrationEvent;
use Iam\Identity\Application\Event\IdentitySuspendedIntegrationEvent;
use Patchlevel\EventSourcing\Message\Reducer;
use Patchlevel\EventSourcing\Store\Criteria\Criteria;
use Patchlevel\EventSourcing\Store\Criteria\StreamCriterion;
use Patchlevel\EventSourcing\Store\Store;
use Shared\Infrastructure\Persistence\EventStore\IntegrationStreamId;

final readonly class StreamAuthenticatableIdentityReducer
{
    public function __construct(private Store $store)
    {
    }

    public function isAuthenticatableFor(string $identityId): bool
    {
        $stream = $this->store->load(new Criteria(
            new StreamCriterion(IntegrationStreamId::build('iam.identity', $identityId)),
        ));

        /** @var array{authenticatable: bool} $state */
        $state = new Reducer()
            ->initState(['authenticatable' => true])
            ->when(IdentitySuspendedIntegrationEvent::class, static fn (): array => ['authenticatable' => false])
            ->when(IdentityReactivatedIntegrationEvent::class, static fn (): array => ['authenticatable' => true])
            ->reduce($stream);

        return $state['authenticatable'];
    }
}
