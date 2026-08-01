<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Infrastructure\Notification;

use Fulfilment\Shipment\Application\Notifier\ShipmentDeliveredNotification;
use Fulfilment\Shipment\Infrastructure\Notification\MailerShipmentDeliveredNotifier;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

final class MailerShipmentDeliveredNotifierTest extends TestCase
{
    #[Test]
    public function itMailsTheCustomerThatTheirOrderArrived(): void
    {
        // Given
        $mailer = new DummyMailer();
        $notification = new ShipmentDeliveredNotification(
            shipmentId: 'shipment-1',
            orderId: 'order-1',
            customerId: 'customer-1',
            customerAddress: 'buyer@example.com',
        );

        // When
        new MailerShipmentDeliveredNotifier($mailer)->notify($notification);

        // Then
        $email = $mailer->message;
        self::assertInstanceOf(Email::class, $email);
        self::assertSame(['buyer@example.com'], array_map(
            static fn (Address $address): string => $address->getAddress(),
            $email->getTo(),
        ));
        self::assertSame('Your order order-1 has been delivered', $email->getSubject());
        self::assertStringContainsString('order-1', (string) $email->getTextBody());
        self::assertStringContainsString('shipment-1', (string) $email->getTextBody());
    }
}

final class DummyMailer implements MailerInterface
{
    public ?RawMessage $message = null;

    public function send(RawMessage $message, ?Envelope $envelope = null): void
    {
        $this->message = $message;
    }
}
