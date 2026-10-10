<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\EventStore;

use Iam\Identity\Domain\Exception\IdentityAlreadyExistsException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\Identity;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Iam\Tests\Identity\Support\Factory\EmailFactory;
use Iam\Tests\Identity\Support\Factory\FullNameFactory;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use Iam\Tests\Identity\Support\Factory\IdentityIdFactory;
use PHPUnit\Framework\Attributes\Test;
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
            ->confirmationRequested()
            ->confirmed()
            ->fullNameChanged(FullNameFactory::new()->create())
            ->emailChangeRequested(EmailFactory::new()->create())
            ->emailChanged(EmailFactory::new()->create())
            ->suspended()
            ->reactivated()
            ->erasureRequested()
            ->erasureCancelled()
            ->erasureRequested()
            ->erased()
            ->create();

        // When
        $this->repository->save($identity);
        $loaded = $this->repository->load($identity->id);

        // Then
        self::assertSame($this->propertiesOf($identity), $this->propertiesOf($loaded));
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->repository->load(IdentityIdFactory::new()->create());
    }

    #[Test]
    public function itThrowsWhenAlreadyExists(): void
    {
        // Given
        $identity = IdentityFactory::new()->create();
        $this->store($identity);
        $duplicate = IdentityFactory::new()->withId($identity->id)->create();

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
        $this->store($identity);

        // When
        $exists = $this->repository->has($identity->id);

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itHasNot(): void
    {
        // When
        $notExists = $this->repository->has(IdentityIdFactory::new()->create());

        // Then
        self::assertFalse($notExists);
    }

    /**
     * @return array<string, string|null>
     */
    private function propertiesOf(Identity $identity): array
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
