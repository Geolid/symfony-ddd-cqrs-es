<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Infrastructure\EventStore;

use Compliance\Erasing\Domain\Erasure;
use Compliance\Erasing\Domain\Exception\ErasureAlreadyExistsException;
use Compliance\Erasing\Domain\Exception\ErasureNotFoundException;
use Compliance\Erasing\Domain\Repository\ErasureRepositoryInterface;
use Compliance\Tests\Erasing\Support\Factory\ErasureFactory;
use Compliance\Tests\Erasing\Support\Factory\ErasureIdFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class PatchlevelErasureRepositoryTest extends AbstractIntegrationTestCase
{
    private ErasureRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(ErasureRepositoryInterface::class);
    }

    #[Test]
    #[DataProvider('provideLifecycle')]
    public function itSavesAndLoads(ErasureFactory $factory): void
    {
        // Given
        $erasure = $factory->create();

        // When
        $this->repository->save($erasure);
        $loaded = $this->repository->load($erasure->id);

        // Then
        self::assertSame($this->propertiesOf($erasure), $this->propertiesOf($loaded));
    }

    /**
     * @return iterable<string, array{ErasureFactory}>
     */
    public static function provideLifecycle(): iterable
    {
        yield 'approved' => [ErasureFactory::new()->approved()];
        yield 'cancelled' => [ErasureFactory::new()->cancelled()];
    }

    #[Test]
    public function itThrowsWhenAlreadyExists(): void
    {
        // Given
        $erasure = ErasureFactory::new()
            ->create();
        $this->store($erasure);
        $duplicate = ErasureFactory::new()
            ->withId($erasure->id)
            ->create();

        // Then
        $this->expectException(ErasureAlreadyExistsException::class);

        // When
        $this->repository->save($duplicate);
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(ErasureNotFoundException::class);

        // When
        $this->repository->load(ErasureIdFactory::new()->create());
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $erasure = ErasureFactory::new()->create();
        $this->store($erasure);

        // When
        $exists = $this->repository->has($erasure->id);

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itHasNot(): void
    {
        // When
        $notExists = $this->repository->has(ErasureIdFactory::new()->create());

        // Then
        self::assertFalse($notExists);
    }

    /**
     * @return array<string, mixed>
     */
    private function propertiesOf(Erasure $erasure): array
    {
        $atom = static fn (?\DateTimeImmutable $date): ?string => $date?->format(\DateTimeInterface::ATOM);

        return [
            'id' => $erasure->id->toString(),
            'identityId' => $erasure->identityId,
            'state' => $erasure->state->value,
            'requestedAt' => $atom($erasure->requestedAt),
            'cancelledAt' => $atom($erasure->cancelledAt),
            'approvedAt' => $atom($erasure->approvedAt),
        ];
    }
}
