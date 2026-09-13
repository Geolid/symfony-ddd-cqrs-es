<?php

declare(strict_types=1);

namespace Shopping\Checkout\Infrastructure\Tax;

use Shared\Domain\ValueObject\CountryCode;
use Shopping\Checkout\Application\Tax\TaxRateResolverInterface;
use Shopping\Checkout\Domain\ValueObject\TaxRate;
use Webmozart\Assert\Assert;

final class StaticTaxRateResolver implements TaxRateResolverInterface
{
    private const array RATES = [
        'FR' => 2000,
        'DE' => 1900,
        'IT' => 2200,
    ];

    public function resolve(CountryCode $country): TaxRate
    {
        $basisPoints = self::RATES[$country->value] ?? null;
        Assert::notNull($basisPoints, \sprintf('No VAT rate configured for country %s.', $country->value));

        return TaxRate::fromBasisPoints($basisPoints);
    }
}
