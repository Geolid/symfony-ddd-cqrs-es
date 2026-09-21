<?php

declare(strict_types=1);

namespace Storefront\Security\Authenticator;

use Iam\Authentication\Application\CredentialVerification\PasswordCredentialVerifierInterface;
use Storefront\Security\Badge\PlainPasswordBadge;
use Storefront\Security\PasswordUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
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
        $email = (string) $request->request->get('email', '');
        $password = (string) $request->request->get('password', '');

        return new Passport(
            new UserBadge($email),
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
                new PlainPasswordBadge($password),
                new RememberMeBadge(),
            ],
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate('storefront_account_show'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): RedirectResponse
    {
        $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, (string) $request->request->get('email', ''));

        return new RedirectResponse($this->urlGenerator->generate('storefront_signin_verify'));
    }

    public function start(Request $request, ?AuthenticationException $authException = null): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate('storefront_signin_identify'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate('storefront_signin_verify');
    }
}
