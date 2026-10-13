<?php

declare(strict_types=1);

namespace Shopping\Tests\Cart\Infrastructure\EventStore;

use PHPUnit\Framework\Attributes\Test;
use Shopping\Cart\Domain\Cart;
use Shopping\Cart\Domain\Entity\CartLine;
use Shopping\Cart\Domain\Exception\CartAlreadyExistsException;
use Shopping\Cart\Domain\Exception\CartNotFoundException;
use Shopping\Cart\Domain\Repository\CartRepositoryInterface;
use Shopping\Tests\Cart\Support\Factory\CartFactory;
use Shopping\Tests\Cart\Support\Factory\CartIdFactory;
use Support\TestCase\AbstractIntegrationTestCase;

final class PatchlevelCartRepositoryTest extends AbstractIntegrationTestCase
{
    private CartRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(CartRepositoryInterface::class);
    }

    #[Test]
    public function itSavesAndLoads(): void
    {
        // Given
        $cart = CartFactory::new()
            ->productAdded()
            ->productAdded()
            ->productQuantityChanged()
            ->productRemoved()
            ->purchased()
            ->create();

        // When
        $this->repository->save($cart);
        $loaded = $this->repository->load($cart->id);

        // Then
        self::assertSame($this->propertiesOf($cart), $this->propertiesOf($loaded));
    }

    #[Test]
    public function itThrowsWhenAlreadyExists(): void
    {
        // Given
        $cart = CartFactory::new()
            ->create();
        $this->store($cart);
        $duplicate = CartFactory::new()
            ->withId($cart->id)
            ->create();

        // Then
        $this->expectException(CartAlreadyExistsException::class);

        // When
        $this->repository->save($duplicate);
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Given
        $unknownId = CartIdFactory::new()->create();

        // Then
        $this->expectException(CartNotFoundException::class);

        // When
        $this->repository->load($unknownId);
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $cart = CartFactory::new()->create();
        $this->store($cart);

        // When
        $exists = $this->repository->has($cart->id);

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itHasNot(): void
    {
        // Given
        $unknownId = CartIdFactory::new()->create();

        // When
        $notExists = $this->repository->has($unknownId);

        // Then
        self::assertFalse($notExists);
    }

    /**
     * @return array<string, mixed>
     */
    private function propertiesOf(Cart $cart): array
    {
        $atom = static fn (?\DateTimeImmutable $date): ?string => $date?->format(\DateTimeInterface::ATOM);

        return [
            'id' => $cart->id->toString(),
            'customerId' => $cart->customerId,
            'startedAt' => $atom($cart->startedAt),
            'operationalState' => $cart->operationalState->value,
            'lines' => array_map(static fn (CartLine $line): array => [
                'id' => $line->id->toString(),
                'productId' => $line->productId,
                'quantity' => $line->quantity->value,
                'addedAt' => $atom($line->addedAt),
                'changedAt' => $atom($line->changedAt),
            ], $cart->lines),
            'lineSequence' => $cart->lineSequence,
            'purchasedAt' => $atom($cart->purchasedAt),
        ];
    }
}
