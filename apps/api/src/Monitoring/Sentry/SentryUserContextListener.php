<?php

declare(strict_types=1);

namespace Api\Monitoring\Sentry;

use Api\Security\ApiUser;
use Sentry\SentrySdk;
use Sentry\State\Scope;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsEventListener]
final readonly class SentryUserContextListener
{
    public function __construct(private TokenStorageInterface $tokenStorage)
    {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->tokenStorage->getToken()?->getUser();

        if (!$user instanceof ApiUser) {
            return;
        }

        SentrySdk::getCurrentHub()->configureScope(static function (Scope $scope) use ($user): void {
            $scope->setContext('identity', ['identityId' => $user->identityId()]);
        });
    }
}
