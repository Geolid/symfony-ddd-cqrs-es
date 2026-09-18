<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Domain\TotpCredential;

use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialEnrolled;
use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialRevoked;
use Iam\Authentication\Domain\TotpCredential\Exception\InvalidTotpCodeException;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialNotConfirmableException;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialOwnedByAnotherIdentityException;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpVerifierInterface;
use Iam\Authentication\Domain\TotpCredential\TotpCredential;
use Iam\Authentication\Domain\TotpCredential\ValueObject\TotpCredentialId;
use Iam\Tests\Authentication\Support\Builder\TotpCredentialBuilder;
use Iam\Tests\Authentication\Support\Double\FakeTotpCipher;
use Iam\Tests\Authentication\Support\Double\FakeTotpVerifier;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;

final class TotpCredentialTest extends AggregateRootTestCase
{
    private TotpCredentialId $id;
    private string $identityId;
    private string $secret;
    private \DateTimeImmutable $enrolledAt;
    private TotpCipherInterface $cipher;
    private TotpVerifierInterface $verifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = TotpCredentialId::fromString(Uuid::uuid7()->toString());
        $this->identityId = TotpCredentialBuilder::sample('identityId');
        $this->secret = TotpCredentialBuilder::sample('secret');
        $this->enrolledAt = TotpCredentialBuilder::sample('enrolledAt');
        $this->cipher = new FakeTotpCipher();
        $this->verifier = new FakeTotpVerifier();
    }

    #[Test]
    public function itEnrolls(): void
    {
        $this
            ->given()
            ->when(fn (): TotpCredential => TotpCredential::enroll(
                $this->id,
                $this->identityId,
                $this->secret,
                $this->cipher,
                $this->enrolledAt,
            ))
            ->then($this->enrolled());
    }

    #[Test]
    public function itConfirms(): void
    {
        $confirmedAt = TotpCredentialBuilder::sample('confirmedAt');
        $code = FakeTotpVerifier::codeFor($this->secret);

        $this
            ->given($this->enrolled())
            ->when(fn (TotpCredential $credential) => $credential->confirm($this->identityId, $code, $this->cipher, $this->verifier, $confirmedAt))
            ->then(new TotpCredentialConfirmed($this->id, $confirmedAt));
    }

    #[Test]
    public function itDoesNotConfirmWhenAlreadyConfirmed(): void
    {
        $confirmedAt = TotpCredentialBuilder::sample('confirmedAt');
        $code = FakeTotpVerifier::codeFor($this->secret);

        $this
            ->given(
                $this->enrolled(),
                new TotpCredentialConfirmed($this->id, $confirmedAt),
            )
            ->when(fn (TotpCredential $credential) => $credential->confirm($this->identityId, $code, $this->cipher, $this->verifier, $confirmedAt))
            ->then();
    }

    #[Test]
    public function itCannotConfirmWhenOwnedByAnotherIdentity(): void
    {
        $anotherIdentityId = TotpCredentialBuilder::sample('identityId');
        $code = FakeTotpVerifier::codeFor($this->secret);

        $this
            ->given($this->enrolled())
            ->when(fn (TotpCredential $credential) => $credential->confirm($anotherIdentityId, $code, $this->cipher, $this->verifier, TotpCredentialBuilder::sample('confirmedAt')))
            ->expectsException(TotpCredentialOwnedByAnotherIdentityException::class);
    }

    #[Test]
    public function itCannotConfirmWhenRevoked(): void
    {
        $revokedAt = TotpCredentialBuilder::sample('revokedAt');
        $code = FakeTotpVerifier::codeFor($this->secret);

        $this
            ->given(
                $this->enrolled(),
                new TotpCredentialRevoked($this->id, $revokedAt),
            )
            ->when(fn (TotpCredential $credential) => $credential->confirm($this->identityId, $code, $this->cipher, $this->verifier, TotpCredentialBuilder::sample('confirmedAt')))
            ->expectsException(TotpCredentialNotConfirmableException::class);
    }

    #[Test]
    public function itCannotConfirmWithInvalidCode(): void
    {
        $this
            ->given($this->enrolled())
            ->when(fn (TotpCredential $credential) => $credential->confirm($this->identityId, 'invalid', $this->cipher, $this->verifier, TotpCredentialBuilder::sample('confirmedAt')))
            ->expectsException(InvalidTotpCodeException::class);
    }

    #[Test]
    public function itRevokes(): void
    {
        $revokedAt = TotpCredentialBuilder::sample('revokedAt');

        $this
            ->given($this->enrolled())
            ->when(fn (TotpCredential $credential) => $credential->revoke($this->identityId, $revokedAt))
            ->then(new TotpCredentialRevoked($this->id, $revokedAt));
    }

    #[Test]
    public function itDoesNotRevokeWhenAlreadyRevoked(): void
    {
        $revokedAt = TotpCredentialBuilder::sample('revokedAt');

        $this
            ->given(
                $this->enrolled(),
                new TotpCredentialRevoked($this->id, $revokedAt),
            )
            ->when(fn (TotpCredential $credential) => $credential->revoke($this->identityId, $revokedAt))
            ->then();
    }

    #[Test]
    public function itCannotRevokeWhenOwnedByAnotherIdentity(): void
    {
        $anotherIdentityId = TotpCredentialBuilder::sample('identityId');

        $this
            ->given($this->enrolled())
            ->when(static fn (TotpCredential $credential) => $credential->revoke($anotherIdentityId, TotpCredentialBuilder::sample('revokedAt')))
            ->expectsException(TotpCredentialOwnedByAnotherIdentityException::class);
    }

    protected function aggregateClass(): string
    {
        return TotpCredential::class;
    }

    private function enrolled(): TotpCredentialEnrolled
    {
        return new TotpCredentialEnrolled(
            $this->id,
            $this->identityId,
            $this->cipher->encrypt($this->secret),
            $this->enrolledAt,
        );
    }
}
