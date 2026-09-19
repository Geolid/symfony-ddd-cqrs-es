<?php

declare(strict_types=1);

namespace Storefront\Form;

use Storefront\Form\FormData\ConfirmationFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<ConfirmationFormData>
 */
final class ConfirmationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('code', TextType::class, ['attr' => [
            'data-testid' => 'confirmation-code',
            'inputmode' => 'numeric',
            'pattern' => '\d{6}',
            'maxlength' => 6,
            'autocomplete' => 'one-time-code',
        ]]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ConfirmationFormData::class,
            'csrf_token_id' => 'confirmation',
        ]);
    }
}
