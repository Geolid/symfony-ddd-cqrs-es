<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\EventStore;

use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordCredentialAlreadyExistsException;
use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordCredentialNotFoundException;
use Iam\Authentication\Domain\PasswordCredential\PasswordCredential;
use Iam\Authentication\Domain\PasswordCredential\Repository\PasswordCredentialRepositoryInterface;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Iam\Tests\Authentication\Support\Double\FakePasswordHasher;
use Iam\Tests\Authentication\Support\Double\StubPasswordStrengthSpecification;
use Iam\Tests\Authentication\Support\Factory\PasswordCredentialFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class PatchlevelPasswordCredentialRepositoryTest extends AbstractIntegrationTestCase
{
    private PasswordCredentialRepositoryInterface $repository;
    private StubPasswordStrengthSpecification $passwordStrength;
    private FakePasswordHasher $hasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(PasswordCredentialRepositoryInterface::class);
        $this->passwordStrength = new StubPasswordStrengthSpecification();
        $this->hasher = new FakePasswordHasher();
    }

    #[Test]
    public function itSavesAndLoads(): void
    {
        // Given
        $credential = PasswordCredentialFactory::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->changed('Changed-Password-1!')
            ->resetRequested()
            ->reset('Reset-Password-2!')
            ->rehashed('Reset-Password-2!')
            ->create();

        // When
        $this->repository->save($credential);
        $loaded = $this->repository->load($credential->id);

        // Then
        self::assertSame($this->stateOf($credential), $this->stateOf($loaded));
    }

    #[Test]
    public function itThrowsWhenAlreadyExists(): void
    {
        // Given
        $credential = PasswordCredentialFactory::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();
        $this->repository->save($credential);
        $duplicate = PasswordCredentialFactory::new()
            ->withIdentityId($credential->identityId)
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();

        // Then
        $this->expectException(PasswordCredentialAlreadyExistsException::class);

        // When
        $this->repository->save($duplicate);
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(PasswordCredentialNotFoundException::class);

        // When
        $this->repository->load(PasswordCredentialId::forIdentity(Uuid::uuid7()->toString()));
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $credential = PasswordCredentialFactory::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();
        $this->repository->save($credential);

        // When
        $exists = $this->repository->has($credential->id);

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itHasNot(): void
    {
        // When
        $notExists = $this->repository->has(PasswordCredentialId::forIdentity(Uuid::uuid7()->toString()));

        // Then
        self::assertFalse($notExists);
    }

    /**
     * @return array<string, mixed>
     */
    private function stateOf(PasswordCredential $credential): array
    {
        $atom = static fn (?\DateTimeImmutable $date): ?string => $date?->format(\DateTimeInterface::ATOM);

        return [
            'id' => $credential->id->toString(),
            'identityId' => $credential->identityId,
            'passwordHash' => $credential->passwordHash,
            'definedAt' => $atom($credential->definedAt),
            'changedAt' => $atom($credential->changedAt),
            'rehashedAt' => $atom($credential->rehashedAt),
            'resetRequestedAt' => $atom($credential->resetRequestedAt),
            'resetAt' => $atom($credential->resetAt),
        ];
    }
}
