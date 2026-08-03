<?php

declare(strict_types=1);

namespace Web\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Web\Form\FormData\OrderLineFormData;

/**
 * @extends AbstractType<OrderLineFormData>
 */
final class OrderLineType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('productId', ChoiceType::class, [
                'label' => 'sales.order.place.line_product_label',
                'translation_domain' => 'messages',
                'choices' => $options['products'],
                'placeholder' => 'sales.order.place.line_product_placeholder',
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'sales.order.place.line_quantity_label',
                'translation_domain' => 'messages',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => OrderLineFormData::class]);
        $resolver->setRequired('products');
        $resolver->setAllowedTypes('products', 'array');
    }
}
