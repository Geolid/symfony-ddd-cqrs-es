<?php

declare(strict_types=1);

namespace Iam\Tests\Access\Infrastructure\Security;

use Iam\Access\Infrastructure\Security\GrantVoter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class GrantVoterTest extends TestCase
{
    #[Test]
    public function itGrantsAccessWhenTheRoleIsPresent(): void
    {
        // Given
        $token = $this->tokenWithRoles(['ROLE_USER', 'sales:read']);

        // When
        $vote = (new GrantVoter())->vote($token, null, ['sales:read']);

        // Then
        self::assertSame(VoterInterface::ACCESS_GRANTED, $vote);
    }

    #[Test]
    public function itDeniesAccessWhenTheRoleIsAbsent(): void
    {
        // Given
        $token = $this->tokenWithRoles(['ROLE_USER']);

        // When
        $vote = (new GrantVoter())->vote($token, null, ['sales:read']);

        // Then
        self::assertSame(VoterInterface::ACCESS_DENIED, $vote);
    }

    #[Test]
    public function itAbstainsOnAnAttributeThatIsNotAPermission(): void
    {
        // Given
        $token = $this->tokenWithRoles(['ROLE_USER']);

        // When
        $vote = (new GrantVoter())->vote($token, null, ['ROLE_USER']);

        // Then
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $vote);
    }

    /**
     * @param list<string> $roles
     */
    private function tokenWithRoles(array $roles): UsernamePasswordToken
    {
        return new UsernamePasswordToken(new InMemoryUser('identity-1', null, $roles), 'main', $roles);
    }
}
