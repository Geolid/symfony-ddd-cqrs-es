<?php

declare(strict_types=1);

namespace Web\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Web\Form\FormData\FullNameFormData;

/**
 * @extends AbstractType<FullNameFormData>
 */
final class FullNameType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'sales.customer.checkout_addresses.address.label_first_name',
                'translation_domain' => 'messages',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'sales.customer.checkout_addresses.address.label_last_name',
                'translation_domain' => 'messages',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FullNameFormData::class,
            'error_mapping' => [
                'fullNameData[firstName]' => 'firstName',
                'fullNameData[lastName]' => 'lastName',
            ],
        ]);
    }
}
