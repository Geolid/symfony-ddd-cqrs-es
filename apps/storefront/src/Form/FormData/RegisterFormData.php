<?php

declare(strict_types=1);

namespace Storefront\Form\FormData;

use Iam\Authentication\Application\Validation\ValidLogin;
use Iam\Authentication\Application\Validation\ValidPassword;
use Iam\Authentication\Application\Validation\ValidUniqueLogin;

final class RegisterFormData
{
    #[ValidLogin]
    #[ValidUniqueLogin]
    public ?string $login = null;

    #[ValidPassword]
    public ?string $password = null;
}
