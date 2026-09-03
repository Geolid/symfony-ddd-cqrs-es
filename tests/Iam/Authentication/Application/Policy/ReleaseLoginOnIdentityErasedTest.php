<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Policy;

use Iam\Authentication\Application\Policy\ReleaseLoginOnIdentityErased;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialUniqueKey;
use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ReleaseLoginOnIdentityErasedTest extends AbstractIntegrationTestCase
{
    private UniqueValueRegistryInterface $uniqueValues;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uniqueValues = $this->service(UniqueValueRegistryInterface::class);
    }

    #[Test]
    public function itReleases(): void
    {
        // Given
        $identityId = PasswordCredentialBuilder::sample('identityId');
        $builder = PasswordCredentialBuilder::new()->withIdentityId($identityId);
        $login = PasswordCredentialBuilder::sample('login')->value;
        $loginKey = UniqueKey::for(PasswordCredentialUniqueKey::LOGIN);
        $this->uniqueValues->reserve($loginKey, $login, $builder['id']->toString());

        $otherLogin = PasswordCredentialBuilder::sample('login')->value;
        $this->uniqueValues->reserve($loginKey, $otherLogin, PasswordCredentialBuilder::sample('id')->toString());

        // When
        $this->trigger(ReleaseLoginOnIdentityErased::class, new IdentityErasedIntegrationEvent($identityId, Clock::get()->now()));

        // Then
        self::assertFalse($this->uniqueValues->exists($loginKey, $login));
        self::assertTrue($this->uniqueValues->exists($loginKey, $otherLogin));
    }
}
