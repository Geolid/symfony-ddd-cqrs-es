<?php

declare(strict_types=1);

namespace Storefront\Security;

use Iam\Authentication\Application\CredentialVerification\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\CredentialVerification\TotpCredentialVerifierInterface;
use Iam\Authentication\Application\Query\GetTotpCredentialByIdentity\GetTotpCredentialByIdentity;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\DefaultTwoFactorFormRenderer;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Twig\Environment;

#[AutoconfigureTag('scheb_two_factor.provider', ['alias' => 'totp'])]
final readonly class TotpTwoFactorProvider implements TwoFactorProviderInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
        private TotpCredentialVerifierInterface $verifier,
        private Environment $twig,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    public function beginAuthentication(AuthenticationContextInterface $context): bool
    {
        $user = $context->getUser();

        return $user instanceof PasswordUser
            && null !== $this->queryBus->ask(new GetTotpCredentialByIdentity($user->identityId()));
    }

    public function prepareAuthentication(object $user): void
    {
        // Nothing to prepare: unlike an email OTP, a TOTP code needs no server-side send step.
    }

    /**
     * @throws IdentityNotAuthenticatableException
     */
    public function validateAuthenticationCode(object $user, string $authenticationCode): bool
    {
        if (!$user instanceof PasswordUser) {
            return false;
        }

        return $this->verifier->verify($user->identityId(), $authenticationCode);
    }

    public function getFormRenderer(): TwoFactorFormRendererInterface
    {
        return new DefaultTwoFactorFormRenderer($this->twig, 'two_factor/challenge.html.twig');
    }
}
