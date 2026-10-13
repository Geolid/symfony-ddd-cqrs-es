<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Policy;

use Finance\Payment\Application\IntegrationEvent\PaymentAuthorized\PaymentAuthorizedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Mapper\PostalAddressMapper;
use Shopping\Checkout\Application\Command\CompleteCheckoutSession\CompleteCheckoutSession;
use Shopping\Checkout\Application\Mapper\CheckoutItemMapper;
use Shopping\Checkout\Application\Policy\CompleteCheckoutSessionOnPaymentAuthorized;
use Shopping\Tests\Checkout\Support\Factory\CheckoutItemFactory;
use Shopping\Tests\Checkout\Support\Factory\CheckoutSessionFactory;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class CompleteCheckoutSessionOnPaymentAuthorizedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCompletes(): void
    {
        // Given
        $checkoutSession = CheckoutSessionFactory::new()->withItems([CheckoutItemFactory::new()->create()])->create();
        $paymentId = Uuid::uuid7()->toString();

        $commandBus = $this->createMock(CommandBusInterface::class);
        $this->replace(CommandBusInterface::class, $commandBus);
        $commandBus->expects(self::once())->method('dispatch')->with(new CompleteCheckoutSession(
            id: $checkoutSession->id->toString(),
            cartId: $checkoutSession->cartId,
            customerId: $checkoutSession->customerId,
            items: array_map(CheckoutItemMapper::toArray(...), $checkoutSession->items),
            currency: $checkoutSession->total->excludingTax->currency->value,
            taxRateBasisPoints: $checkoutSession->items[0]->taxRate->basisPoints,
            shippingAddress: PostalAddressMapper::toArray($checkoutSession->shippingAddress),
            billingAddress: PostalAddressMapper::toArray($checkoutSession->billingAddress),
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
}
