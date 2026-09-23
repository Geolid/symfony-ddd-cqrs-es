<?php

declare(strict_types=1);

namespace Storefront\Security\Provider;

use Iam\Authentication\Application\CredentialVerification\BackupCodeCredentialVerifierInterface;
use Iam\Authentication\Application\Query\GetBackupCodeCredentialByIdentity\GetBackupCodeCredentialByIdentity;
use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeGeneratorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\DefaultTwoFactorFormRenderer;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Storefront\Security\PasswordUser;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Twig\Environment;

#[AutoconfigureTag('scheb_two_factor.provider', ['alias' => 'backup_code'])]
final readonly class BackupCodeTwoFactorProvider implements TwoFactorProviderInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
        private BackupCodeCredentialVerifierInterface $verifier,
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
            && null !== $this->queryBus->ask(new GetBackupCodeCredentialByIdentity($user->identityId()));
    }

    public function prepareAuthentication(object $user): void
    {
    }

    public function validateAuthenticationCode(object $user, string $authenticationCode): bool
    {
        if (!$user instanceof PasswordUser) {
            return false;
        }

        return $this->verifier->verify($user->identityId(), $authenticationCode);
    }

    public function getFormRenderer(): TwoFactorFormRendererInterface
    {
        return new DefaultTwoFactorFormRenderer(
            $this->twig,
            'two_factor/challenge_backup_code.html.twig',
            ['pattern' => \sprintf('\d{%d}', BackupCodeGeneratorInterface::DIGITS)],
        );
    }
}
