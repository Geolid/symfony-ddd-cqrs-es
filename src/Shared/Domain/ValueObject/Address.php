<?php

declare(strict_types=1);

namespace Shared\Domain\ValueObject;

use Webmozart\Assert\Assert;

final readonly class Address
{
    public const int STREET_MAX_LENGTH = 255;
    public const int POSTAL_CODE_MAX_LENGTH = 20;
    public const int CITY_MAX_LENGTH = 255;

    public string $street;

    public string $postalCode;

    public string $city;

    public CountryCode $countryCode;

    private function __construct(string $street, string $postalCode, string $city, string $countryCode)
    {
        $street = trim($street);
        $postalCode = trim($postalCode);
        $city = trim($city);
        Assert::notEmpty($street, 'A street cannot be empty, %s given.');
        Assert::maxLength($street, self::STREET_MAX_LENGTH, 'A street cannot exceed %2$d characters, %s given.');
        Assert::notEmpty($postalCode, 'A postal code cannot be empty, %s given.');
        Assert::maxLength($postalCode, self::POSTAL_CODE_MAX_LENGTH, 'A postal code cannot exceed %2$d characters, %s given.');
        Assert::notEmpty($city, 'A city cannot be empty, %s given.');
        Assert::maxLength($city, self::CITY_MAX_LENGTH, 'A city cannot exceed %2$d characters, %s given.');
        Assert::oneOf($countryCode, array_column(CountryCode::cases(), 'value'), 'A country code must be a valid ISO 3166-1 alpha-2 code, %s given.');

        $this->street = $street;
        $this->postalCode = $postalCode;
        $this->city = $city;
        $this->countryCode = CountryCode::from($countryCode);
    }

    public static function of(string $street, string $postalCode, string $city, string $countryCode): self
    {
        return new self($street, $postalCode, $city, $countryCode);
    }

    public function equals(self $other): bool
    {
        return $this->street === $other->street
            && $this->postalCode === $other->postalCode
            && $this->city === $other->city
            && $this->countryCode === $other->countryCode;
    }

    public function toString(): string
    {
        return \sprintf('%s, %s %s, %s', $this->street, $this->postalCode, $this->city, $this->countryCode->value);
    }
}
