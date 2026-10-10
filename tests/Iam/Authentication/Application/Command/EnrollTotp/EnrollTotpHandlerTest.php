<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\EnrollTotp;

use Iam\Authentication\Application\AuthenticationUniqueKey;
use Iam\Authentication\Application\Command\EnrollTotp\EnrollTotp;
use Iam\Authentication\Application\Command\EnrollTotp\Exception\TotpAlreadyEnrolledException;
use Iam\Authentication\Application\Finder\TotpCredential\TotpCredentialFinderInterface;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

use function Zenstruck\Foundry\faker;

final class EnrollTotpHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itEnrolls(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $identityId = Uuid::uuid7()->toString();
        $secret = faker()->totpSecret();
        $now = Clock::get()->now();

        // When
        $this->dispatch(new EnrollTotp($id, $identityId, $secret));

        // Then
        $result = $this->service(TotpCredentialFinderInterface::class)->ofId($id);
        self::assertSame($id, $result->id);
        self::assertSame($identityId, $result->identityId);
        self::assertSameDate($now, $result->enrolledAt);
        self::assertFalse($result->unenrolled);
        self::assertNull($result->unenrolledAt);

        self::assertNotSame($secret, $result->encryptedSecret);
    }

    #[Test]
    public function itFailsWhenIdentityAlreadyEnrolled(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $this->service(UniquenessRegistryInterface::class)->claim(
            UniqueKey::for(AuthenticationUniqueKey::TOTP_CREDENTIAL_IDENTITY),
            $identityId,
            Uuid::uuid7()->toString(),
        );

        // Then
        $this->expectException(TotpAlreadyEnrolledException::class);

        // When
        $this->dispatch(new EnrollTotp(
            Uuid::uuid7()->toString(),
            $identityId,
            faker()->totpSecret(),
        ));
    }
}
