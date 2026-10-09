<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Domain\ApiKeyCredential;

use Iam\Authentication\Domain\ApiKeyCredential\ApiKeyCredential;
use Iam\Authentication\Domain\ApiKeyCredential\Event\ApiKeyCredentialIssued;
use Iam\Authentication\Domain\ApiKeyCredential\Event\ApiKeyCredentialRevoked;
use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialOwnedByAnotherIdentityException;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialId;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\KeyId;
use Iam\Tests\Authentication\Support\Double\FakeApiKeyHasher;
use Iam\Tests\Authentication\Support\Factory\KeyIdFactory;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Label;
use Shared\Tests\Support\Factory\LabelFactory;
use Symfony\Component\Clock\Clock;

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

        $this->id = ApiKeyCredentialId::fromString(Uuid::uuid7()->toString());
        $this->identityId = Uuid::uuid7()->toString();
        $this->keyId = KeyIdFactory::new()->create();
        $this->label = LabelFactory::new()->create();
        $this->secret = bin2hex(random_bytes(32));
        $this->issuedAt = Clock::get()->now();
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
        $revokedAt = Clock::get()->now()->modify('+1 day');

        $this
            ->given($this->issued())
            ->when(fn (ApiKeyCredential $credential) => $credential->revoke($this->identityId, $revokedAt))
            ->then(new ApiKeyCredentialRevoked($this->id, $revokedAt));
    }

    #[Test]
    public function itDoesNotRevokeWhenAlreadyRevoked(): void
    {
        $revokedAt = Clock::get()->now()->modify('+1 day');

        $this
            ->given(
                $this->issued(),
                new ApiKeyCredentialRevoked($this->id, $revokedAt),
            )
            ->when(fn (ApiKeyCredential $credential) => $credential->revoke($this->identityId, $revokedAt))
            ->then();
    }

    #[Test]
    public function itCannotRevokeWhenOwnedByAnotherIdentity(): void
    {
        $anotherIdentityId = Uuid::uuid7()->toString();

        $this
            ->given($this->issued())
            ->when(static fn (ApiKeyCredential $credential) => $credential->revoke($anotherIdentityId, Clock::get()->now()->modify('+1 day')))
            ->expectsException(ApiKeyCredentialOwnedByAnotherIdentityException::class);
    }

    protected function aggregateClass(): string
    {
        return ApiKeyCredential::class;
    }

    private function issued(): ApiKeyCredentialIssued
    {
        return new ApiKeyCredentialIssued(
            $this->id,
            $this->identityId,
            $this->label,
            $this->keyId,
            $this->hasher->hash($this->secret),
            $this->issuedAt,
        );
    }
}
