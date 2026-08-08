<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\ReleasePasswordCredentialLogin;

use Iam\Identity\Application\Command\ReleasePasswordCredentialLogin\ReleasePasswordCredentialLogin;
use Iam\Identity\Domain\ValueObject\Login;
use Iam\Identity\Domain\ValueObject\PasswordCredentialUniqueValue;
use Iam\Tests\Identity\Support\Factory\PasswordCredentialTestFactory;
use Iam\Tests\Identity\Support\Stub\DummySecretHasher;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Support\AbstractIntegrationTestCase;

final class ReleasePasswordCredentialLoginHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itReleasesTheLogin(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identityId)
            ->withLogin('operator')
            ->withHasher(new DummySecretHasher())
            ->create();
        $this->store($credential);
        $fingerprint = Login::fromString('operator')->fingerprint();
        $this->service(UniqueValueRegistryInterface::class)->reserve(PasswordCredentialUniqueValue::LOGIN, $fingerprint);

        // When
        $this->dispatch(new ReleasePasswordCredentialLogin($identityId));

        // Then
        self::assertFalse($this->service(UniqueValueRegistryInterface::class)->exists(PasswordCredentialUniqueValue::LOGIN, $fingerprint));
    }

    #[Test]
    public function itDoesNothingWhenNoCredentialExistsForTheIdentity(): void
    {
        // When
        $this->dispatch(new ReleasePasswordCredentialLogin(Uuid::uuid7()->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
