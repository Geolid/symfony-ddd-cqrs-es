<?php

declare(strict_types=1);

namespace Api\Security;

use Psr\Clock\ClockInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Core\Exception\AccountExpiredException;
use Symfony\Component\Security\Core\Exception\LockedException;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

#[AsEventListener(event: CheckPassportEvent::class)]
final readonly class ApiUserStatusListener
{
    public function __construct(private ClockInterface $clock)
    {
    }

    public function __invoke(CheckPassportEvent $event): void
    {
        $user = $event->getPassport()->getUser();

        if (!$user instanceof ApiUser) {
            return;
        }

        if ($user->revoked) {
            throw new LockedException('API token credential has been revoked.');
        }

        if ($this->clock->now() > $user->expiresAt) {
            throw new AccountExpiredException('API token credential has expired.');
        }
    }
}
