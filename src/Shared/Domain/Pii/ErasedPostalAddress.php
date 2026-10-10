<?php

declare(strict_types=1);

namespace Shared\Domain\Pii;

use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;

final readonly class ErasedPostalAddress
{
    public function __invoke(): PostalAddress
    {
        return PostalAddress::of('erased', Address::of('erased', '00000', 'erased', 'ZZ'));
    }
}
