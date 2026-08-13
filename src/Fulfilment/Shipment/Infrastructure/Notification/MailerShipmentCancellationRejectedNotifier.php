<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Infrastructure\Notification;

use Fulfilment\Shipment\Application\Notifier\ShipmentCancellationRejectedNotification;
use Fulfilment\Shipment\Application\Notifier\ShipmentCancellationRejectedNotifierInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final readonly class MailerShipmentCancellationRejectedNotifier implements ShipmentCancellationRejectedNotifierInterface
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    public function notify(ShipmentCancellationRejectedNotification $notification): void
    {
        $email = (new Email())
            ->to($notification->customerAddress)
            ->subject(\sprintf('Your order %s could not be cancelled', $notification->orderId))
            ->text(\sprintf(
                "Your order %s (shipment %s) had already left our warehouse when the cancellation was requested — it will still be delivered.\n",
                $notification->orderId,
                $notification->shipmentId,
            ));

        $this->mailer->send($email);
    }
}
