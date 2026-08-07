<?php

declare(strict_types=1);

namespace Web\Security;

use Iam\Identity\Application\Exception\PasswordCredentialAuthenticationFailedException;
use Iam\Identity\Application\Security\PasswordCredentialAuthenticatorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

final class PasswordCredentialAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private PasswordCredentialAuthenticatorInterface $authenticator,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function supports(Request $request): bool
    {
        return 'security_login' === $request->attributes->get('_route') && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $login = (string) $request->request->get('login', '');
        $password = (string) $request->request->get('password', '');

        try {
            $identityId = $this->authenticator->authenticate($login, $password);
        } catch (PasswordCredentialAuthenticationFailedException) {
            throw new CustomUserMessageAuthenticationException('Invalid credentials.');
        }

        return new SelfValidatingPassport(
            new UserBadge($identityId),
            [new CsrfTokenBadge('authenticate', (string) $request->request->get('_csrf_token'))],
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): Response
    {
        return new RedirectResponse($this->urlGenerator->generate('sales_order_list'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, (string) $request->request->get('login', ''));

        return new RedirectResponse($this->urlGenerator->generate('security_login'));
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->urlGenerator->generate('security_login'));
    }
}
