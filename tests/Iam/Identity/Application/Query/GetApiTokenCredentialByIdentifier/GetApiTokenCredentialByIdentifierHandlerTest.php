<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Query\GetApiTokenCredentialByIdentifier;

use Iam\Identity\Application\Exception\ApiTokenCredentialResultNotFoundException;
use Iam\Identity\Application\Query\GetApiTokenCredentialByIdentifier\GetApiTokenCredentialByIdentifier;
use Iam\Identity\Application\Status\IdentityStatus;
use Iam\Tests\Identity\Support\Doubles\FakeSecretHasher;
use Iam\Tests\Identity\Support\Factory\ApiTokenCredentialTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class GetApiTokenCredentialByIdentifierHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGetsByIdentifier(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $expiresAt = new \DateTimeImmutable('+30 days +00:00')->format(\DateTimeInterface::ATOM);
        $credential = ApiTokenCredentialTestFactory::new()
            ->withIdentityId($identityId)
            ->withIdentifier('key_operator')
            ->withLabel('Operator key')
            ->withSecret('S3cr3tApiKey!')
            ->withExpiresAt(new \DateTimeImmutable($expiresAt))
            ->withHasher(new FakeSecretHasher())
            ->store();

        // When
        $result = $this->ask(new GetApiTokenCredentialByIdentifier('key_operator'));

        // Then
        self::assertSame($credential->id->toString(), $result->id);
        self::assertSame($identityId, $result->identityId);
        self::assertSame('key_operator', $result->identifier);
        self::assertSame('Operator key', $result->label);
        self::assertSame('hashed:S3cr3tApiKey!', $result->hash);
        self::assertFalse($result->revoked);
        self::assertSame($expiresAt, $result->expiresAt->format(\DateTimeInterface::ATOM));
        self::assertSame(IdentityStatus::ACTIVE, $result->identityStatus);
    }

    #[Test]
    public function itFailsWhenTheIdentifierIsUnknown(): void
    {
        // Then
        $this->expectException(ApiTokenCredentialResultNotFoundException::class);

        // When
        $this->ask(new GetApiTokenCredentialByIdentifier('key_unknown'));
    }
}
