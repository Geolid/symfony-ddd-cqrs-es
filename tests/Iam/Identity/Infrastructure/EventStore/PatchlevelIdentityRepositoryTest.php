<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\EventStore;

use Iam\Identity\Domain\Exception\IdentityAlreadyExistsException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\Identity;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class PatchlevelIdentityRepositoryTest extends AbstractIntegrationTestCase
{
    private IdentityRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(IdentityRepositoryInterface::class);
    }

    #[Test]
    public function itSavesAndLoads(): void
    {
        // Given
        $identity = IdentityFactory::new()
            ->confirmed()
            ->fullNameChanged('Jane Doe')
            ->emailChangeRequested('jane.doe@example.com')
            ->suspended()
            ->erasureRequested()
            ->create();

        // When
        $this->repository->save($identity);
        $loaded = $this->repository->load($identity->id);

        // Then
        self::assertSame($this->stateOf($identity), $this->stateOf($loaded));
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->repository->load(IdentityId::fromString(Uuid::uuid7()->toString()));
    }

    #[Test]
    public function itThrowsWhenAlreadyExists(): void
    {
        // Given
        $identity = IdentityFactory::new()->create();
        $this->repository->save($identity);
        $duplicate = IdentityFactory::new()->withId($identity->id->toString())->create();

        // Then
        $this->expectException(IdentityAlreadyExistsException::class);

        // When
        $this->repository->save($duplicate);
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $identity = IdentityFactory::new()->create();
        $this->repository->save($identity);

        // When
        $exists = $this->repository->has($identity->id);

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itHasNot(): void
    {
        // When
        $notExists = $this->repository->has(IdentityId::fromString(Uuid::uuid7()->toString()));

        // Then
        self::assertFalse($notExists);
    }

    /**
     * @return array<string, string|null>
     */
    private function stateOf(Identity $identity): array
    {
        $atom = static fn (?\DateTimeImmutable $date): ?string => $date?->format(\DateTimeInterface::ATOM);

        return [
            'id' => $identity->id->toString(),
            'fullName' => $identity->fullName->value,
            'email' => $identity->email->value,
            'verificationState' => $identity->verificationState->value,
            'moderationState' => $identity->moderationState->value,
            'erasureState' => $identity->erasureState->value,
            'registeredAt' => $atom($identity->registeredAt),
            'confirmationRequestedAt' => $atom($identity->confirmationRequestedAt),
            'confirmedAt' => $atom($identity->confirmedAt),
            'fullNameChangedAt' => $atom($identity->fullNameChangedAt),
            'pendingEmail' => $identity->pendingEmail?->value,
            'emailChangeRequestedAt' => $atom($identity->emailChangeRequestedAt),
            'emailChangedAt' => $atom($identity->emailChangedAt),
            'suspensionReason' => $identity->suspensionReason?->value,
            'suspendedAt' => $atom($identity->suspendedAt),
            'reactivationReason' => $identity->reactivationReason?->value,
            'reactivatedAt' => $atom($identity->reactivatedAt),
            'erasureRequestedAt' => $atom($identity->erasureRequestedAt),
            'erasureCancelledAt' => $atom($identity->erasureCancelledAt),
            'erasedAt' => $atom($identity->erasedAt),
        ];
    }
}
