<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Domain;

use Iam\Identity\Domain\Event\PasswordCredentialChanged;
use Iam\Identity\Domain\Event\PasswordCredentialDefined;
use Iam\Identity\Domain\Event\PasswordCredentialRehashed;
use Iam\Identity\Domain\Exception\CompromisedPasswordException;
use Iam\Identity\Domain\Exception\PasswordUnchangedException;
use Iam\Identity\Domain\Exception\WeakPasswordException;
use Iam\Identity\Domain\PasswordCredential;
use Iam\Identity\Domain\Service\PasswordPolicyInterface;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\Login;
use Iam\Identity\Domain\ValueObject\Password;
use Iam\Identity\Domain\ValueObject\PasswordCredentialId;
use Iam\Tests\Identity\Support\Doubles\FakeSecretHasher;
use Iam\Tests\Identity\Support\Doubles\StubPasswordPolicy;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;

final class PasswordCredentialTest extends AggregateRootTestCase
{
    private SecretHasherInterface $hasher;
    private PasswordPolicyInterface $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasher = new FakeSecretHasher();
        $this->policy = new StubPasswordPolicy();
    }

    #[Test]
    public function itDefines(): void
    {
        $identityId = IdentityId::generate();
        $id = PasswordCredentialId::forIdentity($identityId->toString());
        $login = Login::fromString('operator');
        $definedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given()
            ->when(fn () => PasswordCredential::define($id, $identityId, $login, Password::fromString('MyStr0ngP@ssw0rd123!'), $this->policy, $this->hasher, $definedAt))
            ->then(new PasswordCredentialDefined($id->toString(), $identityId->toString(), 'operator', $this->hasher->hash('MyStr0ngP@ssw0rd123!'), $definedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itChanges(): void
    {
        $identityId = IdentityId::generate()->toString();
        $id = PasswordCredentialId::forIdentity($identityId)->toString();
        $definedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $changedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(new PasswordCredentialDefined($id, $identityId, 'operator', $this->hasher->hash('OldStr0ngP@ssw0rd123!'), $definedAt->format(\DateTimeInterface::ATOM)))
            ->when(fn (PasswordCredential $credential) => $credential->change(Password::fromString('NewStr0ngP@ssw0rd456!'), $this->policy, $this->hasher, $changedAt))
            ->then(new PasswordCredentialChanged($id, $this->hasher->hash('NewStr0ngP@ssw0rd456!'), $changedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itCannotChangeToTheSamePassword(): void
    {
        $identityId = IdentityId::generate()->toString();
        $id = PasswordCredentialId::forIdentity($identityId)->toString();
        $definedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $changedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(new PasswordCredentialDefined($id, $identityId, 'operator', $this->hasher->hash('MyStr0ngP@ssw0rd123!'), $definedAt->format(\DateTimeInterface::ATOM)))
            ->when(fn (PasswordCredential $credential) => $credential->change(Password::fromString('MyStr0ngP@ssw0rd123!'), $this->policy, $this->hasher, $changedAt))
            ->expectsException(PasswordUnchangedException::class);
    }

    #[Test]
    public function itCannotDefineAWeakPassword(): void
    {
        $identityId = IdentityId::generate();
        $id = PasswordCredentialId::forIdentity($identityId->toString());
        $login = Login::fromString('operator');
        $policy = new StubPasswordPolicy(strongEnough: false);

        $this
            ->given()
            ->when(fn () => PasswordCredential::define($id, $identityId, $login, Password::fromString('passwordpassword'), $policy, $this->hasher, new \DateTimeImmutable('2026-01-01T00:00:00+00:00')))
            ->expectsException(WeakPasswordException::class);
    }

    #[Test]
    public function itCannotDefineACompromisedPassword(): void
    {
        $identityId = IdentityId::generate();
        $id = PasswordCredentialId::forIdentity($identityId->toString());
        $login = Login::fromString('operator');
        $policy = new StubPasswordPolicy(compromised: true);

        $this
            ->given()
            ->when(fn () => PasswordCredential::define($id, $identityId, $login, Password::fromString('MyStr0ngP@ssw0rd123!'), $policy, $this->hasher, new \DateTimeImmutable('2026-01-01T00:00:00+00:00')))
            ->expectsException(CompromisedPasswordException::class);
    }

    #[Test]
    public function itCannotChangeToAWeakPassword(): void
    {
        $identityId = IdentityId::generate()->toString();
        $id = PasswordCredentialId::forIdentity($identityId)->toString();
        $definedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $policy = new StubPasswordPolicy(strongEnough: false);

        $this
            ->given(new PasswordCredentialDefined($id, $identityId, 'operator', $this->hasher->hash('MyStr0ngP@ssw0rd123!'), $definedAt->format(\DateTimeInterface::ATOM)))
            ->when(fn (PasswordCredential $credential) => $credential->change(Password::fromString('passwordpassword'), $policy, $this->hasher, new \DateTimeImmutable('2026-01-02T00:00:00+00:00')))
            ->expectsException(WeakPasswordException::class);
    }

    #[Test]
    public function itCannotChangeToACompromisedPassword(): void
    {
        $identityId = IdentityId::generate()->toString();
        $id = PasswordCredentialId::forIdentity($identityId)->toString();
        $definedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $policy = new StubPasswordPolicy(compromised: true);

        $this
            ->given(new PasswordCredentialDefined($id, $identityId, 'operator', $this->hasher->hash('MyStr0ngP@ssw0rd123!'), $definedAt->format(\DateTimeInterface::ATOM)))
            ->when(fn (PasswordCredential $credential) => $credential->change(Password::fromString('NewStr0ngP@ssw0rd456!'), $policy, $this->hasher, new \DateTimeImmutable('2026-01-02T00:00:00+00:00')))
            ->expectsException(CompromisedPasswordException::class);
    }

    #[Test]
    public function itRehashes(): void
    {
        $identityId = IdentityId::generate()->toString();
        $id = PasswordCredentialId::forIdentity($identityId)->toString();
        $definedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $rehashedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(new PasswordCredentialDefined($id, $identityId, 'operator', $this->hasher->hash('MyStr0ngP@ssw0rd123!'), $definedAt->format(\DateTimeInterface::ATOM)))
            ->when(fn (PasswordCredential $credential) => $credential->rehash('MyStr0ngP@ssw0rd123!', $this->hasher, $rehashedAt))
            ->then(new PasswordCredentialRehashed($id, $this->hasher->hash('MyStr0ngP@ssw0rd123!'), $rehashedAt->format(\DateTimeInterface::ATOM)));
    }

    protected function aggregateClass(): string
    {
        return PasswordCredential::class;
    }
}
