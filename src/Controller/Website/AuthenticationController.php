<?php

declare(strict_types=1);

namespace App\Controller\Website;

use App\Entity\Event;
use App\Entity\WebUser;
use App\Form\ProfileType;
use App\Form\RegisterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthenticationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
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

    #[Route('/simple_login/{eventId}', name: 'auth_simple_login')]
    public function simpleLoginAction(AuthenticationUtils $authenticationUtils, int $eventId): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();

        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('auth/simple-login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'eventId' => $eventId,
        ]);
    }

    #[Route('/api/login/{eventId}', name: 'api_login', methods: ['POST'])]
    public function apiLogin(#[CurrentUser] ?WebUser $user, Security $security, int $eventId): Response
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
            'redirectUrl' => $this->redirectToRoute('app_event_modal_web_api', ['eventId' => $eventId]),
        ]);
    }

    #[Route('/register', name: 'auth_register')]
    public function registerAction(Request $request): Response
    {
        $webUser = new WebUser();
        $form = $this->createForm(RegisterType::class, $webUser);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $webUser = $form->getData();

            $this->entityManager->persist($webUser);
            $this->entityManager->flush();

            $this->addFlash('success', 'Úspešná registrácia');
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
    public function profileAction(Request $request): Response
    {
        /** @var WebUser $webUser */
        $webUser = $this->getUser();
        if (!$webUser) {
            $this->redirect('auth_login');
        }
        $form = $this->createForm(ProfileType::class, $webUser);

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $webUser->setFirstName($data['profile']['first_name']);
            $webUser->setLastName($data['profile']['last_name']);
            $webUser->setEmail($data['profile']['email']);
            if (!empty($data['profile']['password'])) {
                if ($data['profile']['password'] !== $data['profile']['password_confirmation']) {
                    $this->addFlash('error', 'Heslá sa nezhodujú');
                    return $this->render('auth/profile.html.twig', [
                        'form' => $form,
                    ]);
                }
                $this->addFlash('success', 'Vaše heslo bolo upravené');
                $webUser->setPassword($data['profile']['password']);
            }

            $this->entityManager->persist($webUser);
            $this->entityManager->flush();

            $this->addFlash('success', 'Váš profil bol upravený');
            $form = $this->createForm(ProfileType::class, $webUser);
        }

        return $this->render('auth/profile.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/events', name: 'auth_events')]
    public function eventsAction(): Response
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

        return $this->render('auth/events.html.twig', [
            'pastEvents' => $pastEvents,
            'futureEvents' => $futureEvents,
        ]);
    }

    #[Route('/password_reset', name:'auth_password_reset')]
    public function passwordReset(Request $request): Response
    {
        $message = '';
        if ($this->getUser()) {
            return $this->redirectToRoute('auth_profile');
        }

        if ($request->isMethod('POST')) {
            $email = $request->get('email');
            $webUser = $this->entityManager->getRepository(WebUser::class)->findOneBy(['email' => $email]);
            if (empty($webUser)) {
                $message = [
                    'status' => 'error',
                    'message' => 'Užívateľ s takýmto e-mailom neexistuje: ' . $email,
                ];
            } else {
                $webUser->generateAndSendPasswordResetLink();
                $this->entityManager->persist($webUser);
                $this->entityManager->flush();

                $message = [
                    'status' => 'ok',
                    'message' => 'Bol vám odoslaný e-mail s linkou na obnovu hesla.',
                ];
            }
        }

        return $this->render('auth/password_reset.html.twig', [
            'message' => $message,
        ]);
    }

    #[Route('/password_reset_form/{email}/{hash}', name: 'auth_password_reset_form')]
    public function passwordResetForm(string $email, string $hash, Request $request): Response
    {
        $webUser = $this->entityManager->getRepository(WebUser::class)->findOneBy(['email' => $email]);
        $message = '';

        if (!$webUser) {
            $message = 'Užívateľ s takýmto emailom neexistuje: ' . $email;
            return $this->render('auth/password_reset_form_error.html.twig', [
                'message' => $message,
            ]);

        }

        if ($webUser->getPasswordResetHash() !== $hash) {
            $message = 'Email a bezpečnostný kód sa nezhodujú';
            return $this->render('auth/password_reset_form_error.html.twig', [
                'message' => $message,
            ]);
        }

        if ($request->isMethod('POST')) {
            $email = $request->get('email');
            $password = $request->get('password');
            $passwordConfirm = $request->get('password_confirmation');

            if ($password !== $passwordConfirm) {
                $message = [
                    'status' => 'error',
                    'message' => 'Heslá sa nezhodujú',
                ];
            } else {
                $webUser->setPassword($password);
                $this->entityManager->persist($webUser);
                $this->entityManager->flush();

                return $this->redirectToRoute('auth_login');
            }
        }

        return $this->render('auth/password_reset_form.html.twig', [
            'email' => $email,
            'hash' => $hash,
            'message' => $message,
        ]);
    }
}
