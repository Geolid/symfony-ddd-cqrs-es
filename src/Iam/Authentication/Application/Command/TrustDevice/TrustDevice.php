<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\TrustDevice;

use Shared\Application\Command\CommandInterface;

final readonly class TrustDevice implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $identityId,
        public int $version,
        public string $userAgent,
        public string $ip,
    ) {
    }
}
