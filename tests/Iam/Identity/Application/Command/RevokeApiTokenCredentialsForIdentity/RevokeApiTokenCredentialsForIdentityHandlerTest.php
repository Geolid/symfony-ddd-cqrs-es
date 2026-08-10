<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\RevokeApiTokenCredentialsForIdentity;

use Iam\Identity\Application\Command\RevokeApiTokenCredentialsForIdentity\RevokeApiTokenCredentialsForIdentity;
use Iam\Identity\Application\Finder\ApiTokenCredential\ApiTokenCredentialFinderInterface;
use Iam\Tests\Identity\Support\Factory\ApiTokenCredentialTestFactory;
use Iam\Tests\Identity\Support\Stub\DummySecretHasher;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class RevokeApiTokenCredentialsForIdentityHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRevokesEveryActiveCredentialOfTheIdentity(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $this->store(ApiTokenCredentialTestFactory::new()->withIdentityId($identityId)->withHasher(new DummySecretHasher())->create());
        $this->store(ApiTokenCredentialTestFactory::new()->withIdentityId($identityId)->withHasher(new DummySecretHasher())->create());
        $other = ApiTokenCredentialTestFactory::new()->withHasher(new DummySecretHasher())->create();
        $this->store($other);

        // When
        $this->dispatch(new RevokeApiTokenCredentialsForIdentity($identityId));

        // Then
        $finder = $this->service(ApiTokenCredentialFinderInterface::class);
        self::assertCount(0, $finder->byIdentity($identityId)->active());
        self::assertCount(1, $finder->byIdentity($other->identityId()->toString())->active());
    }

    #[Test]
    public function itIgnoresAnIdentityWithNoCredentials(): void
    {
        // When
        $this->dispatch(new RevokeApiTokenCredentialsForIdentity(Uuid::uuid7()->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
