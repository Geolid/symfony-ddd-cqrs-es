<?php

declare(strict_types=1);

namespace Finance\Payment\Infrastructure\PSP\Globex;

use Finance\Payment\Application\PSP\PaymentGatewayException;

final class GlobexClientException extends PaymentGatewayException
{
    public static function networkFailure(string $path, string $reason): self
    {
        return new self(\sprintf('Globex network failure on "%s": %s', $path, $reason));
    }

    public static function invalidResponse(string $path, string $reason): self
    {
        return new self(\sprintf('Globex invalid response on "%s": %s', $path, $reason));
    }
}
