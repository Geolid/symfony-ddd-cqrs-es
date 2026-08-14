<?php

declare(strict_types=1);

namespace Web\Form\FormData;

use Symfony\Component\Validator\Constraints as Assert;

final class CompleteAddressesFormData
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $firstName = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $lastName = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $street = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    public ?string $postalCode = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $city = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $billingFirstName = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $billingLastName = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $billingStreet = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    public ?string $billingPostalCode = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $billingCity = null;
}
