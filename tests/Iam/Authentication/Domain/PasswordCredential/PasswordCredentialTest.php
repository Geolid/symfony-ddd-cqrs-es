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
use Iam\Tests\Authentication\Support\Doubles\FakePasswordHasher;
use Iam\Tests\Authentication\Support\Doubles\StubPasswordStrength;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;

final class PasswordCredentialTest extends AggregateRootTestCase
{
    private PasswordCredentialId $id;
    private string $identityId;
    private Login $login;
    private Password $password;
    private FakePasswordHasher $hasher;
    private \DateTimeImmutable $definedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->identityId = PasswordCredentialBuilder::sample('identityId');
        $this->id = PasswordCredentialId::forIdentity($this->identityId);
        $this->login = PasswordCredentialBuilder::sample('login');
        $this->password = PasswordCredentialBuilder::sample('password');
        $this->definedAt = PasswordCredentialBuilder::sample('definedAt');
        $this->hasher = new FakePasswordHasher();
    }

    #[Test]
    public function itDefines(): void
    {
        $this
            ->given()
            ->when(fn (): PasswordCredential => PasswordCredential::define(
                $this->id,
                $this->identityId,
                $this->login,
                $this->password,
                new StubPasswordStrength(),
                $this->hasher,
                $this->definedAt,
            ))
            ->then($this->defined());
    }

    #[Test]
    public function itCannotDefineWithWeakPassword(): void
    {
        $this
            ->given()
            ->when(fn (): PasswordCredential => PasswordCredential::define(
                $this->id,
                $this->identityId,
                $this->login,
                Password::fromString('passwordpassword'),
                new StubPasswordStrength(sufficient: false),
                $this->hasher,
                $this->definedAt,
            ))
            ->expectsException(WeakPasswordException::class);
    }

    #[Test]
    public function itChanges(): void
    {
        $changedAt = PasswordCredentialBuilder::sample('changedAt');
        $newPassword = 'updated-password';

        $this
            ->given($this->defined())
            ->when(fn (PasswordCredential $credential) => $credential->change(
                Password::fromString($newPassword),
                new StubPasswordStrength(),
                $this->hasher,
                $changedAt,
            ))
            ->then(new PasswordCredentialChanged(
                $this->id->toString(),
                $this->hasher->hash($newPassword),
                $changedAt,
            ));
    }

    #[Test]
    public function itCannotChangeToWeakPassword(): void
    {
        $this
            ->given($this->defined())
            ->when(fn (PasswordCredential $credential) => $credential->change(
                Password::fromString('updated-password'),
                new StubPasswordStrength(sufficient: false),
                $this->hasher,
                PasswordCredentialBuilder::sample('changedAt'),
            ))
            ->expectsException(WeakPasswordException::class);
    }

    #[Test]
    public function itCannotChangeToSamePassword(): void
    {
        $this
            ->given($this->defined())
            ->when(fn (PasswordCredential $credential) => $credential->change(
                $this->password,
                new StubPasswordStrength(),
                $this->hasher,
                PasswordCredentialBuilder::sample('changedAt'),
            ))
            ->expectsException(SamePasswordException::class);
    }

    #[Test]
    public function itRehashes(): void
    {
        $rehashedAt = PasswordCredentialBuilder::sample('rehashedAt');

        $this
            ->given($this->defined())
            ->when(fn (PasswordCredential $credential) => $credential->rehash(
                $this->password->value,
                $this->hasher,
                $rehashedAt,
            ))
            ->then(new PasswordCredentialRehashed(
                $this->id->toString(),
                $this->hasher->hash($this->password->value),
                $rehashedAt,
            ));
    }

    protected function aggregateClass(): string
    {
        return PasswordCredential::class;
    }

    private function defined(): PasswordCredentialDefined
    {
        return new PasswordCredentialDefined(
            $this->id->toString(),
            $this->identityId,
            $this->login->value,
            $this->hasher->hash($this->password->value),
            $this->definedAt,
        );
    }
}
