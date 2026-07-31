<?php

declare(strict_types=1);

namespace Web\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
            ->add('label', TextType::class, [
                'label' => 'sales.order.place.line_label',
                'translation_domain' => 'messages',
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'sales.order.place.line_quantity_label',
                'translation_domain' => 'messages',
            ])
            ->add('unitAmountInCents', IntegerType::class, [
                'label' => 'sales.order.place.line_unit_amount_label',
                'translation_domain' => 'messages',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => OrderLineFormData::class]);
    }
}
