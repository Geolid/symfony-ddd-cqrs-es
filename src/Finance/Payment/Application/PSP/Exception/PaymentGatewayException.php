<?php

declare(strict_types=1);

namespace Finance\Payment\Application\PSP\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

abstract class PaymentGatewayException extends \RuntimeException implements ApplicationExceptionInterface
{
}
