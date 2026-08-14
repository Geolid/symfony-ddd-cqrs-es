<?php

declare(strict_types=1);

namespace Web\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Web\Form\FormData\CompleteAddressesFormData;

/**
 * @extends AbstractType<CompleteAddressesFormData>
 */
final class CompleteAddressesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'sales.order.complete_addresses.label_first_name',
                'translation_domain' => 'messages',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'sales.order.complete_addresses.label_last_name',
                'translation_domain' => 'messages',
            ])
            ->add('street', TextType::class, [
                'label' => 'sales.order.complete_addresses.label_street',
                'translation_domain' => 'messages',
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'sales.order.complete_addresses.label_postal_code',
                'translation_domain' => 'messages',
            ])
            ->add('city', TextType::class, [
                'label' => 'sales.order.complete_addresses.label_city',
                'translation_domain' => 'messages',
            ])
            ->add('billingFirstName', TextType::class, [
                'label' => 'sales.order.complete_addresses.label_billing_first_name',
                'translation_domain' => 'messages',
            ])
            ->add('billingLastName', TextType::class, [
                'label' => 'sales.order.complete_addresses.label_billing_last_name',
                'translation_domain' => 'messages',
            ])
            ->add('billingStreet', TextType::class, [
                'label' => 'sales.order.complete_addresses.label_billing_street',
                'translation_domain' => 'messages',
            ])
            ->add('billingPostalCode', TextType::class, [
                'label' => 'sales.order.complete_addresses.label_billing_postal_code',
                'translation_domain' => 'messages',
            ])
            ->add('billingCity', TextType::class, [
                'label' => 'sales.order.complete_addresses.label_billing_city',
                'translation_domain' => 'messages',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => CompleteAddressesFormData::class]);
    }
}
