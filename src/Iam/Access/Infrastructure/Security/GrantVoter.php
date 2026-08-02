<?php

declare(strict_types=1);

namespace Iam\Access\Infrastructure\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, mixed>
 */
final class GrantVoter extends Voter
{
    private const string PERMISSION_PATTERN = '/^[a-z][a-z0-9_]*:[a-z][a-z0-9_]*$/';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return 1 === preg_match(self::PERMISSION_PATTERN, $attribute);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        return \in_array($attribute, $token->getRoleNames(), true);
    }
}
