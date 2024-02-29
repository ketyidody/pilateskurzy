<?php

namespace App\Service;

use App\Entity\WebUser;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class AuthMailerService
{
    public function __construct(
        protected MailerInterface $mailer,
        protected Environment $twig,
        protected RouterInterface $router
    ) {
    }

    public function sendPasswordResetEmail(WebUser $webUser)
    {
        $messageTemplate = $this->twig->render('auth/password-reset-email.html.twig', [
            'resetLink' => $this->router->generate('auth_password_reset_form', [
                'hash' => $webUser->getPasswordResetHash(),
                'email' => $webUser->getEmail(),
            ]),
        ]);
        $email = (new Email())
            ->from('admin@pilateskurzy.sk')
            ->to($webUser->getEmail())
            ->subject('Obnova hesla')
            ->html($messageTemplate);
        $this->mailer->send($email);
    }
}