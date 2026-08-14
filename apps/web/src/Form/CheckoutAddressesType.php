<?php

declare(strict_types=1);

namespace Web\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Web\Form\FormData\CheckoutAddressesFormData;

/**
 * @extends AbstractType<CheckoutAddressesFormData>
 */
final class CheckoutAddressesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('shipping', PostalAddressType::class, ['label' => false])
            ->add('billing', PostalAddressType::class, ['label' => false])
            ->add('sameAsShipping', CheckboxType::class, [
                'label' => 'sales.customer.checkout_addresses.label_same_as_shipping',
                'translation_domain' => 'messages',
                'required' => false,
            ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, static function (FormEvent $event): void {
            $data = $event->getData();

            if (!\is_array($data) || empty($data['sameAsShipping']) || !\is_array($data['shipping'] ?? null)) {
                return;
            }

            $data['billing'] = $data['shipping'];
            $event->setData($data);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => CheckoutAddressesFormData::class]);
    }
}
