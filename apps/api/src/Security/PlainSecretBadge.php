<?php

declare(strict_types=1);

namespace Api\Security;

use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

final readonly class PlainSecretBadge implements BadgeInterface
{
    public function __construct(#[\SensitiveParameter] private string $secret)
    {
    }

    public function secret(): string
    {
        return $this->secret;
    }

    public function isResolved(): bool
    {
        return true;
    }
}
