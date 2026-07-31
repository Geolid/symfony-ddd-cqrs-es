<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Infrastructure\Mailing;

use Fulfilment\Shipment\Application\Mailing\ShipmentDeliveredNotification;
use Fulfilment\Shipment\Application\Mailing\ShipmentMailerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final readonly class ShipmentMailer implements ShipmentMailerInterface
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    public function sendDelivered(ShipmentDeliveredNotification $notification): void
    {
        $this->mailer->send(new Email()
            ->to(\sprintf('%s@example.com', $notification->customerId))
            ->subject(\sprintf('Your order %s has been delivered', $notification->orderId))
            ->text(\sprintf(
                "Good news — your order %s (shipment %s) has just been delivered.\n",
                $notification->orderId,
                $notification->shipmentId,
            )));
    }
}
