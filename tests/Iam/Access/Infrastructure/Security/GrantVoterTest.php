<?php

declare(strict_types=1);

namespace Iam\Tests\Access\Infrastructure\Security;

use Iam\Access\Infrastructure\Security\GrantVoter;
use Iam\Tests\Access\Support\Factory\GrantTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class GrantVoterTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGrantsAccessWhenTheIdentityHoldsThePermission(): void
    {
        // Given
        $this->store(GrantTestFactory::new()->forIdentity('identity-1')->withPermission('sales:supervise')->create());

        // When
        $vote = $this->service(GrantVoter::class)->vote($this->tokenFor('identity-1'), null, ['sales:supervise']);

        // Then
        self::assertSame(VoterInterface::ACCESS_GRANTED, $vote);
    }

    #[Test]
    public function itDeniesAccessWhenTheIdentityDoesNotHoldThePermission(): void
    {
        // Given
        $this->store(GrantTestFactory::new()->forIdentity('identity-1')->withPermission('sales:supervise')->create());

        // When
        $vote = $this->service(GrantVoter::class)->vote($this->tokenFor('identity-1'), null, ['catalog:manage']);

        // Then
        self::assertSame(VoterInterface::ACCESS_DENIED, $vote);
    }

    #[Test]
    public function itDeniesAccessWhenThePermissionWasRevoked(): void
    {
        // Given
        $this->store(GrantTestFactory::new()->forIdentity('identity-1')->withPermission('sales:supervise')->revoked()->create());

        // When
        $vote = $this->service(GrantVoter::class)->vote($this->tokenFor('identity-1'), null, ['sales:supervise']);

        // Then
        self::assertSame(VoterInterface::ACCESS_DENIED, $vote);
    }

    #[Test]
    public function itAbstainsOnAnAttributeThatIsNotAPermission(): void
    {
        // When
        $vote = $this->service(GrantVoter::class)->vote($this->tokenFor('identity-1'), null, ['ROLE_USER']);

        // Then
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $vote);
    }

    private function tokenFor(string $identityId): UsernamePasswordToken
    {
        return new UsernamePasswordToken(new InMemoryUser($identityId, null), 'main', ['ROLE_USER']);
    }
}
