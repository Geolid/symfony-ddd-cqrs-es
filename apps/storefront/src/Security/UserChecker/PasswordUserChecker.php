<?php

declare(strict_types=1);

namespace Storefront\Security\UserChecker;

use Storefront\Security\Exception\AccountSuspendedException;
use Storefront\Security\Exception\AccountUnconfirmedException;
use Storefront\Security\PasswordUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class PasswordUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof PasswordUser) {
            return;
        }

        if ($user->verificationStatus->isPending()) {
            throw AccountUnconfirmedException::forIdentity($user->identityId());
        }

        if ($user->moderationStatus->isSuspended()) {
            throw AccountSuspendedException::forIdentity($user->identityId());
        }
    }

    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
    }
}
