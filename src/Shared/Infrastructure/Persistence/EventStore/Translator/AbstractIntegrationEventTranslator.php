<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Persistence\EventStore\Translator;

use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Store\Header\StreamNameHeader;
use Patchlevel\EventSourcing\Store\Store;
use Shared\Application\Event\IntegrationEventInterface;

/**
 * Converts a BC's Domain Events into its one public Integration Event contract and appends
 * them to the store, keyed by the correlation id consumers subscribe against. Domain Events are
 * sealed to their BC — this translation is the only path out.
 */
abstract readonly class AbstractIntegrationEventTranslator
{
    public function __construct(private Store $store)
    {
    }

    protected function append(string $streamId, IntegrationEventInterface $integrationEvent): void
    {
        $this->store->save(Message::create($integrationEvent)->withHeader(new StreamNameHeader($streamId)));
    }
}
