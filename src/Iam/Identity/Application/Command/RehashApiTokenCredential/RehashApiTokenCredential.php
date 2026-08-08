<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\RehashApiTokenCredential;

use Shared\Application\Command\CommandInterface;

final readonly class RehashApiTokenCredential implements CommandInterface
{
    public function __construct(
        public string $identifier,
        #[\SensitiveParameter]
        public string $plainSecret,
    ) {
    }
}
