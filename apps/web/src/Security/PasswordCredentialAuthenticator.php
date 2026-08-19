<?php

declare(strict_types=1);

namespace Web\Security;

use Iam\Identity\Application\Credential\PasswordCredentialVerifierInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

final class PasswordCredentialAuthenticator extends AbstractLoginFormAuthenticator
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly PasswordCredentialVerifierInterface $verifier,
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        $login = (string) $request->request->get('login', '');
        $password = (string) $request->request->get('password', '');

        return new Passport(
            new UserBadge($login),
            new CustomCredentials(
                function (mixed $password, UserInterface $user): bool {
                    \assert(\is_string($password));
                    \assert($user instanceof PasswordUser);

                    return $this->verifier->verify($user->identityId(), $password);
                },
                $password,
            ),
            [
                new CsrfTokenBadge('authenticate', (string) $request->request->get('_csrf_token')),
                new PlainSecretBadge($password),
            ],
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate('sales_order_list'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($request->hasSession()) {
            $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, (string) $request->request->get('login', ''));
        }

        return parent::onAuthenticationFailure($request, $exception);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate('security_login');
    }
}
