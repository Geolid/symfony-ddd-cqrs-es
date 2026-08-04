<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Language;

use Iam\Identity\Domain\ValueObject\IdentityStatus;
use Shared\Application\Language\PublishedLanguageInterface;

enum PublishedIdentityStatus: string implements PublishedLanguageInterface
{
    case ACTIVE = IdentityStatus::ACTIVE->value;
    case SUSPENDED = IdentityStatus::SUSPENDED->value;

    public function isActive(): bool
    {
        return self::ACTIVE === $this;
    }
}
