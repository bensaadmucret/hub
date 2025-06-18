<?php

namespace App\Core\Service;

use App\Core\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid as UuidGenerator;

class EmailVerificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private string $appEnv,
        private string $defaultFromEmail
    ) {
    }

    public function generateToken(): string
    {
        return UuidGenerator::v4()->toRfc4122();
    }

    public function sendVerificationEmail(User $user, string $verificationUrl): void
    {
        $email = (new Email())
            ->from($this->defaultFromEmail)
            ->to($user->getEmail())
            ->subject('Vérifiez votre adresse email')
            ->html($this->getEmailContent($user, $verificationUrl));

        $this->mailer->send($email);
    }

    public function verifyEmail(User $user, string $token): bool
    {
        if ($user->getEmailVerificationToken() !== $token) {
            return false;
        }

        if ($user->isEmailVerified()) {
            return true; // Déjà vérifié
        }

        $user->setIsEmailVerified(true);
        $user->setEmailVerificationToken(null);
        $user->setEmailVerifiedAt(new \DateTimeImmutable());
        $user->setIsActive(true); // Activer le compte après vérification

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return true;
    }

    private function getEmailContent(User $user, string $verificationUrl): string
    {
        return sprintf(
            '<h1>Vérification d\'email</h1>'
            . '<p>Bonjour %s,</p>'
            . '<p>Merci de vous être inscrit. Veuillez cliquer sur le lien ci-dessous pour vérifier votre adresse email :</p>'
            . '<p><a href="%s">Vérifier mon email</a></p>'
            . '<p>Si vous n\'avez pas créé de compte, vous pouvez ignorer cet email.</p>',
            htmlspecialchars($user->getFirstName() ?? $user->getEmail()),
            $verificationUrl
        );
    }
}
