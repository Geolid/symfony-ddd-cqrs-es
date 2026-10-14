<?php

declare(strict_types=1);

namespace Storefront\Controller\Account;

use Compliance\Erasing\Application\Command\CancelErasure\CancelErasure;
use Compliance\Erasing\Application\Command\RequestErasure\RequestErasure;
use Compliance\Erasing\Application\Query\GetRequestedErasureByIdentity\GetRequestedErasureByIdentity;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Storefront\Security\PasswordUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route(path: ['en' => '/account/personal-data', 'fr' => '/compte/donnees-personnelles'], name: 'storefront_account_personal_data_')]
final class PersonalDataController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly CommandBusInterface $commandBus,
        private readonly TranslatorInterface $translator,
        #[Autowire(param: 'compliance.erasing.retention_days')]
        private readonly int $retentionDays,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route(name: 'show', methods: ['GET'])]
    public function show(#[CurrentUser] PasswordUser $user): Response
    {
        return $this->render('account/personal_data/show.html.twig', [
            'erasure' => $this->queryBus->ask(new GetRequestedErasureByIdentity($user->identityId())),
            'retentionDays' => $this->retentionDays,
        ]);
    }

    #[Route(path: ['en' => '/erasure', 'fr' => '/suppression'], name: 'confirm_erasure', methods: ['GET'])]
    public function confirmErasure(): Response
    {
        return $this->render('account/personal_data/confirm_erasure.html.twig', ['retentionDays' => $this->retentionDays]);
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route(path: ['en' => '/erasure', 'fr' => '/suppression'], name: 'request_erasure', methods: ['POST'])]
    public function requestErasure(Request $request, #[CurrentUser] PasswordUser $user): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('request_erasure', (string) $request->request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('flash_invalid_csrf_token', domain: 'messages'));

            return $this->redirectToRoute('storefront_account_personal_data_confirm_erasure');
        }

        $this->commandBus->dispatch(new RequestErasure(Uuid::uuid7()->toString(), $user->identityId()));
        $this->addFlash('success', $this->translator->trans('request_erasure_flash_requested', domain: 'account_personal_data'));

        return $this->redirectToRoute('storefront_account_personal_data_show');
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route(path: ['en' => '/erasure/{id}/cancel', 'fr' => '/suppression/{id}/annuler'], name: 'cancel_erasure', methods: ['POST'])]
    public function cancelErasure(string $id, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('cancel_erasure_'.$id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('flash_invalid_csrf_token', domain: 'messages'));

            return $this->redirectToRoute('storefront_account_personal_data_show');
        }

        $this->commandBus->dispatch(new CancelErasure($id));
        $this->addFlash('success', $this->translator->trans('cancel_erasure_flash_cancelled', domain: 'account_personal_data'));

        return $this->redirectToRoute('storefront_account_personal_data_show');
    }
}
