<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\IssueTotpCredential;

use Iam\Authentication\Application\AuthenticationUniqueKey;
use Iam\Authentication\Application\Command\IssueTotpCredential\Exception\TotpAlreadyEnrolledException;
use Iam\Authentication\Application\Command\IssueTotpCredential\IssueTotpCredential;
use Iam\Authentication\Application\Finder\TotpCredential\TotpCredentialFinderInterface;
use Iam\Tests\Authentication\Support\Builder\TotpCredentialBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class IssueTotpCredentialHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itIssues(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $identityId = TotpCredentialBuilder::sample('identityId');
        $secret = TotpCredentialBuilder::sample('secret');
        $now = Clock::get()->now();

        // When
        $this->dispatch(new IssueTotpCredential($id, $identityId, $secret));

        // Then
        $result = $this->service(TotpCredentialFinderInterface::class)->ofId($id);
        self::assertSame($id, $result->id);
        self::assertSame($identityId, $result->identityId);
        self::assertSame(
            $now->format(\DateTimeInterface::ATOM),
            $result->issuedAt->format(\DateTimeInterface::ATOM),
        );
        self::assertFalse($result->revoked);
        self::assertNull($result->revokedAt);

        self::assertNotSame($secret, $result->encryptedSecret);
    }

    #[Test]
    public function itFailsWhenIdentityAlreadyEnrolled(): void
    {
        // Given
        $identityId = TotpCredentialBuilder::sample('identityId');
        $this->service(UniquenessRegistryInterface::class)->claim(
            UniqueKey::for(AuthenticationUniqueKey::TOTP_CREDENTIAL_IDENTITY),
            $identityId,
            Uuid::uuid7()->toString(),
        );

        // Then
        $this->expectException(TotpAlreadyEnrolledException::class);

        // When
        $this->dispatch(new IssueTotpCredential(
            Uuid::uuid7()->toString(),
            $identityId,
            TotpCredentialBuilder::sample('secret'),
        ));
    }
}
