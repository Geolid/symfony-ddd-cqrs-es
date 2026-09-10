<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Infrastructure\Projection\Finder;

use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use Fulfilment\Shipping\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class DbalOrderPaymentFinderTest extends AbstractIntegrationTestCase
{
    private OrderPaymentFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(OrderPaymentFinderInterface::class);
    }

    #[Test]
    public function itFindsByOrder(): void
    {
        // Given
        $other = PaymentBuilder::new()->create();
        $builder = PaymentBuilder::new()->authorized()->captured();
        $payment = $builder->create();
        $this->store($other, $payment);

        // When
        $found = $this->finder->ofOrderOrNull($builder['orderId']);
        $notFound = $this->finder->ofOrderOrNull(Uuid::uuid7()->toString());

        // Then
        self::assertNotNull($found);
        self::assertSame($builder['orderId'], $found->orderId);
        self::assertTrue($found->paid);
        self::assertNull($notFound);
    }
}
