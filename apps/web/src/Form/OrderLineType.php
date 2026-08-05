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
        /** @var array<string, int> $productPricesInCents */
        $productPricesInCents = $options['productPricesInCents'];

        $builder
            ->add('productId', ChoiceType::class, [
                'label' => 'sales.order.place.label_line_product',
                'translation_domain' => 'messages',
                'choices' => $options['products'],
                'placeholder' => 'sales.order.place.placeholder_line_product',
                'choice_attr' => static fn (string $productId): array => [
                    'data-price-cents' => $productPricesInCents[$productId] ?? 0,
                ],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'sales.order.place.label_line_quantity',
                'translation_domain' => 'messages',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => OrderLineFormData::class]);
        $resolver->setRequired(['products', 'productPricesInCents']);
        $resolver->setAllowedTypes('products', 'array');
        $resolver->setAllowedTypes('productPricesInCents', 'array');
    }
}
