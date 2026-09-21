<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Projection\Finder;

use Iam\Authentication\Application\Finder\TotpCredential\Exception\TotpCredentialResultNotFoundException;
use Iam\Authentication\Application\Finder\TotpCredential\TotpCredentialFinderInterface;
use Iam\Tests\Authentication\Support\Builder\TotpCredentialBuilder;
use Iam\Tests\Authentication\Support\Double\FakeTotpCipher;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

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
        $other = TotpCredentialBuilder::new()->withCipher($this->cipher)->create();

        $builder = TotpCredentialBuilder::new()->withCipher($this->cipher);
        $credential = $builder->create();
        $this->store($other, $credential);

        // When
        $result = $this->finder->ofId($credential->id->toString());

        // Then
        self::assertSame($credential->id->toString(), $result->id);
        self::assertSame($builder['identityId'], $result->identityId);
        self::assertSame(
            $builder['issuedAt']->format(\DateTimeInterface::ATOM),
            $result->issuedAt->format(\DateTimeInterface::ATOM),
        );
        self::assertFalse($result->revoked);
        self::assertNull($result->revokedAt);
        self::assertSame($this->cipher->encrypt($builder['secret']), $result->encryptedSecret);
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
        $other = TotpCredentialBuilder::new()->withCipher($this->cipher)->create();

        $builder = TotpCredentialBuilder::new()->withCipher($this->cipher);
        $credential = $builder->create();

        $revokedBuilder = TotpCredentialBuilder::new()->withCipher($this->cipher)->revoked();
        $revokedCredential = $revokedBuilder->create();

        $this->store($other, $credential, $revokedCredential);

        // When
        $result = $this->finder->activeOfIdentityOrNull($builder['identityId']);
        $revokedResult = $this->finder->activeOfIdentityOrNull($revokedBuilder['identityId']);
        $nothing = $this->finder->activeOfIdentityOrNull(TotpCredentialBuilder::sample('identityId'));

        // Then
        self::assertNotNull($result);
        self::assertSame($credential->id->toString(), $result->id);
        self::assertSame($builder['identityId'], $result->identityId);

        self::assertNull($revokedResult);
        self::assertNull($nothing);
    }
}
