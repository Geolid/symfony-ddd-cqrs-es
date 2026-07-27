<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Monitoring\Sentry;

use Sentry\Event;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class SentryEventEnricher
{
    /**
     * @param iterable<SentryContextProviderInterface> $contextProviders
     */
    public function __construct(
        #[Autowire('%kernel.app_id%')]
        private ?string $appId,
        #[AutowireIterator(SentryContextProviderInterface::class)]
        private iterable $contextProviders,
    ) {
    }

    public function beforeSend(): callable
    {
        return function (Event $event): Event {
            if (null !== $this->appId) {
                $event->setTag('app_id', $this->appId);
            }

            foreach ($this->contextProviders as $provider) {
                $context = $provider->provide();

                if (null !== $context) {
                    $event->setContext($provider->name(), $context);
                }
            }

            return $event;
        };
    }
}
