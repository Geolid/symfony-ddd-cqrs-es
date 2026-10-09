<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Domain\PasswordCredential;

use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialChanged;
use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialDefined;
use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialRehashed;
use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialReset;
use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialResetRequested;
use Iam\Authentication\Domain\PasswordCredential\Exception\InvalidCurrentPasswordException;
use Iam\Authentication\Domain\PasswordCredential\Exception\InvalidPasswordResetCodeException;
use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordResetRequestedTooRecentlyException;
use Iam\Authentication\Domain\PasswordCredential\Exception\SamePasswordException;
use Iam\Authentication\Domain\PasswordCredential\Exception\WeakPasswordException;
use Iam\Authentication\Domain\PasswordCredential\PasswordCredential;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Iam\Tests\Authentication\Support\Double\FakePasswordHasher;
use Iam\Tests\Authentication\Support\Double\StubPasswordStrengthSpecification;
use Iam\Tests\Authentication\Support\Factory\PasswordFactory;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\Service\CodeChallengerInterface;
use Shared\Tests\Support\Double\FakeCodeChallenger;
use Symfony\Component\Clock\Clock;

final class PasswordCredentialTest extends AggregateRootTestCase
{
    private PasswordCredentialId $id;
    private string $identityId;
    private Password $password;
    private FakePasswordHasher $hasher;
    private CodeChallengerInterface $codeChallenger;
    private \DateTimeImmutable $definedAt;
    private \DateTimeImmutable $requestedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->identityId = Uuid::uuid7()->toString();
        $this->id = PasswordCredentialId::forIdentity($this->identityId);
        $this->password = PasswordFactory::new()->create();
        $this->definedAt = Clock::get()->now();
        $this->requestedAt = Clock::get()->now()->modify('+3 day');
        $this->hasher = new FakePasswordHasher();
        $this->codeChallenger = new FakeCodeChallenger();
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
        $changedAt = Clock::get()->now()->modify('+1 day');
        $newPassword = 'updated-password';

        $this
            ->given($this->defined())
            ->when(fn (PasswordCredential $credential) => $credential->change(
                $this->password->value,
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
    public function itCannotChangeWithInvalidCurrentPassword(): void
    {
        $this
            ->given($this->defined())
            ->when(fn (PasswordCredential $credential) => $credential->change(
                'wrong-current-password',
                Password::fromString('updated-password'),
                new StubPasswordStrengthSpecification(),
                $this->hasher,
                Clock::get()->now()->modify('+1 day'),
            ))
            ->expectsException(InvalidCurrentPasswordException::class);
    }

    #[Test]
    public function itCannotChangeToWeakPassword(): void
    {
        $this
            ->given($this->defined())
            ->when(fn (PasswordCredential $credential) => $credential->change(
                $this->password->value,
                Password::fromString('updated-password'),
                new StubPasswordStrengthSpecification(sufficient: false),
                $this->hasher,
                Clock::get()->now()->modify('+1 day'),
            ))
            ->expectsException(WeakPasswordException::class);
    }

    #[Test]
    public function itCannotChangeToSamePassword(): void
    {
        $this
            ->given($this->defined())
            ->when(fn (PasswordCredential $credential) => $credential->change(
                $this->password->value,
                $this->password,
                new StubPasswordStrengthSpecification(),
                $this->hasher,
                Clock::get()->now()->modify('+1 day'),
            ))
            ->expectsException(SamePasswordException::class);
    }

    #[Test]
    public function itRequestsReset(): void
    {
        $this
            ->given($this->defined())
            ->when(fn (PasswordCredential $credential) => $credential->requestReset($this->requestedAt))
            ->then($this->resetRequested());
    }

    #[Test]
    public function itCannotRequestResetTooSoon(): void
    {
        $this
            ->given($this->defined(), $this->resetRequested())
            ->when(fn (PasswordCredential $credential) => $credential->requestReset($this->requestedAt->modify('+1 second')))
            ->expectsException(PasswordResetRequestedTooRecentlyException::class)
            ->expectsExceptionMessage('requested too recently');
    }

    #[Test]
    public function itResets(): void
    {
        $resetAt = Clock::get()->now()->modify('+4 day');
        $newPassword = 'updated-password';

        $this
            ->given($this->defined())
            ->when(fn (PasswordCredential $credential) => $credential->resetPassword(
                FakeCodeChallenger::CODE,
                $this->codeChallenger,
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
                'wrong',
                $this->codeChallenger,
                Password::fromString('updated-password'),
                new StubPasswordStrengthSpecification(),
                $this->hasher,
                Clock::get()->now()->modify('+4 day'),
            ))
            ->expectsException(InvalidPasswordResetCodeException::class);
    }

    #[Test]
    public function itCannotResetToWeakPassword(): void
    {
        $this
            ->given($this->defined())
            ->when(fn (PasswordCredential $credential) => $credential->resetPassword(
                FakeCodeChallenger::CODE,
                $this->codeChallenger,
                Password::fromString('updated-password'),
                new StubPasswordStrengthSpecification(sufficient: false),
                $this->hasher,
                Clock::get()->now()->modify('+4 day'),
            ))
            ->expectsException(WeakPasswordException::class);
    }

    #[Test]
    public function itCannotResetToSamePassword(): void
    {
        $this
            ->given($this->defined())
            ->when(fn (PasswordCredential $credential) => $credential->resetPassword(
                FakeCodeChallenger::CODE,
                $this->codeChallenger,
                $this->password,
                new StubPasswordStrengthSpecification(),
                $this->hasher,
                Clock::get()->now()->modify('+4 day'),
            ))
            ->expectsException(SamePasswordException::class);
    }

    #[Test]
    public function itRehashes(): void
    {
        $rehashedAt = Clock::get()->now()->modify('+2 day');

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
