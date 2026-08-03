<?php

declare(strict_types=1);

namespace Web\Form;

use Symfony\Component\Form\AbstractType;
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
        $builder->add('lines', CollectionType::class, [
            'label' => 'sales.order.place.lines_label',
            'translation_domain' => 'messages',
            'entry_type' => OrderLineType::class,
            'entry_options' => ['label' => false, 'products' => $options['products']],
            'allow_add' => true,
            'allow_delete' => true,
            'prototype' => true,
            'by_reference' => false,
            'data' => [new OrderLineFormData()],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => PlaceOrderFormData::class]);
        $resolver->setRequired('products');
        $resolver->setAllowedTypes('products', 'array');
    }
}
