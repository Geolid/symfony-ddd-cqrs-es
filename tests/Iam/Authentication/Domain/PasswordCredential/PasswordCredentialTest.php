<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Domain\PasswordCredential;

use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialChanged;
use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialDefined;
use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialRehashed;
use Iam\Authentication\Domain\PasswordCredential\Exception\CompromisedPasswordException;
use Iam\Authentication\Domain\PasswordCredential\Exception\SamePasswordException;
use Iam\Authentication\Domain\PasswordCredential\Exception\WeakPasswordException;
use Iam\Authentication\Domain\PasswordCredential\PasswordCredential;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\Login;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Iam\Tests\Authentication\Support\Doubles\StubPasswordHasher;
use Iam\Tests\Authentication\Support\Doubles\StubPasswordPolicy;
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
        $definedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $hasher = new StubPasswordHasher();

        $this
            ->given()
            ->when(static fn (): PasswordCredential => PasswordCredential::define(
                $id,
                $identityId,
                Login::fromString('ada.lovelace'),
                Password::fromString('Xk9$mQ2vLp7&zR4w'),
                new StubPasswordPolicy(),
                $hasher,
                $definedAt,
            ))
            ->then(new PasswordCredentialDefined(
                $id->toString(),
                $identityId,
                'ada.lovelace',
                $hasher->hash('Xk9$mQ2vLp7&zR4w'),
                $definedAt->format(\DateTimeInterface::ATOM),
            ));
    }

    #[Test]
    public function itCannotDefineWithWeakPassword(): void
    {
        $identityId = Uuid::uuid7()->toString();

        $this
            ->given()
            ->when(static fn (): PasswordCredential => PasswordCredential::define(
                PasswordCredentialId::forIdentity($identityId),
                $identityId,
                Login::fromString('ada.lovelace'),
                Password::fromString('Xk9$mQ2vLp7&zR4w'),
                new StubPasswordPolicy(strongEnough: false),
                new StubPasswordHasher(),
                new \DateTimeImmutable('2026-01-01T00:00:00+00:00'),
            ))
            ->expectsException(WeakPasswordException::class);
    }

    #[Test]
    public function itCannotDefineWithCompromisedPassword(): void
    {
        $identityId = Uuid::uuid7()->toString();

        $this
            ->given()
            ->when(static fn (): PasswordCredential => PasswordCredential::define(
                PasswordCredentialId::forIdentity($identityId),
                $identityId,
                Login::fromString('ada.lovelace'),
                Password::fromString('Xk9$mQ2vLp7&zR4w'),
                new StubPasswordPolicy(compromised: true),
                new StubPasswordHasher(),
                new \DateTimeImmutable('2026-01-01T00:00:00+00:00'),
            ))
            ->expectsException(CompromisedPasswordException::class);
    }

    #[Test]
    public function itChanges(): void
    {
        $identityId = Uuid::uuid7()->toString();
        $id = PasswordCredentialId::forIdentity($identityId);
        $hasher = new StubPasswordHasher();
        $changedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(new PasswordCredentialDefined(
                $id->toString(),
                $identityId,
                'ada.lovelace',
                $hasher->hash('Xk9$mQ2vLp7&zR4w'),
                '2026-01-01T00:00:00+00:00',
            ))
            ->when(static fn (PasswordCredential $credential) => $credential->change(
                Password::fromString('Qm3&nJ8wXv5Tz1p!'),
                new StubPasswordPolicy(),
                $hasher,
                $changedAt,
            ))
            ->then(new PasswordCredentialChanged(
                $id->toString(),
                $hasher->hash('Qm3&nJ8wXv5Tz1p!'),
                $changedAt->format(\DateTimeInterface::ATOM),
            ));
    }

    #[Test]
    public function itCannotChangeToWeakPassword(): void
    {
        $identityId = Uuid::uuid7()->toString();
        $id = PasswordCredentialId::forIdentity($identityId);
        $hasher = new StubPasswordHasher();

        $this
            ->given(new PasswordCredentialDefined(
                $id->toString(),
                $identityId,
                'ada.lovelace',
                $hasher->hash('Xk9$mQ2vLp7&zR4w'),
                '2026-01-01T00:00:00+00:00',
            ))
            ->when(static fn (PasswordCredential $credential) => $credential->change(
                Password::fromString('Qm3&nJ8wXv5Tz1p!'),
                new StubPasswordPolicy(strongEnough: false),
                $hasher,
                new \DateTimeImmutable('2026-01-02T00:00:00+00:00'),
            ))
            ->expectsException(WeakPasswordException::class);
    }

    #[Test]
    public function itCannotChangeToCompromisedPassword(): void
    {
        $identityId = Uuid::uuid7()->toString();
        $id = PasswordCredentialId::forIdentity($identityId);
        $hasher = new StubPasswordHasher();

        $this
            ->given(new PasswordCredentialDefined(
                $id->toString(),
                $identityId,
                'ada.lovelace',
                $hasher->hash('Xk9$mQ2vLp7&zR4w'),
                '2026-01-01T00:00:00+00:00',
            ))
            ->when(static fn (PasswordCredential $credential) => $credential->change(
                Password::fromString('Qm3&nJ8wXv5Tz1p!'),
                new StubPasswordPolicy(compromised: true),
                $hasher,
                new \DateTimeImmutable('2026-01-02T00:00:00+00:00'),
            ))
            ->expectsException(CompromisedPasswordException::class);
    }

    #[Test]
    public function itCannotChangeToSamePassword(): void
    {
        $identityId = Uuid::uuid7()->toString();
        $id = PasswordCredentialId::forIdentity($identityId);
        $hasher = new StubPasswordHasher();

        $this
            ->given(new PasswordCredentialDefined(
                $id->toString(),
                $identityId,
                'ada.lovelace',
                $hasher->hash('Xk9$mQ2vLp7&zR4w'),
                '2026-01-01T00:00:00+00:00',
            ))
            ->when(static fn (PasswordCredential $credential) => $credential->change(
                Password::fromString('Xk9$mQ2vLp7&zR4w'),
                new StubPasswordPolicy(),
                $hasher,
                new \DateTimeImmutable('2026-01-02T00:00:00+00:00'),
            ))
            ->expectsException(SamePasswordException::class);
    }

    #[Test]
    public function itRehashes(): void
    {
        $identityId = Uuid::uuid7()->toString();
        $id = PasswordCredentialId::forIdentity($identityId);
        $hasher = new StubPasswordHasher();
        $rehashedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(new PasswordCredentialDefined(
                $id->toString(),
                $identityId,
                'ada.lovelace',
                $hasher->hash('Xk9$mQ2vLp7&zR4w'),
                '2026-01-01T00:00:00+00:00',
            ))
            ->when(static fn (PasswordCredential $credential) => $credential->rehash(
                'Xk9$mQ2vLp7&zR4w',
                $hasher,
                $rehashedAt,
            ))
            ->then(new PasswordCredentialRehashed(
                $id->toString(),
                $hasher->hash('Xk9$mQ2vLp7&zR4w'),
                $rehashedAt->format(\DateTimeInterface::ATOM),
            ));
    }

    protected function aggregateClass(): string
    {
        return PasswordCredential::class;
    }
}
