<?php

declare(strict_types=1);

namespace Shipping\Shipment\Infrastructure\Notification;

use Shipping\Shipment\Application\Notifier\ShipmentDeliveredNotifierInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * The showcase has no real customer directory to resolve an email address from, so the
 * recipient is derived from the customer ID (`<customerId>@example.com`) — good enough to show
 * the pattern, not meant to be a real address resolution strategy. The sender ("From") comes
 * from the default header set in config/packages/mailer.php.
 */
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
