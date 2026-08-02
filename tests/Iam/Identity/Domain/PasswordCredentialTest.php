<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Domain;

use Iam\Identity\Domain\Event\PasswordCredentialChanged;
use Iam\Identity\Domain\Event\PasswordCredentialSet;
use Iam\Identity\Domain\IdentityId;
use Iam\Identity\Domain\Login;
use Iam\Identity\Domain\PasswordCredential;
use Iam\Identity\Domain\PasswordCredentialId;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;

final class PasswordCredentialTest extends AggregateRootTestCase
{
    #[Test]
    public function itSetsAPasswordCredential(): void
    {
        $id = PasswordCredentialId::generate();
        $identityId = IdentityId::generate();
        $login = Login::fromString('operator@example.com');
        $setAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $hasher = new DummySecretHasher();

        $this
            ->given()
            ->when(static fn () => PasswordCredential::set($id, $identityId, $login, 'S3cr3t!', $hasher, $setAt))
            ->then(new PasswordCredentialSet($id->toString(), $identityId->toString(), 'operator@example.com', $hasher->hash('S3cr3t!'), $setAt->format('c')));
    }

    #[Test]
    public function itChangesAPasswordCredential(): void
    {
        $id = PasswordCredentialId::generate()->toString();
        $identityId = IdentityId::generate()->toString();
        $setAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $changedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $hasher = new DummySecretHasher();

        $this
            ->given(new PasswordCredentialSet($id, $identityId, 'operator@example.com', $hasher->hash('OldS3cr3t!'), $setAt->format('c')))
            ->when(static fn (PasswordCredential $credential) => $credential->change('NewS3cr3t!', $hasher, $changedAt))
            ->then(new PasswordCredentialChanged($id, $hasher->hash('NewS3cr3t!'), $changedAt->format('c')));
    }

    #[Test]
    public function itVerifiesTheCorrectPassword(): void
    {
        // Given
        $id = PasswordCredentialId::generate()->toString();
        $identityId = IdentityId::generate()->toString();
        $setAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $hasher = new DummySecretHasher();
        $correctResult = null;
        $wrongResult = null;

        // When
        $this
            ->given(new PasswordCredentialSet($id, $identityId, 'operator@example.com', $hasher->hash('S3cr3t!'), $setAt->format('c')))
            ->when(static function (PasswordCredential $credential) use ($hasher, &$correctResult, &$wrongResult): void {
                $correctResult = $credential->verify('S3cr3t!', $hasher);
                $wrongResult = $credential->verify('wrong', $hasher);
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
