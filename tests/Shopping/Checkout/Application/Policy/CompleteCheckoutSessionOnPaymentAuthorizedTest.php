<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Policy;

use Finance\Payment\Application\IntegrationEvent\PaymentAuthorized\PaymentAuthorizedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Mapper\PostalAddressMapper;
use Shopping\Checkout\Application\Command\CompleteCheckoutSession\CompleteCheckoutSession;
use Shopping\Checkout\Application\Policy\CompleteCheckoutSessionOnPaymentAuthorized;
use Shopping\Checkout\Domain\CheckoutSession\ValueObject\CheckoutItem;
use Shopping\Tests\Checkout\Support\Builder\CheckoutSessionBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class CompleteCheckoutSessionOnPaymentAuthorizedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCompletes(): void
    {
        // Given
        $builder = CheckoutSessionBuilder::new()->withItems([CheckoutSessionBuilder::sample('items')[0]]);
        $checkoutSession = $builder->create();
        $paymentId = Uuid::uuid7()->toString();
        $items = array_map($this->toArray(...), $builder['items']);

        $commandBus = $this->createMock(CommandBusInterface::class);
        $this->replace(CommandBusInterface::class, $commandBus);
        $commandBus->expects(self::once())->method('dispatch')->with(new CompleteCheckoutSession(
            id: $checkoutSession->id->toString(),
            cartId: $builder['cartId'],
            shopperId: $builder['shopperId'],
            items: $items,
            shippingAddress: PostalAddressMapper::toArray($builder['shippingAddress']),
            billingAddress: PostalAddressMapper::toArray($builder['billingAddress']),
            paymentId: $paymentId,
        ));
        $this->store($checkoutSession);

        // When
        $this->trigger(CompleteCheckoutSessionOnPaymentAuthorized::class, new PaymentAuthorizedIntegrationEvent(
            $paymentId,
            $checkoutSession->id->toString(),
            Clock::get()->now(),
        ));
    }

    /**
     * @return array{productId: string, label: string, unitPriceInCents: int, quantity: int}
     */
    private function toArray(CheckoutItem $item): array
    {
        return [
            'productId' => $item->productId,
            'label' => $item->label->value,
            'unitPriceInCents' => $item->unitPrice->cents,
            'quantity' => $item->quantity->value,
        ];
    }
}
