<?php

declare(strict_types=1);

namespace Storefront\Form\RequestEmailChange;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<RequestEmailChangeFormData>
 */
final class RequestEmailChangeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('newEmail', EmailType::class, [
            'label' => 'request_email_change_label_new_email',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RequestEmailChangeFormData::class,
            'csrf_token_id' => 'request_email_change',
            'translation_domain' => 'account_security',
        ]);
    }
}
