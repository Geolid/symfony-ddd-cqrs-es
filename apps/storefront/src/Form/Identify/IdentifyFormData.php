<?php

declare(strict_types=1);

namespace Storefront\Form\Identify;

use Iam\Identity\Application\Validation\ValidEmail;

final class IdentifyFormData
{
    #[ValidEmail]
    public ?string $email = null;
}
