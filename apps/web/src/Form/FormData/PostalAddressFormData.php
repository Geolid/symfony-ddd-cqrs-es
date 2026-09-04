<?php

declare(strict_types=1);

namespace Web\Form\FormData;

use Shared\Application\Validation\ValidRecipientName;

final class PostalAddressFormData
{
    public function __construct(
        #[ValidRecipientName]
        public ?string $recipientName = null,
        public AddressFormData $address = new AddressFormData(),
    ) {
    }
}
