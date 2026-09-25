<?php

declare(strict_types=1);

namespace Storefront\Security\Provider;

use Iam\Authentication\Application\Command\TrustDevice\TrustDevice;
use Iam\Authentication\Application\Query\ListTrustedDevicesByIdentity\ListTrustedDevicesByIdentity;
use Ramsey\Uuid\Uuid;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceTokenStorage;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Query\QueryBusInterface;
use Storefront\Security\PasswordUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class TrustedDeviceManager implements TrustedDeviceManagerInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
        private CommandBusInterface $commandBus,
        private TrustedDeviceTokenStorage $trustedTokenStorage,
        private RequestStack $requestStack,
    ) {
    }

    public function canSetTrustedDevice(object $user, Request $request, string $firewallName): bool
    {
        return $user instanceof PasswordUser;
    }

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
