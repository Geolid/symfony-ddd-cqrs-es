<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Infrastructure\Notification;

use Fulfilment\Shipment\Application\Notifier\ShipmentDeliveredNotifierInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final readonly class MailerShipmentDeliveredNotifier implements ShipmentDeliveredNotifierInterface
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    public function notify(string $shipmentId, string $orderId, string $customerId): void
    {
        $email = (new Email())
            ->to(\sprintf('%s@example.com', $customerId))
            ->subject(\sprintf('Your order %s has been delivered', $orderId))
            ->text(\sprintf(
                "Good news — your order %s (shipment %s) has just been delivered.\n",
                $orderId,
                $shipmentId,
            ));

        $this->mailer->send($email);
    }
}
