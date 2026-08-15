<?php

declare(strict_types=1);

namespace Web\Form\FormData;

use Iam\Identity\Application\Validation\ValidLogin;
use Iam\Identity\Application\Validation\ValidPassword;
use Shared\Application\Validation\ValidEmail;

final class RegisterCustomerFormData
{
    #[ValidLogin]
    public ?string $login = null;

    #[ValidEmail]
    public ?string $email = null;

    #[ValidPassword]
    public ?string $password = null;
}
