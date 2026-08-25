<?php

declare(strict_types=1);

namespace Web\Form\FormData;

use Iam\Authentication\Application\Validation\ValidPassword;

final class ChangePasswordFormData
{
    #[ValidPassword]
    public ?string $password = null;
}
