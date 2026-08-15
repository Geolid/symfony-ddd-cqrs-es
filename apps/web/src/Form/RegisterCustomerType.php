<?php

declare(strict_types=1);

namespace Web\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Web\Form\FormData\RegisterCustomerFormData;

/**
 * @extends AbstractType<RegisterCustomerFormData>
 */
final class RegisterCustomerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('login', TextType::class, [
                'label' => 'sales.customer.register.label_login',
                'translation_domain' => 'messages',
            ])
            ->add('email', EmailType::class, [
                'label' => 'sales.customer.register.label_email',
                'translation_domain' => 'messages',
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'sales.customer.register.label_password',
                    'translation_domain' => 'messages',
                ],
                'second_options' => [
                    'label' => 'sales.customer.register.label_password_confirm',
                    'translation_domain' => 'messages',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => RegisterCustomerFormData::class]);
    }
}
