<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Projection\Finder;

use Iam\Authentication\Application\Finder\TotpCredential\Exception\TotpCredentialResultNotFoundException;
use Iam\Authentication\Application\Finder\TotpCredential\TotpCredentialFinderInterface;
use Iam\Authentication\Application\TotpCredentialStatus;
use Iam\Tests\Authentication\Support\Builder\TotpCredentialBuilder;
use Iam\Tests\Authentication\Support\Double\FakeTotpCipher;
use Iam\Tests\Authentication\Support\Double\FakeTotpVerifier;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class DbalTotpCredentialFinderTest extends AbstractIntegrationTestCase
{
    private TotpCredentialFinderInterface $finder;
    private FakeTotpCipher $cipher;
    private FakeTotpVerifier $verifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(TotpCredentialFinderInterface::class);
        $this->cipher = new FakeTotpCipher();
        $this->verifier = new FakeTotpVerifier();
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
            $builder['enrolledAt']->format(\DateTimeInterface::ATOM),
            $result->enrolledAt->format(\DateTimeInterface::ATOM),
        );
        self::assertSame(TotpCredentialStatus::PENDING, $result->status);
        self::assertNull($result->confirmedAt);
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
    public function itFindsByIdentity(): void
    {
        // Given
        $other = TotpCredentialBuilder::new()->withCipher($this->cipher)->create();

        $builder = TotpCredentialBuilder::new()
            ->withCipher($this->cipher)
            ->withVerifier($this->verifier)
            ->confirmed();
        $credential = $builder->create();

        $revokedBuilder = TotpCredentialBuilder::new()->withCipher($this->cipher)->revoked();
        $revokedCredential = $revokedBuilder->create();

        $this->store($other, $credential, $revokedCredential);

        // When
        $result = $this->finder->confirmedOfIdentityOrNull($builder['identityId']);
        $revokedResult = $this->finder->confirmedOfIdentityOrNull($revokedBuilder['identityId']);
        $nothing = $this->finder->confirmedOfIdentityOrNull(TotpCredentialBuilder::sample('identityId'));

        // Then
        self::assertNotNull($result);
        self::assertSame($credential->id->toString(), $result->id);
        self::assertSame($builder['identityId'], $result->identityId);

        self::assertNull($revokedResult);
        self::assertNull($nothing);
    }
}
