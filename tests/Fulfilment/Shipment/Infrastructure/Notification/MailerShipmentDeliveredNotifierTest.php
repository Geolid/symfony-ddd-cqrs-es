<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Infrastructure\Notification;

use Fulfilment\Shipment\Application\Notifier\ShipmentDeliveredNotification;
use Fulfilment\Shipment\Infrastructure\Notification\MailerShipmentDeliveredNotifier;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
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
        $shipmentId = Uuid::uuid7()->toString();
        $orderId = Uuid::uuid7()->toString();
        $notification = new ShipmentDeliveredNotification(
            shipmentId: $shipmentId,
            orderId: $orderId,
            customerId: Uuid::uuid7()->toString(),
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
        self::assertSame(\sprintf('Your order %s has been delivered', $orderId), $email->getSubject());
        self::assertStringContainsString($orderId, (string) $email->getTextBody());
        self::assertStringContainsString($shipmentId, (string) $email->getTextBody());
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
