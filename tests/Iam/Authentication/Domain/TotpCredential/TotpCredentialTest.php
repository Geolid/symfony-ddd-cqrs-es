<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Domain\TotpCredential;

use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialEnrolled;
use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialRevoked;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialOwnedByAnotherIdentityException;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Authentication\Domain\TotpCredential\TotpCredential;
use Iam\Authentication\Domain\TotpCredential\ValueObject\TotpCredentialId;
use Iam\Tests\Authentication\Support\Builder\TotpCredentialBuilder;
use Iam\Tests\Authentication\Support\Double\FakeTotpCipher;
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = TotpCredentialId::fromString(Uuid::uuid7()->toString());
        $this->identityId = TotpCredentialBuilder::sample('identityId');
        $this->secret = TotpCredentialBuilder::sample('secret');
        $this->enrolledAt = TotpCredentialBuilder::sample('enrolledAt');
        $this->cipher = new FakeTotpCipher();
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
