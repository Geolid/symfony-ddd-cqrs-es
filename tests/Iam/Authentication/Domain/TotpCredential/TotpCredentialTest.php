<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Domain\TotpCredential;

use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialIssued;
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
    private \DateTimeImmutable $issuedAt;
    private TotpCipherInterface $cipher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = TotpCredentialId::fromString(Uuid::uuid7()->toString());
        $this->identityId = TotpCredentialBuilder::sample('identityId');
        $this->secret = TotpCredentialBuilder::sample('secret');
        $this->issuedAt = TotpCredentialBuilder::sample('issuedAt');
        $this->cipher = new FakeTotpCipher();
    }

    #[Test]
    public function itIssues(): void
    {
        $this
            ->given()
            ->when(fn (): TotpCredential => TotpCredential::issue(
                $this->id,
                $this->identityId,
                $this->secret,
                $this->cipher,
                $this->issuedAt,
            ))
            ->then($this->issued());
    }

    #[Test]
    public function itRevokes(): void
    {
        $revokedAt = TotpCredentialBuilder::sample('revokedAt');

        $this
            ->given($this->issued())
            ->when(fn (TotpCredential $credential) => $credential->revoke($this->identityId, $revokedAt))
            ->then(new TotpCredentialRevoked($this->id, $revokedAt));
    }

    #[Test]
    public function itDoesNotRevokeWhenAlreadyRevoked(): void
    {
        $revokedAt = TotpCredentialBuilder::sample('revokedAt');

        $this
            ->given(
                $this->issued(),
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
            ->given($this->issued())
            ->when(static fn (TotpCredential $credential) => $credential->revoke($anotherIdentityId, TotpCredentialBuilder::sample('revokedAt')))
            ->expectsException(TotpCredentialOwnedByAnotherIdentityException::class);
    }

    protected function aggregateClass(): string
    {
        return TotpCredential::class;
    }

    private function issued(): TotpCredentialIssued
    {
        return new TotpCredentialIssued(
            $this->id,
            $this->identityId,
            $this->cipher->encrypt($this->secret),
            $this->issuedAt,
        );
    }
}
