<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Domain\PasswordCredential;

use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialChanged;
use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialDefined;
use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialRehashed;
use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialReset;
use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialResetRequested;
use Iam\Authentication\Domain\PasswordCredential\Exception\InvalidPasswordResetCodeException;
use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordResetRequestedTooRecentlyException;
use Iam\Authentication\Domain\PasswordCredential\Exception\SamePasswordException;
use Iam\Authentication\Domain\PasswordCredential\Exception\WeakPasswordException;
use Iam\Authentication\Domain\PasswordCredential\PasswordCredential;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Authentication\Support\Double\FakePasswordHasher;
use Iam\Tests\Authentication\Support\Double\StubPasswordStrengthSpecification;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\Service\VerificationCodeInterface;
use Shared\Tests\Support\Double\FakeVerificationCode;

final class PasswordCredentialTest extends AggregateRootTestCase
{
    private PasswordCredentialId $id;
    private string $identityId;
    private Password $password;
    private FakePasswordHasher $hasher;
    private VerificationCodeInterface $verificationCode;
    private \DateTimeImmutable $definedAt;
    private \DateTimeImmutable $requestedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->identityId = PasswordCredentialBuilder::sample('identityId');
        $this->id = PasswordCredentialId::forIdentity($this->identityId);
        $this->password = PasswordCredentialBuilder::sample('password');
        $this->definedAt = PasswordCredentialBuilder::sample('definedAt');
        $this->requestedAt = PasswordCredentialBuilder::sample('requestedAt');
        $this->hasher = new FakePasswordHasher();
        $this->verificationCode = new FakeVerificationCode();
    }

    #[Test]
    public function itDefines(): void
    {
        $this
            ->given()
            ->when(fn (): PasswordCredential => PasswordCredential::define(
                $this->id,
                $this->identityId,
                $this->password,
                new StubPasswordStrengthSpecification(),
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
                Password::fromString('passwordpassword'),
                new StubPasswordStrengthSpecification(sufficient: false),
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
                new StubPasswordStrengthSpecification(),
                $this->hasher,
                $changedAt,
            ))
            ->then(new PasswordCredentialChanged(
                $this->id,
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
                new StubPasswordStrengthSpecification(sufficient: false),
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
                new StubPasswordStrengthSpecification(),
                $this->hasher,
                PasswordCredentialBuilder::sample('changedAt'),
            ))
            ->expectsException(SamePasswordException::class);
    }

    #[Test]
    public function itRequestsReset(): void
    {
        $this
            ->given($this->defined())
            ->when(fn (PasswordCredential $credential) => $credential->requestReset($this->identityId, $this->requestedAt))
            ->then($this->resetRequested());
    }

    #[Test]
    public function itCannotRequestResetTooSoon(): void
    {
        $this
            ->given($this->defined(), $this->resetRequested())
            ->when(fn (PasswordCredential $credential) => $credential->requestReset($this->identityId, $this->requestedAt->modify('+1 second')))
            ->expectsException(PasswordResetRequestedTooRecentlyException::class);
    }

    #[Test]
    public function itResets(): void
    {
        $resetAt = PasswordCredentialBuilder::sample('resetAt');
        $newPassword = 'updated-password';

        $this
            ->given($this->defined())
            ->when(fn (PasswordCredential $credential) => $credential->resetPassword(
                $this->identityId,
                FakeVerificationCode::CODE,
                $this->verificationCode,
                Password::fromString($newPassword),
                new StubPasswordStrengthSpecification(),
                $this->hasher,
                $resetAt,
            ))
            ->then(new PasswordCredentialReset(
                $this->id,
                $this->hasher->hash($newPassword),
                $resetAt,
            ));
    }

    #[Test]
    public function itCannotResetWithInvalidCode(): void
    {
        $this
            ->given($this->defined())
            ->when(fn (PasswordCredential $credential) => $credential->resetPassword(
                $this->identityId,
                'wrong',
                $this->verificationCode,
                Password::fromString('updated-password'),
                new StubPasswordStrengthSpecification(),
                $this->hasher,
                PasswordCredentialBuilder::sample('resetAt'),
            ))
            ->expectsException(InvalidPasswordResetCodeException::class);
    }

    #[Test]
    public function itCannotResetToWeakPassword(): void
    {
        $this
            ->given($this->defined())
            ->when(fn (PasswordCredential $credential) => $credential->resetPassword(
                $this->identityId,
                FakeVerificationCode::CODE,
                $this->verificationCode,
                Password::fromString('updated-password'),
                new StubPasswordStrengthSpecification(sufficient: false),
                $this->hasher,
                PasswordCredentialBuilder::sample('resetAt'),
            ))
            ->expectsException(WeakPasswordException::class);
    }

    #[Test]
    public function itCannotResetToSamePassword(): void
    {
        $this
            ->given($this->defined())
            ->when(fn (PasswordCredential $credential) => $credential->resetPassword(
                $this->identityId,
                FakeVerificationCode::CODE,
                $this->verificationCode,
                $this->password,
                new StubPasswordStrengthSpecification(),
                $this->hasher,
                PasswordCredentialBuilder::sample('resetAt'),
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
                $this->id,
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
            $this->id,
            $this->identityId,
            $this->hasher->hash($this->password->value),
            $this->definedAt,
        );
    }

    private function resetRequested(): PasswordCredentialResetRequested
    {
        return new PasswordCredentialResetRequested($this->id, $this->identityId, $this->requestedAt);
    }
}
