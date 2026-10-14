<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Policy;

use Compliance\Erasing\Application\IntegrationEvent\ErasureRequested\ErasureRequestedIntegrationEvent;
use Iam\Identity\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\Notification\IdentityNotifierInterface;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Mailer\Exception\MailerException;
use Shared\Application\Policy;

#[Policy('iam.identity.notify_erasure_requested_on_erasure_requested')]
final readonly class NotifyErasureRequestedOnErasureRequested
{
    public function __construct(
        private IdentityFinderInterface $identityFinder,
        private IdentityNotifierInterface $notifier,
    ) {
    }

    /**
     * @throws IdentityResultNotFoundException
     * @throws MailerException
     */
    #[Subscribe(ErasureRequestedIntegrationEvent::class)]
    public function __invoke(ErasureRequestedIntegrationEvent $event): void
    {
        $identity = $this->identityFinder->ofId($event->identityId);

        $this->notifier->notifyErasureRequested($identity->email, $event->effectiveAt);
    }
}
