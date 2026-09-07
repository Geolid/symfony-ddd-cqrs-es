<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\Command\RegisterSubject;

use Shared\Application\Command\CommandInterface;

final readonly class RegisterSubject implements CommandInterface
{
    public function __construct(
        public string $identityId,
        public \DateTimeImmutable $registeredAt,
    ) {
    }
}
