<?php

declare(strict_types=1);

namespace Storefront\Form\FormData;

use Iam\Authentication\Application\Validation\ValidPassword;
use Iam\Identity\Application\Validation\ValidEmail;
use Iam\Identity\Application\Validation\ValidFullName;
use Iam\Identity\Application\Validation\ValidUniqueEmail;

final class RegisterFormData
{
    #[ValidEmail]
    #[ValidUniqueEmail]
    public ?string $email = null;

    #[ValidFullName]
    public ?string $fullName = null;

    #[ValidPassword]
    public ?string $password = null;
}
