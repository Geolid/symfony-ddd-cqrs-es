<?php

declare(strict_types=1);

namespace Storefront\Security\EventListener;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener(event: LoginSuccessEvent::class)]
final readonly class RedirectToTwoFactorChallengeOnLoginSuccess
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function __invoke(LoginSuccessEvent $event): void
    {
        if ($event->getAuthenticatedToken() instanceof TwoFactorTokenInterface) {
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('storefront_two_factor_challenge')));
        }
    }
}
