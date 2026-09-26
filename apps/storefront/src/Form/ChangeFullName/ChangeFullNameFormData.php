<?php

declare(strict_types=1);

namespace Storefront\Form\ChangeFullName;

use Iam\Identity\Application\Validation\ValidFullName;

final class ChangeFullNameFormData
{
    #[ValidFullName]
    public ?string $fullName = null;
}
