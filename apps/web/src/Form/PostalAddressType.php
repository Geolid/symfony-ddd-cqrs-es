<?php

declare(strict_types=1);

namespace Web\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Web\Form\FormData\PostalAddressFormData;

/**
 * @extends AbstractType<PostalAddressFormData>
 */
final class PostalAddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('recipientName', TextType::class, [
                'label' => 'sales.customer.checkout_addresses.address.label_recipient_name',
                'translation_domain' => 'messages',
            ])
            ->add('address', AddressType::class, ['label' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => PostalAddressFormData::class]);
    }
}
