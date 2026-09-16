<?php

declare(strict_types=1);

namespace Storefront\Controller;

use Iam\Authentication\Application\Command\DefinePasswordCredential\DefinePasswordCredential;
use Iam\Authentication\Application\Command\DefinePasswordCredential\Exception\PasswordCredentialLoginAlreadyInUseException;
use Iam\Identity\Application\Command\EraseIdentity\EraseIdentity;
use Iam\Identity\Application\Command\RegisterIdentity\RegisterIdentity;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Storefront\Form\FormData\RegisterFormData;
use Storefront\Form\RegisterType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController extends AbstractController
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Route(path: '/inscription', name: 'storefront_register', methods: ['GET', 'POST'])]
    public function register(Request $request): Response
    {
        $formData = new RegisterFormData();
        $form = $this->createForm(RegisterType::class, $formData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $id = Uuid::uuid7()->toString();

            $this->commandBus->dispatch(new RegisterIdentity($id));

            try {
                $this->commandBus->dispatch(new DefinePasswordCredential($id, (string) $formData->login, (string) $formData->password));
            } catch (PasswordCredentialLoginAlreadyInUseException) {
                $this->commandBus->dispatch(new EraseIdentity($id));
                $this->addFlash('error', 'Ce login est déjà utilisé.');

                return $this->render('registration/register.html.twig', ['form' => $form]);
            } catch (\Throwable $e) {
                $this->commandBus->dispatch(new EraseIdentity($id));

                throw $e;
            }

            $this->addFlash('success', 'Inscription réussie, vous pouvez vous connecter.');

            return $this->redirectToRoute('security_login');
        }

        return $this->render('registration/register.html.twig', ['form' => $form]);
    }
}
