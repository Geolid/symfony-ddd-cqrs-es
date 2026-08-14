<?php

declare(strict_types=1);

namespace Web\Form\FormData;

final class CheckoutAddressesFormData
{
    public function __construct(
        public PostalAddressFormData $shipping = new PostalAddressFormData(),
        public PostalAddressFormData $billing = new PostalAddressFormData(),
        public bool $sameAsShipping = false,
    ) {
    }
}
