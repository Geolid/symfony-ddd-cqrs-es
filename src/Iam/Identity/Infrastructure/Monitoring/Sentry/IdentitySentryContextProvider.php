<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Monitoring\Sentry;

use Shared\Infrastructure\Monitoring\Sentry\SentryContextProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final readonly class IdentitySentryContextProvider implements SentryContextProviderInterface
{
    public function __construct(
        private ?TokenStorageInterface $tokenStorage = null,
    ) {
    }

    public function name(): string
    {
        return 'identity';
    }

    public function provide(): ?array
    {
        $identityId = $this->tokenStorage?->getToken()?->getUser()?->getUserIdentifier();

        if (null === $identityId) {
            return null;
        }

        return ['identityId' => $identityId];
    }
}
