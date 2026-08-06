<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Security;

use Iam\Identity\Application\Exception\IdentityResultNotFoundException;
use Iam\Identity\Application\Query\GetIdentity\GetIdentity;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class IdentityStatusUserChecker implements UserCheckerInterface
{
    public function __construct(private QueryBusInterface $queryBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    public function checkPreAuth(UserInterface $user): void
    {
        try {
            $identity = $this->queryBus->ask(new GetIdentity($user->getUserIdentifier()));
        } catch (IdentityResultNotFoundException) {
            throw new CustomUserMessageAccountStatusException('Invalid credentials.');
        }

        if (!$identity->status->isActive()) {
            throw new CustomUserMessageAccountStatusException('This account is suspended.');
        }
    }

    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
    }
}
