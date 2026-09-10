<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Uniqueness;

use Iam\Authentication\Application\PasswordCredentialUniqueKey;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Iam\Authentication\Infrastructure\Uniqueness\ReleaseLoginOnIdentityErased;
use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ReleaseLoginOnIdentityErasedTest extends AbstractIntegrationTestCase
{
    private UniquenessRegistryInterface $uniqueValues;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uniqueValues = $this->service(UniquenessRegistryInterface::class);
    }

    #[Test]
    public function itReleases(): void
    {
        // Given
        $identityId = PasswordCredentialBuilder::sample('identityId');
        $builder = PasswordCredentialBuilder::new()->withIdentityId($identityId);
        $login = PasswordCredentialBuilder::sample('login')->value;
        $loginKey = UniqueKey::for(PasswordCredentialUniqueKey::LOGIN);
        $this->uniqueValues->claim($loginKey, $login, $builder['id']->toString());

        $otherLogin = PasswordCredentialBuilder::sample('login')->value;
        $this->uniqueValues->claim($loginKey, $otherLogin, PasswordCredentialId::forIdentity(Uuid::uuid7()->toString())->toString());

        // When
        $this->trigger(ReleaseLoginOnIdentityErased::class, new IdentityErasedIntegrationEvent($identityId, Clock::get()->now()));

        // Then
        self::assertFalse($this->uniqueValues->isClaimed($loginKey, $login));
        self::assertTrue($this->uniqueValues->isClaimed($loginKey, $otherLogin));
    }
}
