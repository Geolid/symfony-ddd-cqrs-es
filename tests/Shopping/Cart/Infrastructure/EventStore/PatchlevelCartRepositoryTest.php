<?php

declare(strict_types=1);

namespace Shopping\Tests\Cart\Infrastructure\EventStore;

use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\ValueObject\Quantity;
use Shopping\Cart\Domain\Cart;
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
            ->withId($cart->id->toString())
            ->create();

        // Then
        $this->expectException(CartAlreadyExistsException::class);

        // When
        $this->repository->save($duplicate);
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(CartNotFoundException::class);

        // When
        $this->repository->load(CartIdFactory::new()->create());
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
        // When
        $notExists = $this->repository->has(CartIdFactory::new()->create());

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
            'products' => array_map(static fn (Quantity $quantity): int => $quantity->value, $cart->products),
            'purchasedAt' => $atom($cart->purchasedAt),
        ];
    }
}
