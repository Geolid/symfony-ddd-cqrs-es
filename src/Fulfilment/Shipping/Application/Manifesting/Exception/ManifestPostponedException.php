<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Manifesting\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class ManifestPostponedException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forUnpaidOrder(string $shipmentId): self
    {
        return new self(\sprintf('Cannot manifest shipment "%s" yet: the related order is not paid yet.', $shipmentId));
    }
}
