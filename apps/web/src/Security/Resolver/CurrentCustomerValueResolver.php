<?php

declare(strict_types=1);

namespace Web\Security\Resolver;

use Sales\Customer\Application\Finder\Customer\CustomerResult;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Web\Security\CustomerIdentityResolver;

#[AsTargetedValueResolver]
final readonly class CurrentCustomerValueResolver implements ValueResolverInterface
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private CustomerIdentityResolver $customerIdentityResolver,
    ) {
    }

    /**
     * @return iterable<CustomerResult>
     *
     * @throws ApplicationExceptionInterface
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            throw new AccessDeniedException('No authenticated token to resolve a customer from.');
        }

        return [$this->customerIdentityResolver->resolveFor($token)];
    }
}
