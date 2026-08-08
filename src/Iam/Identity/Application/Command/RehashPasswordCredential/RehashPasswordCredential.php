<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\RehashPasswordCredential;

use Shared\Application\Command\CommandInterface;

final readonly class RehashPasswordCredential implements CommandInterface
{
    public function __construct(
        public string $identityId,
        #[\SensitiveParameter]
        public string $plainSecret,
    ) {
    }
}
