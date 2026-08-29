<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Sentry;

use Sentry\Event;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class AppIdTagger
{
    public function __construct(
        #[Autowire('%kernel.app_id%')]
        private ?string $appId,
    ) {
    }

    public function beforeSend(): callable
    {
        return function (Event $event): Event {
            if (null !== $this->appId) {
                $event->setTag('app_id', $this->appId);
            }

            return $event;
        };
    }
}
