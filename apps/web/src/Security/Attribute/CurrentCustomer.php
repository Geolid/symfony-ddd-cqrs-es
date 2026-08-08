<?php

declare(strict_types=1);

namespace Web\Security\Attribute;

use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Web\Security\Resolver\CurrentCustomerValueResolver;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class CurrentCustomer extends ValueResolver
{
    public function __construct(bool $disabled = false, string $resolver = CurrentCustomerValueResolver::class)
    {
        parent::__construct($resolver, $disabled);
    }
}
