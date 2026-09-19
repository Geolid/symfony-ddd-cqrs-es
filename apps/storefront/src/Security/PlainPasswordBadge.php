<?php

declare(strict_types=1);

namespace Storefront\Security;

use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

final readonly class PlainPasswordBadge implements BadgeInterface
{
    public function __construct(#[\SensitiveParameter] private string $plainPassword)
    {
    }

    public function plainPassword(): string
    {
        return $this->plainPassword;
    }

    public function isResolved(): bool
    {
        return true;
    }
}
