<?php

declare(strict_types=1);

namespace Web\Form;

use Sales\Customer\Application\Query\ListCustomers\ListCustomers;
use Shared\Application\Query\QueryBusInterface;
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
    private const int BUYERS_OFFERED = 100;

    public function __construct(private readonly QueryBusInterface $queryBus)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customerId', ChoiceType::class, [
                'label' => 'sales.order.place.customer_label',
                'translation_domain' => 'messages',
                'placeholder' => 'sales.order.place.customer_placeholder',
                'choices' => $this->buyers(),
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
        $resolver->setDefaults(['data_class' => PlaceOrderFormData::class]);
    }

    /**
     * @return array<string, string>
     */
    private function buyers(): array
    {
        $buyers = [];

        foreach ($this->queryBus->ask(new ListCustomers(itemsPerPage: self::BUYERS_OFFERED))->items as $customer) {
            if (null !== $customer->email) {
                $buyers[$customer->email] = $customer->id;
            }
        }

        return $buyers;
    }
}
