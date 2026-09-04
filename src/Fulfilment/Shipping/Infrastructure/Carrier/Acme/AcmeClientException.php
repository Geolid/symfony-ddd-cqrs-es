<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Infrastructure\Carrier\Acme;

final class AcmeClientException extends \RuntimeException
{
    public static function networkFailure(string $path, string $reason): self
    {
        return new self(\sprintf('Acme network failure on "%s": %s', $path, $reason));
    }

    public static function invalidResponse(string $path, string $reason): self
    {
        return new self(\sprintf('Acme invalid response on "%s": %s', $path, $reason));
    }
}
