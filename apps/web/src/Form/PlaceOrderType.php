<?php

declare(strict_types=1);

namespace Web\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Web\Form\FormData\OrderLineFormData;
use Web\Form\FormData\PlaceOrderFormData;

/**
 * @extends AbstractType<PlaceOrderFormData>
 */
final class PlaceOrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customerId', ChoiceType::class, [
                'label' => 'sales.order.place.customer_label',
                'translation_domain' => 'messages',
                'placeholder' => 'sales.order.place.customer_placeholder',
                'choices' => $options['buyers'],
            ])
            ->add('lines', CollectionType::class, [
                'label' => 'sales.order.place.lines_label',
                'translation_domain' => 'messages',
                'entry_type' => OrderLineType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'by_reference' => false,
                'data' => [new OrderLineFormData()],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults(['data_class' => PlaceOrderFormData::class])
            ->setRequired('buyers')
            ->setAllowedTypes('buyers', 'string[]');
    }
}
