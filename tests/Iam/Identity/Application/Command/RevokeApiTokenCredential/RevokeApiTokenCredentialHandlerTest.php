<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\RevokeApiTokenCredential;

use Iam\Identity\Application\Command\IssueApiTokenCredential\IssueApiTokenCredential;
use Iam\Identity\Application\Command\RevokeApiTokenCredential\RevokeApiTokenCredential;
use Iam\Identity\Application\Finder\ApiTokenCredential\ApiTokenCredentialFinderInterface;
use Iam\Identity\Domain\Exception\ApiTokenCredentialAlreadyRevokedException;
use Iam\Identity\Domain\Exception\ApiTokenCredentialNotFoundException;
use Iam\Identity\Domain\ValueObject\ApiTokenCredentialId;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Tests\Identity\Support\Factory\ApiTokenCredentialTestFactory;
use Iam\Tests\Identity\Support\Stub\DummySecretHasher;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class RevokeApiTokenCredentialHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRevokesAnApiTokenCredential(): void
    {
        // Given
        $identifier = 'key_'.bin2hex(random_bytes(4));
        $credential = ApiTokenCredentialTestFactory::new()->withIdentifier($identifier)->withHasher(new DummySecretHasher())->create();
        $this->store($credential);

        // When
        $this->dispatch(new RevokeApiTokenCredential($credential->id()->toString()));

        // Then
        $result = $this->service(ApiTokenCredentialFinderInterface::class)->ofIdentifier($identifier);
        self::assertNotNull($result);
        self::assertTrue($result->revoked);
    }

    #[Test]
    public function itReleasesTheLabelSoItCanBeReusedAfterRevoking(): void
    {
        // Given
        $identityId = IdentityId::generate()->toString();
        $revokedId = ApiTokenCredentialId::generate()->toString();
        $this->dispatch(new IssueApiTokenCredential(
            id: $revokedId,
            identityId: $identityId,
            identifier: 'key_'.bin2hex(random_bytes(4)),
            secret: 'S3cr3t!',
            label: 'CI pipeline',
            expiresAt: new \DateTimeImmutable('+1 year +00:00')->format(\DateTimeInterface::ATOM),
        ));
        $this->dispatch(new RevokeApiTokenCredential($revokedId));

        $newIdentifier = 'key_'.bin2hex(random_bytes(4));

        // When
        $this->dispatch(new IssueApiTokenCredential(
            id: ApiTokenCredentialId::generate()->toString(),
            identityId: $identityId,
            identifier: $newIdentifier,
            secret: 'Another$3cr3t',
            label: 'CI pipeline',
            expiresAt: new \DateTimeImmutable('+1 year +00:00')->format(\DateTimeInterface::ATOM),
        ));

        // Then
        $result = $this->service(ApiTokenCredentialFinderInterface::class)->ofIdentifier($newIdentifier);
        self::assertSame('CI pipeline', $result->label);
    }

    #[Test]
    public function itFailsWhenTheCredentialDoesNotExist(): void
    {
        // Then
        $this->expectException(ApiTokenCredentialNotFoundException::class);

        // When
        $this->dispatch(new RevokeApiTokenCredential(ApiTokenCredentialId::generate()->toString()));
    }

    #[Test]
    public function itFailsWhenTheCredentialIsAlreadyRevoked(): void
    {
        // Given
        $credential = ApiTokenCredentialTestFactory::new()->withHasher(new DummySecretHasher())->revoked()->create();
        $this->store($credential);

        // Then
        $this->expectException(ApiTokenCredentialAlreadyRevokedException::class);

        // When
        $this->dispatch(new RevokeApiTokenCredential($credential->id()->toString()));
    }
}
