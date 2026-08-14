<?php

declare(strict_types=1);

namespace Web\Form\FormData;

final class PostalAddressFormData
{
    public function __construct(
        public FullNameFormData $fullName = new FullNameFormData(),
        public AddressFormData $address = new AddressFormData(),
    ) {
    }
}
