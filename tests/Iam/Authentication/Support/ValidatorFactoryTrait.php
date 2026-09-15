<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Support;

use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

trait ValidatorFactoryTrait
{
    private function validatorUsing(string $constraintValidatorClass, ConstraintValidatorInterface $validator): ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->setConstraintValidatorFactory(new ConstraintValidatorFactory([
                $constraintValidatorClass => $validator,
            ]))
            ->getValidator();
    }
}
