<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Command\LinkCustomerIdentity;

use Shared\Application\Command\CommandInterface;

final readonly class LinkCustomerIdentity implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $identityId,
    ) {
    }
}
