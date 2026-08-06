<?php

declare(strict_types=1);

namespace Web\Security\Voter;

use Sales\OrderSummary\Application\Finder\OrderSummary\OrderSummaryResult;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Web\Security\CustomerIdentityProvider;

/**
 * @extends Voter<string, mixed>
 */
final class OrderVoter extends Voter
{
    public const string VIEW = 'VIEW';

    public function __construct(private readonly CustomerIdentityProvider $customerIdentityProvider)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::VIEW === $attribute && $subject instanceof OrderSummaryResult;
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        \assert($subject instanceof OrderSummaryResult);

        $customerId = $this->customerIdentityProvider->resolveCustomerId($token);

        return null !== $customerId && $subject->customerId === $customerId;
    }
}
