<?php

declare(strict_types=1);

namespace Web\Form;

use Shared\Domain\ValueObject\CountryCode;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Web\Form\FormData\AddressFormData;

/**
 * @extends AbstractType<AddressFormData>
 */
final class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('street', TextType::class, [
                'label' => 'sales.customer.checkout_addresses.address.label_street',
                'translation_domain' => 'messages',
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'sales.customer.checkout_addresses.address.label_postal_code',
                'translation_domain' => 'messages',
            ])
            ->add('city', TextType::class, [
                'label' => 'sales.customer.checkout_addresses.address.label_city',
                'translation_domain' => 'messages',
            ])
            ->add('countryCode', EnumType::class, [
                'class' => CountryCode::class,
                'label' => 'sales.customer.checkout_addresses.address.label_country',
                'translation_domain' => 'messages',
                'choice_label' => static fn (CountryCode $countryCode): string => $countryCode->value,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AddressFormData::class,
            'error_mapping' => [
                'addressData[street]' => 'street',
                'addressData[postalCode]' => 'postalCode',
                'addressData[city]' => 'city',
                'addressData[countryCode]' => 'countryCode',
            ],
        ]);
    }
}
