<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\EnrollTotpCredential;

use Iam\Authentication\Application\AuthenticationUniqueKey;
use Iam\Authentication\Application\Command\EnrollTotpCredential\EnrollTotpCredential;
use Iam\Authentication\Application\Command\EnrollTotpCredential\Exception\TotpAlreadyEnrolledException;
use Iam\Authentication\Application\Finder\TotpCredential\TotpCredentialFinderInterface;
use Iam\Tests\Authentication\Support\Builder\TotpCredentialBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class EnrollTotpCredentialHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itEnrolls(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $identityId = TotpCredentialBuilder::sample('identityId');
        $secret = TotpCredentialBuilder::sample('secret');
        $now = Clock::get()->now();

        // When
        $this->dispatch(new EnrollTotpCredential($id, $identityId, $secret));

        // Then
        $result = $this->service(TotpCredentialFinderInterface::class)->ofId($id);
        self::assertSame($id, $result->id);
        self::assertSame($identityId, $result->identityId);
        self::assertSame(
            $now->format(\DateTimeInterface::ATOM),
            $result->enrolledAt->format(\DateTimeInterface::ATOM),
        );
        self::assertFalse($result->unenrolled);
        self::assertNull($result->unenrolledAt);

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
        $this->dispatch(new EnrollTotpCredential(
            Uuid::uuid7()->toString(),
            $identityId,
            TotpCredentialBuilder::sample('secret'),
        ));
    }
}
