<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Projection\Finder;

use Iam\Authentication\Application\Finder\TotpCredential\Exception\TotpCredentialResultNotFoundException;
use Iam\Authentication\Application\Finder\TotpCredential\TotpCredentialFinderInterface;
use Iam\Tests\Authentication\Support\Double\FakeTotpCipher;
use Iam\Tests\Authentication\Support\Factory\TotpCredentialFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

use function Zenstruck\Foundry\faker;

final class DbalTotpCredentialFinderTest extends AbstractIntegrationTestCase
{
    private TotpCredentialFinderInterface $finder;
    private FakeTotpCipher $cipher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(TotpCredentialFinderInterface::class);
        $this->cipher = new FakeTotpCipher();
    }

    #[Test]
    public function itGetsById(): void
    {
        // Given
        $other = TotpCredentialFactory::new()->withCipher($this->cipher)->create();
        $secret = faker()->totpSecret();

        $credential = TotpCredentialFactory::new()->withSecret($secret)->withCipher($this->cipher)->create();
        $this->store($other, $credential);

        // When
        $result = $this->finder->ofId($credential->id->toString());

        // Then
        self::assertSame($credential->id->toString(), $result->id);
        self::assertSame($credential->identityId, $result->identityId);
        self::assertSameDate($credential->enrolledAt, $result->enrolledAt);
        self::assertFalse($result->unenrolled);
        self::assertNull($result->unenrolledAt);
        self::assertSame($this->cipher->encrypt($secret), $result->encryptedSecret);
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Then
        $this->expectException(TotpCredentialResultNotFoundException::class);

        // When
        $this->finder->ofId(Uuid::uuid7()->toString());
    }

    #[Test]
    public function itFindsActiveByIdentity(): void
    {
        // Given
        $other = TotpCredentialFactory::new()->withCipher($this->cipher)->create();

        $credential = TotpCredentialFactory::new()->withCipher($this->cipher)->create();
        $this->store($other, $credential);

        // When
        $result = $this->finder->activeOfIdentityOrNull($credential->identityId);

        // Then
        self::assertNotNull($result);
        self::assertSame($credential->id->toString(), $result->id);
        self::assertSame($credential->identityId, $result->identityId);
    }

    #[Test]
    public function itFindsNothingWhenUnenrolled(): void
    {
        // Given
        $credential = TotpCredentialFactory::new()->withCipher($this->cipher)->unenrolled()->create();
        $this->store($credential);

        // When
        $result = $this->finder->activeOfIdentityOrNull($credential->identityId);

        // Then
        self::assertNull($result);
    }

    #[Test]
    public function itFindsNothingWhenNotEnrolled(): void
    {
        // When
        $result = $this->finder->activeOfIdentityOrNull(Uuid::uuid7()->toString());

        // Then
        self::assertNull($result);
    }
}
