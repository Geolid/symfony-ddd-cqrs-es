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

        $commandBus = $this->createMock(CommandBusInterface::class);
        $this->replace(CommandBusInterface::class, $commandBus);
        $commandBus->expects(self::once())->method('dispatch')->with(new CompleteCheckoutSession(
            id: $checkoutSession->id->toString(),
            cartId: $builder['cartId'],
            customerId: $builder['customerId'],
            items: array_map(CheckoutItemMapper::toArray(...), $builder['items']),
            currency: $builder['currency']->value,
            taxRateBasisPoints: $builder['taxRate']->basisPoints,
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
}
