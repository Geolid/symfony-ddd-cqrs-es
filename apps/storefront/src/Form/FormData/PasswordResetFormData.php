<?php

declare(strict_types=1);

namespace Storefront\Form\FormData;

use Iam\Authentication\Application\Validation\ValidPassword;
use Iam\Identity\Application\Validation\ValidConfirmationCode;

final class PasswordResetFormData
{
    #[ValidConfirmationCode]
    public ?string $code = null;

    #[ValidPassword]
    public ?string $newPassword = null;
}
