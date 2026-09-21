<?php

declare(strict_types=1);

namespace Storefront\Form\TwoFactorConfirm;

use Iam\Authentication\Application\Validation\ValidTotpCode;

final class TwoFactorConfirmFormData
{
    #[ValidTotpCode]
    public ?string $code = null;
}
