<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Payment\Globex\Exception;

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
