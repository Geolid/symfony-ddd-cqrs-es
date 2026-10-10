<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Infrastructure\EventStore;

use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Mapper\PostalAddressMapper;
use Shopping\Checkout\Application\Mapper\CheckoutItemMapper;
use Shopping\Checkout\Domain\CheckoutSession;
use Shopping\Checkout\Domain\Exception\CheckoutSessionAlreadyExistsException;
use Shopping\Checkout\Domain\Exception\CheckoutSessionNotFoundException;
use Shopping\Checkout\Domain\Repository\CheckoutSessionRepositoryInterface;
use Shopping\Tests\Checkout\Support\Factory\CheckoutSessionFactory;
use Shopping\Tests\Checkout\Support\Factory\CheckoutSessionIdFactory;
use Support\TestCase\AbstractIntegrationTestCase;

final class PatchlevelCheckoutSessionRepositoryTest extends AbstractIntegrationTestCase
{
    private CheckoutSessionRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(CheckoutSessionRepositoryInterface::class);
    }

    #[Test]
    public function itSavesAndLoads(): void
    {
        // Given
        $checkoutSession = CheckoutSessionFactory::new()
            ->completed()
            ->create();

        // When
        $this->repository->save($checkoutSession);
        $loaded = $this->repository->load($checkoutSession->id);

        // Then
        self::assertSame($this->propertiesOf($checkoutSession), $this->propertiesOf($loaded));
    }

    #[Test]
    public function itThrowsWhenAlreadyExists(): void
    {
        // Given
        $checkoutSession = CheckoutSessionFactory::new()
            ->create();
        $this->store($checkoutSession);
        $duplicate = CheckoutSessionFactory::new()
            ->withId($checkoutSession->id)
            ->create();

        // Then
        $this->expectException(CheckoutSessionAlreadyExistsException::class);

        // When
        $this->repository->save($duplicate);
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(CheckoutSessionNotFoundException::class);

        // When
        $this->repository->load(CheckoutSessionIdFactory::new()->create());
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $checkoutSession = CheckoutSessionFactory::new()->create();
        $this->store($checkoutSession);

        // When
        $exists = $this->repository->has($checkoutSession->id);

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itHasNot(): void
    {
        // When
        $notExists = $this->repository->has(CheckoutSessionIdFactory::new()->create());

        // Then
        self::assertFalse($notExists);
    }

    /**
     * @return array<string, mixed>
     */
    private function propertiesOf(CheckoutSession $checkoutSession): array
    {
        $atom = static fn (?\DateTimeImmutable $date): ?string => $date?->format(\DateTimeInterface::ATOM);

        return [
            'id' => $checkoutSession->id->toString(),
            'cartId' => $checkoutSession->cartId,
            'customerId' => $checkoutSession->customerId,
            'items' => array_map(CheckoutItemMapper::toArray(...), $checkoutSession->items),
            'shippingAddress' => PostalAddressMapper::toArray($checkoutSession->shippingAddress),
            'billingAddress' => PostalAddressMapper::toArray($checkoutSession->billingAddress),
            'total' => [
                'excludingTax' => $checkoutSession->total->excludingTax->cents,
                'taxAmount' => $checkoutSession->total->taxAmount->cents,
                'includingTax' => $checkoutSession->total->includingTax->cents,
            ],
            'operationalState' => $checkoutSession->operationalState->value,
            'openedAt' => $atom($checkoutSession->openedAt),
            'expiredAt' => $atom($checkoutSession->expiredAt),
            'staledAt' => $atom($checkoutSession->staledAt),
            'paymentId' => $checkoutSession->paymentId,
            'completedAt' => $atom($checkoutSession->completedAt),
        ];
    }
}
