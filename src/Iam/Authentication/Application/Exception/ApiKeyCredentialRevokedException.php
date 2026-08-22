<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class ApiKeyCredentialRevokedException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forKeyId(string $keyId): self
    {
        return new self(\sprintf('API key credential "%s" has been revoked.', $keyId));
    }
}
