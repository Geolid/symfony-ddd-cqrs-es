<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\RevokeApiTokenCredential;

use Iam\Identity\Application\Command\RevokeApiTokenCredential\RevokeApiTokenCredential;
use Iam\Identity\Application\Finder\ApiTokenCredential\ApiTokenCredentialFinderInterface;
use Iam\Identity\Domain\Exception\ApiTokenCredentialNotFoundException;
use Iam\Identity\Domain\ValueObject\ApiTokenCredentialId;
use Iam\Tests\Identity\Support\Doubles\FakeSecretHasher;
use Iam\Tests\Identity\Support\Factory\ApiTokenCredentialTestFactory;
use Iam\Tests\Identity\Support\Helpers\ApiTokenTrait;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class RevokeApiTokenCredentialHandlerTest extends AbstractIntegrationTestCase
{
    use ApiTokenTrait;

    #[Test]
    public function itRevokesAnApiTokenCredential(): void
    {
        // Given
        $identifier = $this->generateIdentifier();
        $credential = ApiTokenCredentialTestFactory::new()->withIdentifier($identifier)->withHasher(new FakeSecretHasher())->store();

        // When
        $this->dispatch(new RevokeApiTokenCredential($credential->id()->toString()));

        // Then
        $result = $this->service(ApiTokenCredentialFinderInterface::class)->ofIdentifier($identifier);
        self::assertNotNull($result);
        self::assertTrue($result->revoked);
    }

    #[Test]
    public function itFailsWhenTheCredentialDoesNotExist(): void
    {
        // Given
        $id = ApiTokenCredentialId::generate()->toString();

        // Then
        $this->expectException(ApiTokenCredentialNotFoundException::class);

        // When
        $this->dispatch(new RevokeApiTokenCredential($id));
    }

    #[Test]
    public function itIgnoresAnAlreadyRevokedCredential(): void
    {
        // Given
        $credential = ApiTokenCredentialTestFactory::new()->withHasher(new FakeSecretHasher())->revoked()->store();

        // When
        $this->dispatch(new RevokeApiTokenCredential($credential->id()->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
