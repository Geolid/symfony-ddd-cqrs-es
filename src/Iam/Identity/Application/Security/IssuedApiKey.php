<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Security;

use Shared\Application\Language\PublishedLanguageInterface;

final readonly class IssuedApiKey implements PublishedLanguageInterface
{
    public function __construct(
        public string $identifier,
        public string $secret,
    ) {
    }
}
