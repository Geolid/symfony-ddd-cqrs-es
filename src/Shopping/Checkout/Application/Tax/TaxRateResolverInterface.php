<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Tax;

use Shared\Domain\ValueObject\CountryCode;
use Shopping\Checkout\Domain\ValueObject\TaxRate;

interface TaxRateResolverInterface
{
    public function resolve(CountryCode $country): TaxRate;
}
