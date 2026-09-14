<?php

declare(strict_types=1);

namespace Support\Faker;

use Faker\Provider\Base;
use Shared\Domain\ValueObject\CountryCode;
use Webmozart\Assert\Assert;

final class CountryCodeFakerProvider extends Base
{
    public function countryCode(): string
    {
        Assert::string($countryCode = $this->generator->randomElement(array_diff(CountryCode::values(), [CountryCode::ZZ->value])));

        return $countryCode;
    }
}
