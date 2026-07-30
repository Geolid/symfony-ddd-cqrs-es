<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class AddressAlreadyRegisteredException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forFingerprint(string $fingerprint): self
    {
        return new self(\sprintf('An address with fingerprint "%s" is already registered.', $fingerprint));
    }
}
