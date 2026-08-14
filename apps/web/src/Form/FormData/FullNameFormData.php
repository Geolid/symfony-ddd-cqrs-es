<?php

declare(strict_types=1);

namespace Web\Form\FormData;

use Shared\Application\Validation\ValidFullName;

final class FullNameFormData
{
    public ?string $firstName = null;

    public ?string $lastName = null;

    /**
     * @return array{firstName: ?string, lastName: ?string}
     */
    #[ValidFullName]
    public function getFullNameData(): array
    {
        return ['firstName' => $this->firstName, 'lastName' => $this->lastName];
    }
}
