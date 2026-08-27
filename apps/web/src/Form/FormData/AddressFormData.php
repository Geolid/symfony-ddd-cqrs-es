<?php

declare(strict_types=1);

namespace Web\Form\FormData;

use Shared\Application\Validation\ValidAddress;

final class AddressFormData
{
    public ?string $street = null;

    public ?string $postalCode = null;

    public ?string $city = null;

    public ?string $countryCode = null;

    /**
     * @return array{street: ?string, postalCode: ?string, city: ?string, countryCode: ?string}
     */
    #[ValidAddress]
    public function getAddressData(): array
    {
        return [
            'street' => $this->street,
            'postalCode' => $this->postalCode,
            'city' => $this->city,
            'countryCode' => $this->countryCode,
        ];
    }
}
