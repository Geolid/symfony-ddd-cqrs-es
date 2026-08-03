<?php

declare(strict_types=1);

namespace Web\Form\FormData;

use Symfony\Component\Validator\Constraints as Assert;

final class ChangePasswordFormData
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    public ?string $password = null;
}
