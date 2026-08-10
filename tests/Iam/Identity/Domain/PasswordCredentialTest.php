<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Domain;

use Iam\Identity\Domain\Event\PasswordCredentialChanged;
use Iam\Identity\Domain\Event\PasswordCredentialRehashed;
use Iam\Identity\Domain\Event\PasswordCredentialSet;
use Iam\Identity\Domain\PasswordCredential;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\Login;
use Iam\Identity\Domain\ValueObject\PasswordCredentialId;
use Iam\Tests\Identity\Support\Stub\DummySecretHasher;
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
    public function itSets(): void
    {
        $identityId = IdentityId::generate();
        $id = PasswordCredentialId::forIdentity($identityId->toString());
        $login = Login::fromString('operator');
        $setAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given()
            ->when(fn () => PasswordCredential::set($id, $identityId, $login, 'S3cr3t!', $this->hasher, $setAt))
            ->then(new PasswordCredentialSet($id->toString(), $identityId->toString(), 'operator', $this->hasher->hash('S3cr3t!'), $setAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itChanges(): void
    {
        $identityId = IdentityId::generate()->toString();
        $id = PasswordCredentialId::forIdentity($identityId)->toString();
        $setAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $changedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(new PasswordCredentialSet($id, $identityId, 'operator', $this->hasher->hash('OldS3cr3t!'), $setAt->format(\DateTimeInterface::ATOM)))
            ->when(fn (PasswordCredential $credential) => $credential->change('NewS3cr3t!', $this->hasher, $changedAt))
            ->then(new PasswordCredentialChanged($id, $this->hasher->hash('NewS3cr3t!'), $changedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itRehashes(): void
    {
        $identityId = IdentityId::generate()->toString();
        $id = PasswordCredentialId::forIdentity($identityId)->toString();
        $setAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $rehashedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(new PasswordCredentialSet($id, $identityId, 'operator', $this->hasher->hash('S3cr3t!'), $setAt->format(\DateTimeInterface::ATOM)))
            ->when(fn (PasswordCredential $credential) => $credential->rehash('S3cr3t!', $this->hasher, $rehashedAt))
            ->then(new PasswordCredentialRehashed($id, $this->hasher->hash('S3cr3t!'), $rehashedAt->format(\DateTimeInterface::ATOM)));
    }

    protected function aggregateClass(): string
    {
        return PasswordCredential::class;
    }
}
