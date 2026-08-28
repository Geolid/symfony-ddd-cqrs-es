<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Domain\PasswordCredential;

use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialChanged;
use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialDefined;
use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialRehashed;
use Iam\Authentication\Domain\PasswordCredential\Exception\SamePasswordException;
use Iam\Authentication\Domain\PasswordCredential\Exception\WeakPasswordException;
use Iam\Authentication\Domain\PasswordCredential\PasswordCredential;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\Login;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Iam\Tests\Authentication\Support\Doubles\StubPasswordHasher;
use Iam\Tests\Authentication\Support\Doubles\StubPasswordStrength;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;

final class PasswordCredentialTest extends AggregateRootTestCase
{
    #[Test]
    public function itDefines(): void
    {
        $identityId = Uuid::uuid7()->toString();
        $id = PasswordCredentialId::forIdentity($identityId);
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $hasher = new StubPasswordHasher();

        $this
            ->given()
            ->when(static fn (): PasswordCredential => PasswordCredential::define(
                $id,
                $identityId,
                Login::fromString('ada.lovelace'),
                Password::fromString('original-password'),
                new StubPasswordStrength(),
                $hasher,
                $now,
            ))
            ->then(new PasswordCredentialDefined(
                $id->toString(),
                $identityId,
                'ada.lovelace',
                $hasher->hash('original-password'),
                $now,
            ));
    }

    #[Test]
    public function itCannotDefineWithWeakPassword(): void
    {
        $identityId = Uuid::uuid7()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given()
            ->when(static fn (): PasswordCredential => PasswordCredential::define(
                PasswordCredentialId::forIdentity($identityId),
                $identityId,
                Login::fromString('ada.lovelace'),
                Password::fromString('original-password'),
                new StubPasswordStrength(sufficient: false),
                new StubPasswordHasher(),
                $now,
            ))
            ->expectsException(WeakPasswordException::class);
    }

    #[Test]
    public function itChanges(): void
    {
        $identityId = Uuid::uuid7()->toString();
        $id = PasswordCredentialId::forIdentity($identityId);
        $hasher = new StubPasswordHasher();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $changedAt = $now->modify('+1 day');

        $this
            ->given(new PasswordCredentialDefined(
                $id->toString(),
                $identityId,
                'ada.lovelace',
                $hasher->hash('original-password'),
                $now,
            ))
            ->when(static fn (PasswordCredential $credential) => $credential->change(
                Password::fromString('updated-password'),
                new StubPasswordStrength(),
                $hasher,
                $changedAt,
            ))
            ->then(new PasswordCredentialChanged(
                $id->toString(),
                $hasher->hash('updated-password'),
                $changedAt,
            ));
    }

    #[Test]
    public function itCannotChangeToWeakPassword(): void
    {
        $identityId = Uuid::uuid7()->toString();
        $id = PasswordCredentialId::forIdentity($identityId);
        $hasher = new StubPasswordHasher();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given(new PasswordCredentialDefined(
                $id->toString(),
                $identityId,
                'ada.lovelace',
                $hasher->hash('original-password'),
                $now,
            ))
            ->when(static fn (PasswordCredential $credential) => $credential->change(
                Password::fromString('updated-password'),
                new StubPasswordStrength(sufficient: false),
                $hasher,
                $now->modify('+1 day'),
            ))
            ->expectsException(WeakPasswordException::class);
    }

    #[Test]
    public function itCannotChangeToSamePassword(): void
    {
        $identityId = Uuid::uuid7()->toString();
        $id = PasswordCredentialId::forIdentity($identityId);
        $hasher = new StubPasswordHasher();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given(new PasswordCredentialDefined(
                $id->toString(),
                $identityId,
                'ada.lovelace',
                $hasher->hash('original-password'),
                $now,
            ))
            ->when(static fn (PasswordCredential $credential) => $credential->change(
                Password::fromString('original-password'),
                new StubPasswordStrength(),
                $hasher,
                $now->modify('+1 day'),
            ))
            ->expectsException(SamePasswordException::class);
    }

    #[Test]
    public function itRehashes(): void
    {
        $identityId = Uuid::uuid7()->toString();
        $id = PasswordCredentialId::forIdentity($identityId);
        $hasher = new StubPasswordHasher();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $rehashedAt = $now->modify('+1 day');

        $this
            ->given(new PasswordCredentialDefined(
                $id->toString(),
                $identityId,
                'ada.lovelace',
                $hasher->hash('original-password'),
                $now,
            ))
            ->when(static fn (PasswordCredential $credential) => $credential->rehash(
                'original-password',
                $hasher,
                $rehashedAt,
            ))
            ->then(new PasswordCredentialRehashed(
                $id->toString(),
                $hasher->hash('original-password'),
                $rehashedAt,
            ));
    }

    protected function aggregateClass(): string
    {
        return PasswordCredential::class;
    }
}
