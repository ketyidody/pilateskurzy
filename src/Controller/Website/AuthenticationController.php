<?php

declare(strict_types=1);

namespace App\Controller\Website;

use App\Entity\Event;
use App\Entity\WebUser;
use App\Form\RegisterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthenticationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('/login', name: 'auth_login')]
    public function loginAction(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('auth_profile');
        }

        $error = $authenticationUtils->getLastAuthenticationError();

        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('auth/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/simple_login', name: 'auth_simple_login')]
    public function simpleLoginAction(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();

        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('auth/simple-login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function apiLogin(#[CurrentUser] ?WebUser $user, Security $security): Response
    {
        if (null === $user) {
            return $this->json([
                'message' => 'missing credentials',
                ], Response::HTTP_UNAUTHORIZED);
        }

        $token = $security->getToken();

        return $this->json([
            'user' => $user->getUserIdentifier(),
            'token' => $token,
            'redirectUrl' => $this->redirect('/api/event/modal/6'),
        ]);
    }

    #[Route('/register', name: 'auth_register')]
    public function registerAction(Request $request): Response
    {
        $webUser = new WebUser($this->passwordHasher);
        $form = $this->createForm(RegisterType::class, $webUser);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $webUser = $form->getData();

            $this->entityManager->persist($webUser);
            $this->entityManager->flush();

            $this->addFlash('success', 'Successful registration');
            $this->redirectToRoute('auth_login');
        }

        return $this->render('auth/register.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/logout', name: 'auth_logout')]
    public function logoutAction(Security $security): Response
    {
        $response = $security->logout(false);

        return $this->redirect('/');
    }

    #[Route('/profile', name: 'auth_profile')]
    public function profileAction(): Response
    {
        /** @var WebUser $webUser */
        if (! $webUser = $this->getUser()) {
            return $this->redirectToRoute('auth_login');
        }

        $events = $this->entityManager->getRepository(Event::class)
            ->findByUser($webUser);

        usort($events, fn (Event $a, Event $b) => $a->getDateTime() > $b->getDateTime());
        $now = new \DateTime();
        $pastEvents = array_filter($events, function(Event $event) use ($now) {
            return $event->getDateTime() < $now;
        });
        $futureEvents = array_filter($events, function(Event $event) use ($now) {
            return $event->getDateTime() > $now;
        });

        return $this->render('auth/profile.html.twig', [
            'pastEvents' => $pastEvents,
            'futureEvents' => $futureEvents,
        ]);
    }
}
