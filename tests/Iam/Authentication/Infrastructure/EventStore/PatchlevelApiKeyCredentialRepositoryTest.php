<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\EventStore;

use Iam\Authentication\Domain\ApiKeyCredential\ApiKeyCredential;
use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialAlreadyExistsException;
use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialNotFoundException;
use Iam\Authentication\Domain\ApiKeyCredential\Repository\ApiKeyCredentialRepositoryInterface;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialId;
use Iam\Tests\Authentication\Support\Double\FakeApiKeyHasher;
use Iam\Tests\Authentication\Support\Factory\ApiKeyCredentialFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class PatchlevelApiKeyCredentialRepositoryTest extends AbstractIntegrationTestCase
{
    private ApiKeyCredentialRepositoryInterface $repository;
    private FakeApiKeyHasher $hasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(ApiKeyCredentialRepositoryInterface::class);
        $this->hasher = new FakeApiKeyHasher();
    }

    #[Test]
    public function itSavesAndLoads(): void
    {
        // Given
        $credential = ApiKeyCredentialFactory::new()
            ->withHasher($this->hasher)
            ->revoked()
            ->create();

        // When
        $this->repository->save($credential);
        $loaded = $this->repository->load($credential->id);

        // Then
        self::assertSame($this->propertiesOf($credential), $this->propertiesOf($loaded));
    }

    #[Test]
    public function itThrowsWhenAlreadyExists(): void
    {
        // Given
        $credential = ApiKeyCredentialFactory::new()->withHasher($this->hasher)->create();
        $this->store($credential);
        $duplicate = ApiKeyCredentialFactory::new()->withHasher($this->hasher)->withId($credential->id->toString())->create();

        // Then
        $this->expectException(ApiKeyCredentialAlreadyExistsException::class);

        // When
        $this->repository->save($duplicate);
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(ApiKeyCredentialNotFoundException::class);

        // When
        $this->repository->load(ApiKeyCredentialId::fromString(Uuid::uuid7()->toString()));
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $credential = ApiKeyCredentialFactory::new()->withHasher($this->hasher)->create();
        $this->store($credential);

        // When
        $exists = $this->repository->has($credential->id);

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itHasNot(): void
    {
        // When
        $notExists = $this->repository->has(ApiKeyCredentialId::fromString(Uuid::uuid7()->toString()));

        // Then
        self::assertFalse($notExists);
    }

    /**
     * @return array<string, mixed>
     */
    private function propertiesOf(ApiKeyCredential $credential): array
    {
        $atom = static fn (?\DateTimeImmutable $date): ?string => $date?->format(\DateTimeInterface::ATOM);

        return [
            'id' => $credential->id->toString(),
            'identityId' => $credential->identityId,
            'label' => $credential->label->value,
            'keyId' => $credential->keyId->value,
            'secretHash' => $credential->secretHash,
            'issuedAt' => $atom($credential->issuedAt),
            'revoked' => $credential->revoked,
            'revokedAt' => $atom($credential->revokedAt),
        ];
    }
}
