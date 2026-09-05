<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Carrier;

use Shared\Application\Exception\ApplicationExceptionInterface;

abstract class CarrierGatewayException extends \RuntimeException implements ApplicationExceptionInterface
{
}
