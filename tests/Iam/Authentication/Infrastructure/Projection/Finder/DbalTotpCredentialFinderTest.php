<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Projection\Finder;

use Iam\Authentication\Application\Finder\TotpCredential\Exception\TotpCredentialResultNotFoundException;
use Iam\Authentication\Application\Finder\TotpCredential\TotpCredentialFinderInterface;
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
        self::assertFalse($result->confirmed);
        self::assertNull($result->confirmedAt);
        self::assertFalse($result->revoked);
        self::assertNull($result->revokedAt);
        self::assertTrue($result->identityAuthenticatable);

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
    public function itGetsByIdentity(): void
    {
        // Given
        $other = TotpCredentialBuilder::new()->withCipher($this->cipher)->create();

        $builder = TotpCredentialBuilder::new()
            ->withCipher($this->cipher)
            ->withVerifier($this->verifier)
            ->confirmed();
        $credential = $builder->create();
        $this->store($other, $credential);

        // When
        $result = $this->finder->ofIdentity($builder['identityId']);

        // Then
        self::assertSame($credential->id->toString(), $result->id);
        self::assertSame($builder['identityId'], $result->identityId);
    }

    #[Test]
    public function itThrowsWhenIdentityNotFound(): void
    {
        // Then
        $this->expectException(TotpCredentialResultNotFoundException::class);

        // When
        $this->finder->ofIdentity(TotpCredentialBuilder::sample('identityId'));
    }
}
