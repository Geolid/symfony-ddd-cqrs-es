<?php

declare(strict_types=1);

namespace Storefront\Security\Provider;

use Iam\Authentication\Application\Command\TrustDevice\TrustDevice;
use Iam\Authentication\Application\Query\ListTrustedDevicesByIdentity\ListTrustedDevicesByIdentity;
use Ramsey\Uuid\Uuid;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceTokenStorage;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Storefront\Security\PasswordUser;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class TrustedDeviceManager implements TrustedDeviceManagerInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
        private CommandBusInterface $commandBus,
        #[Autowire(service: 'scheb_two_factor.trusted_token_storage')]
        private TrustedDeviceTokenStorage $trustedTokenStorage,
        private RequestStack $requestStack,
    ) {
    }

    public function canSetTrustedDevice(object $user, Request $request, string $firewallName): bool
    {
        return $user instanceof PasswordUser;
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function addTrustedDevice(object $user, string $firewallName): void
    {
        if (!$user instanceof PasswordUser) {
            return;
        }

        $request = $this->requestStack->getMainRequest();

        if (null === $request) {
            return;
        }

        $version = random_int(1, \PHP_INT_MAX);

        $this->commandBus->dispatch(new TrustDevice(
            Uuid::uuid7()->toString(),
            $user->identityId(),
            $version,
            $request->headers->get('User-Agent', ''),
            $request->getClientIp() ?? '',
        ));

        $this->trustedTokenStorage->addTrustedToken($user->getUserIdentifier(), $firewallName, $version);
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    public function isTrustedDevice(object $user, string $firewallName): bool
    {
        if (!$user instanceof PasswordUser) {
            return false;
        }

        $trustedDevices = $this->queryBus->ask(new ListTrustedDevicesByIdentity($user->identityId()));

        foreach ($trustedDevices as $trustedDevice) {
            if ($this->trustedTokenStorage->hasTrustedToken($user->getUserIdentifier(), $firewallName, $trustedDevice->version)) {
                return true;
            }
        }

        return false;
    }
}
