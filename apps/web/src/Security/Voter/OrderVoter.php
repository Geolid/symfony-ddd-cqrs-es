<?php

declare(strict_types=1);

namespace Web\Security\Voter;

use Sales\OrderSummary\Application\Finder\OrderSummary\OrderSummaryResult;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Web\Security\PasswordUser;

/**
 * @extends Voter<string, OrderSummaryResult>
 */
final class OrderVoter extends Voter
{
    public const string VIEW = 'VIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::VIEW === $attribute && $subject instanceof OrderSummaryResult;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        \assert($user instanceof PasswordUser);

        return $subject->customerId === $user->identityId();
    }
}
