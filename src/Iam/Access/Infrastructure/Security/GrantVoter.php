<?php

declare(strict_types=1);

namespace Iam\Access\Infrastructure\Security;

use Iam\Access\Application\Query\ListGrantsForIdentity\ListGrantsForIdentity;
use Shared\Application\Query\QueryBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class GrantVoter extends Voter
{
    private const string PERMISSION_PATTERN = '/^[a-z][a-z0-9_]*:[a-z][a-z0-9_]*$/';

    public function __construct(private QueryBusInterface $queryBus)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return 1 === preg_match(self::PERMISSION_PATTERN, $attribute);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$token->getUser() instanceof UserInterface) {
            return false;
        }

        $grants = $this->queryBus->ask(new ListGrantsForIdentity($token->getUserIdentifier()));

        foreach ($grants as $grant) {
            if ($grant->permission === $attribute) {
                return true;
            }
        }

        return false;
    }
}
