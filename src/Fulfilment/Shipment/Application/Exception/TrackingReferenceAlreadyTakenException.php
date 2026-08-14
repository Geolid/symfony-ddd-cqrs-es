<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class TrackingReferenceAlreadyTakenException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forReference(string $reference, \Throwable $previous): self
    {
        return new self(
            message: \sprintf('The tracking reference "%s" is already assigned to another shipment.', $reference),
            previous: $previous,
        );
    }
}
