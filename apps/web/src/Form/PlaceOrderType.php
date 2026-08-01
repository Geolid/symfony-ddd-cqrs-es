<?php

declare(strict_types=1);

namespace Web\Form;

use Sales\Customer\Application\Query\StreamRegisteredCustomers\StreamRegisteredCustomers;
use Shared\Application\Exception\ApplicationExceptionInterface;
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
    public function __construct(private readonly QueryBusInterface $queryBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     */
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
     *
     * @throws ApplicationExceptionInterface
     */
    private function buyers(): array
    {
        $buyers = [];

        foreach ($this->queryBus->ask(new StreamRegisteredCustomers()) as $customer) {
            $buyers[(string) $customer->email] = $customer->id;
        }

        return $buyers;
    }
}
