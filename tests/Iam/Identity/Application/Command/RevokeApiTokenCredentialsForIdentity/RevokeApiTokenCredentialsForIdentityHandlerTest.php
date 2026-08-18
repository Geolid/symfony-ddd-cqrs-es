<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\RevokeApiTokenCredentialsForIdentity;

use Iam\Identity\Application\Command\RevokeApiTokenCredentialsForIdentity\RevokeApiTokenCredentialsForIdentity;
use Iam\Identity\Application\Finder\ApiTokenCredential\ApiTokenCredentialFinderInterface;
use Iam\Tests\Identity\Support\Doubles\FakeSecretHasher;
use Iam\Tests\Identity\Support\Factory\ApiTokenCredentialTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class RevokeApiTokenCredentialsForIdentityHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRevokesActiveCredentialsOfTheIdentity(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        ApiTokenCredentialTestFactory::new()->withIdentityId($identityId)->withHasher(new FakeSecretHasher())->store();
        ApiTokenCredentialTestFactory::new()->withIdentityId($identityId)->withHasher(new FakeSecretHasher())->store();
        $other = ApiTokenCredentialTestFactory::new()->withHasher(new FakeSecretHasher())->store();

        // When
        $this->dispatch(new RevokeApiTokenCredentialsForIdentity($identityId));

        // Then
        $finder = $this->service(ApiTokenCredentialFinderInterface::class);
        self::assertCount(0, $finder->byIdentity($identityId)->active());
        self::assertCount(1, $finder->byIdentity($other->identityId->toString())->active());
    }

    #[Test]
    public function itIgnoresAnIdentityWithNoCredentials(): void
    {
        // Given
        $other = ApiTokenCredentialTestFactory::new()->withHasher(new FakeSecretHasher())->store();

        // When
        $this->dispatch(new RevokeApiTokenCredentialsForIdentity(Uuid::uuid7()->toString()));

        // Then
        self::assertCount(1, $this->service(ApiTokenCredentialFinderInterface::class)->byIdentity($other->identityId->toString())->active());
    }
}
