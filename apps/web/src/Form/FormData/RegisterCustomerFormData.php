<?php

declare(strict_types=1);

namespace Web\Form\FormData;

use Sales\Customer\Application\Validation\ValidEmail;
use Symfony\Component\Validator\Constraints as Assert;

final class RegisterCustomerFormData
{
    #[ValidEmail]
    public ?string $email = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    public ?string $password = null;
}
