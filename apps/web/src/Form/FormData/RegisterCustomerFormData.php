<?php

declare(strict_types=1);

namespace Web\Form\FormData;

use Iam\Authentication\Application\Validation\ValidLogin;
use Iam\Authentication\Application\Validation\ValidPassword;
use Iam\Authentication\Application\Validation\ValidUniqueLogin;
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
