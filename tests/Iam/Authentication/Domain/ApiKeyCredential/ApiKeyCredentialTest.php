<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Domain\ApiKeyCredential;

use Iam\Authentication\Domain\ApiKeyCredential\ApiKeyCredential;
use Iam\Authentication\Domain\ApiKeyCredential\Event\ApiKeyCredentialIssued;
use Iam\Authentication\Domain\ApiKeyCredential\Event\ApiKeyCredentialRevoked;
use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialOwnedByAnotherIdentityException;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialId;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\KeyId;
use Iam\Tests\Authentication\Support\Builder\ApiKeyCredentialBuilder;
use Iam\Tests\Authentication\Support\Double\FakeApiKeyHasher;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\ValueObject\Label;

final class ApiKeyCredentialTest extends AggregateRootTestCase
{
    private ApiKeyCredentialId $id;
    private string $identityId;
    private KeyId $keyId;
    private Label $label;
    private string $secret;
    private FakeApiKeyHasher $hasher;
    private \DateTimeImmutable $issuedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = ApiKeyCredentialId::generate();
        $this->identityId = ApiKeyCredentialBuilder::sample('identityId');
        $this->keyId = ApiKeyCredentialBuilder::sample('keyId');
        $this->label = ApiKeyCredentialBuilder::sample('label');
        $this->secret = ApiKeyCredentialBuilder::sample('secret');
        $this->issuedAt = ApiKeyCredentialBuilder::sample('issuedAt');
        $this->hasher = new FakeApiKeyHasher();
    }

    #[Test]
    public function itIssues(): void
    {
        $this
            ->given()
            ->when(fn (): ApiKeyCredential => ApiKeyCredential::issue(
                $this->id,
                $this->identityId,
                $this->label,
                $this->keyId,
                $this->secret,
                $this->hasher,
                $this->issuedAt,
            ))
            ->then($this->issued());
    }

    #[Test]
    public function itRevokes(): void
    {
        $revokedAt = ApiKeyCredentialBuilder::sample('revokedAt');

        $this
            ->given($this->issued())
            ->when(fn (ApiKeyCredential $credential) => $credential->revoke($this->identityId, $revokedAt))
            ->then(new ApiKeyCredentialRevoked($this->id->toString(), $revokedAt));
    }

    #[Test]
    public function itDoesNotRevokeWhenAlreadyRevoked(): void
    {
        $revokedAt = ApiKeyCredentialBuilder::sample('revokedAt');

        $this
            ->given(
                $this->issued(),
                new ApiKeyCredentialRevoked($this->id->toString(), $revokedAt),
            )
            ->when(fn (ApiKeyCredential $credential) => $credential->revoke($this->identityId, $revokedAt))
            ->then();
    }

    #[Test]
    public function itCannotRevokeWhenOwnedByAnotherIdentity(): void
    {
        $anotherIdentityId = ApiKeyCredentialBuilder::sample('identityId');

        $this
            ->given($this->issued())
            ->when(static fn (ApiKeyCredential $credential) => $credential->revoke($anotherIdentityId, ApiKeyCredentialBuilder::sample('revokedAt')))
            ->expectsException(ApiKeyCredentialOwnedByAnotherIdentityException::class);
    }

    protected function aggregateClass(): string
    {
        return ApiKeyCredential::class;
    }

    private function issued(): ApiKeyCredentialIssued
    {
        return new ApiKeyCredentialIssued(
            $this->id->toString(),
            $this->identityId,
            $this->label,
            $this->keyId,
            $this->hasher->hash($this->secret),
            $this->issuedAt,
        );
    }
}
