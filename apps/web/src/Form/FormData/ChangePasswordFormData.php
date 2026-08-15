<?php

declare(strict_types=1);

namespace Web\Form\FormData;

use Iam\Identity\Application\Validation\ValidPassword;

final class ChangePasswordFormData
{
    #[ValidPassword]
    public ?string $password = null;
}
