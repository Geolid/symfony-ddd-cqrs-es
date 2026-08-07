<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Domain;

use Iam\Identity\Domain\Event\PasswordCredentialChanged;
use Iam\Identity\Domain\Event\PasswordCredentialSet;
use Iam\Identity\Domain\PasswordCredential;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\Login;
use Iam\Identity\Domain\ValueObject\PasswordCredentialId;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;

final class PasswordCredentialTest extends AggregateRootTestCase
{
    private SecretHasherInterface $hasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasher = new DummySecretHasher();
    }

    #[Test]
    public function itSetsAPasswordCredential(): void
    {
        $id = PasswordCredentialId::generate();
        $identityId = IdentityId::generate();
        $login = Login::fromString('operator@example.com');
        $setAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given()
            ->when(fn () => PasswordCredential::set($id, $identityId, $login, 'S3cr3t!', $this->hasher, $setAt))
            ->then(new PasswordCredentialSet($id->toString(), $identityId->toString(), 'operator@example.com', $this->hasher->hash('S3cr3t!'), $setAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itChangesAPasswordCredential(): void
    {
        $id = PasswordCredentialId::generate()->toString();
        $identityId = IdentityId::generate()->toString();
        $setAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $changedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(new PasswordCredentialSet($id, $identityId, 'operator@example.com', $this->hasher->hash('OldS3cr3t!'), $setAt->format(\DateTimeInterface::ATOM)))
            ->when(fn (PasswordCredential $credential) => $credential->change('NewS3cr3t!', $this->hasher, $changedAt))
            ->then(new PasswordCredentialChanged($id, $this->hasher->hash('NewS3cr3t!'), $changedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itVerifiesTheCorrectPassword(): void
    {
        // Given
        $id = PasswordCredentialId::generate()->toString();
        $identityId = IdentityId::generate()->toString();
        $setAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $correctResult = null;
        $wrongResult = null;

        // When
        $this
            ->given(new PasswordCredentialSet($id, $identityId, 'operator@example.com', $this->hasher->hash('S3cr3t!'), $setAt->format(\DateTimeInterface::ATOM)))
            ->when(function (PasswordCredential $credential) use (&$correctResult, &$wrongResult): void {
                $correctResult = $credential->verify('S3cr3t!', $this->hasher);
                $wrongResult = $credential->verify('wrong', $this->hasher);
            })
            ->then();

        // Then
        self::assertTrue($correctResult);
        self::assertFalse($wrongResult);
    }

    protected function aggregateClass(): string
    {
        return PasswordCredential::class;
    }
}

final class DummySecretHasher implements SecretHasherInterface
{
    public function hash(string $secret): string
    {
        return 'hashed:'.$secret;
    }

    public function verify(string $hash, string $secret): bool
    {
        return $hash === $this->hash($secret);
    }
}
