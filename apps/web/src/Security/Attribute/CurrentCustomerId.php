<?php

declare(strict_types=1);

namespace Web\Security\Attribute;

use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Web\Security\Resolver\CustomerIdValueResolver;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class CurrentCustomerId extends ValueResolver
{
    public function __construct(bool $disabled = false, string $resolver = CustomerIdValueResolver::class)
    {
        parent::__construct($resolver, $disabled);
    }
}
