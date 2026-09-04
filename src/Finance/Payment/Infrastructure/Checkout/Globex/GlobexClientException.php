<?php

declare(strict_types=1);

namespace Finance\Payment\Infrastructure\Checkout\Globex;

final class GlobexClientException extends \RuntimeException
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
