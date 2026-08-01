<?php

declare(strict_types=1);

namespace Web\Form\FormData;

use Sales\Customer\Application\Validation\ValidEmail;

final class RegisterCustomerFormData
{
    #[ValidEmail]
    public ?string $email = null;
}
