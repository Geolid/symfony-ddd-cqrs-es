<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\EventStore;

use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialAlreadyExistsException;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialNotFoundException;
use Iam\Authentication\Domain\TotpCredential\Repository\TotpCredentialRepositoryInterface;
use Iam\Authentication\Domain\TotpCredential\TotpCredential;
use Iam\Authentication\Domain\TotpCredential\ValueObject\TotpCredentialId;
use Iam\Tests\Authentication\Support\Double\FakeTotpCipher;
use Iam\Tests\Authentication\Support\Factory\TotpCredentialFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class PatchlevelTotpCredentialRepositoryTest extends AbstractIntegrationTestCase
{
    private TotpCredentialRepositoryInterface $repository;
    private FakeTotpCipher $cipher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(TotpCredentialRepositoryInterface::class);
        $this->cipher = new FakeTotpCipher();
    }

    #[Test]
    public function itSavesAndLoads(): void
    {
        // Given
        $credential = TotpCredentialFactory::new()
            ->withCipher($this->cipher)
            ->unenrolled()
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
        $credential = TotpCredentialFactory::new()->withCipher($this->cipher)->create();
        $this->store($credential);
        $duplicate = TotpCredentialFactory::new()->withCipher($this->cipher)->withId($credential->id->toString())->create();

        // Then
        $this->expectException(TotpCredentialAlreadyExistsException::class);

        // When
        $this->repository->save($duplicate);
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(TotpCredentialNotFoundException::class);

        // When
        $this->repository->load(TotpCredentialId::fromString(Uuid::uuid7()->toString()));
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $credential = TotpCredentialFactory::new()->withCipher($this->cipher)->create();
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
        $notExists = $this->repository->has(TotpCredentialId::fromString(Uuid::uuid7()->toString()));

        // Then
        self::assertFalse($notExists);
    }

    /**
     * @return array<string, mixed>
     */
    private function propertiesOf(TotpCredential $credential): array
    {
        $atom = static fn (?\DateTimeImmutable $date): ?string => $date?->format(\DateTimeInterface::ATOM);

        return [
            'id' => $credential->id->toString(),
            'identityId' => $credential->identityId,
            'encryptedSecret' => $credential->encryptedSecret,
            'enrolledAt' => $atom($credential->enrolledAt),
            'unenrolled' => $credential->unenrolled,
            'unenrolledAt' => $atom($credential->unenrolledAt),
        ];
    }
}
