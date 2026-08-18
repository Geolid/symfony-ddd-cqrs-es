<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Processor;

use Iam\Identity\Application\Finder\ApiTokenCredential\ApiTokenCredentialFinderInterface;
use Iam\Identity\Application\Processor\RevokeApiTokenCredentialsOnIdentityErased;
use Iam\Identity\Domain\Event\IdentityErased;
use Iam\Tests\Identity\Support\Doubles\FakeSecretHasher;
use Iam\Tests\Identity\Support\Factory\ApiTokenCredentialTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class RevokeApiTokenCredentialsOnIdentityErasedTest extends AbstractIntegrationTestCase
{
    private RevokeApiTokenCredentialsOnIdentityErased $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = $this->service(RevokeApiTokenCredentialsOnIdentityErased::class);
    }

    #[Test]
    public function itRevokesActiveCredentialsOnIdentityErased(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        ApiTokenCredentialTestFactory::new()->withIdentityId($identityId)->withHasher(new FakeSecretHasher())->store();
        $other = ApiTokenCredentialTestFactory::new()->withHasher(new FakeSecretHasher())->store();

        // When
        ($this->processor)(new IdentityErased($identityId, '2026-01-02T00:00:00+00:00'));

        // Then
        $finder = $this->service(ApiTokenCredentialFinderInterface::class);
        self::assertCount(0, $finder->byIdentity($identityId)->active());
        self::assertCount(1, $finder->byIdentity($other->identityId->toString())->active());
    }

    #[Test]
    public function itDoesNothingWhenNoCredentialsExistForTheIdentity(): void
    {
        // Given
        $other = ApiTokenCredentialTestFactory::new()->withHasher(new FakeSecretHasher())->store();

        // When
        ($this->processor)(new IdentityErased(Uuid::uuid7()->toString(), '2026-01-02T00:00:00+00:00'));

        // Then
        self::assertCount(1, $this->service(ApiTokenCredentialFinderInterface::class)->byIdentity($other->identityId->toString())->active());
    }
}
