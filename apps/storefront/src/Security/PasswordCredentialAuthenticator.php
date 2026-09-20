<?php

declare(strict_types=1);

namespace Storefront\Security;

use Iam\Authentication\Application\CredentialVerification\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\CredentialVerification\PasswordCredentialVerifierInterface;
use Iam\Authentication\Application\Finder\PasswordCredential\Exception\PasswordCredentialResultNotFoundException;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
use Twig\Environment;

final class PasswordCredentialAuthenticator extends AbstractLoginFormAuthenticator
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly PasswordCredentialVerifierInterface $verifier,
        private readonly Environment $twig,
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

                    try {
                        return $this->verifier->verify($user->identityId(), $password);
                    } catch (PasswordCredentialResultNotFoundException|IdentityNotAuthenticatableException) {
                        return false;
                    }
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
        if ($token instanceof TwoFactorTokenInterface) {
            return new RedirectResponse($this->urlGenerator->generate('storefront_two_factor_challenge'));
        }

        return new RedirectResponse($this->urlGenerator->generate('storefront_account_show'));
    }

    // Renders the password screen directly instead of redirecting: the account is already
    // known and confirmed at this point, re-running the identify step would gain nothing.
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $email = (string) $request->request->get('email', '');

        return new Response($this->twig->render('security/password.html.twig', [
            'email' => $email,
            'error' => $exception,
        ]));
    }

    public function start(Request $request, ?AuthenticationException $authException = null): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate('storefront_signin'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate('storefront_signin_verify');
    }
}
