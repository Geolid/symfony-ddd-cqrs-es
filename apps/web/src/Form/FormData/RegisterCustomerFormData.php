<?php

declare(strict_types=1);

namespace Web\Form\FormData;

use Iam\Identity\Application\Validation\ValidLogin;
use Iam\Identity\Application\Validation\ValidPassword;
use Iam\Identity\Application\Validation\ValidUniqueLogin;
use Sales\Customer\Application\Validation\ValidEmail;
use Sales\Customer\Application\Validation\ValidUniqueCustomerEmail;

final class RegisterCustomerFormData
{
    #[ValidLogin]
    #[ValidUniqueLogin]
    public ?string $login = null;

    #[ValidEmail]
    #[ValidUniqueCustomerEmail]
    public ?string $email = null;

    #[ValidPassword]
    public ?string $password = null;
}
