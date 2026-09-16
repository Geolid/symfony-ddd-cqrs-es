<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Domain\TotpCredential;

use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialEnrolled;
use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialEnrollmentConfirmed;
use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialRevoked;
use Iam\Authentication\Domain\TotpCredential\Exception\InvalidTotpCodeException;
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
            ->when(fn (TotpCredential $credential) => $credential->confirm($code, $this->cipher, $this->verifier, $confirmedAt))
            ->then(new TotpCredentialEnrollmentConfirmed($this->id, $confirmedAt));
    }

    #[Test]
    public function itDoesNotConfirmWhenAlreadyConfirmed(): void
    {
        $confirmedAt = TotpCredentialBuilder::sample('confirmedAt');
        $code = FakeTotpVerifier::codeFor($this->secret);

        $this
            ->given(
                $this->enrolled(),
                new TotpCredentialEnrollmentConfirmed($this->id, $confirmedAt),
            )
            ->when(fn (TotpCredential $credential) => $credential->confirm($code, $this->cipher, $this->verifier, $confirmedAt))
            ->then();
    }

    #[Test]
    public function itCannotConfirmWithInvalidCode(): void
    {
        $this
            ->given($this->enrolled())
            ->when(fn (TotpCredential $credential) => $credential->confirm('invalid', $this->cipher, $this->verifier, TotpCredentialBuilder::sample('confirmedAt')))
            ->expectsException(InvalidTotpCodeException::class);
    }

    #[Test]
    public function itRevokes(): void
    {
        $revokedAt = TotpCredentialBuilder::sample('revokedAt');

        $this
            ->given($this->enrolled())
            ->when(static fn (TotpCredential $credential) => $credential->revoke($revokedAt))
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
            ->when(static fn (TotpCredential $credential) => $credential->revoke($revokedAt))
            ->then();
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
