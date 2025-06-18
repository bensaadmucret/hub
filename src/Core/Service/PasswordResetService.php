<?php

namespace App\Core\Service;

use App\Core\Entity\User;
use App\Core\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid as UuidGenerator;

class PasswordResetService
{
    private const TOKEN_TTL = 3600; // 1 heure

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private string $defaultFromEmail
    ) {
    }

    public function generateResetToken(): string
    {
        return UuidGenerator::v4()->toRfc4122();
    }

    public function requestPasswordReset(string $email): bool
    {
        $user = $this->userRepository->findOneByEmail($email);

        if (!$user) {
            // Pour des raisons de sécurité, on ne révèle pas si l'email existe
            return true;
        }

        // Générer un token de réinitialisation
        $token = $this->generateResetToken();
        $expiresAt = new \DateTimeImmutable(sprintf('+%d seconds', self::TOKEN_TTL));

        $user->setResetToken($token);
        $user->setResetTokenExpiresAt($expiresAt);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Envoyer l'email de réinitialisation
        $resetUrl = $this->urlGenerator->generate(
            'app_reset_password',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $this->sendPasswordResetEmail($user, $resetUrl);

        return true;
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        $user = $this->userRepository->findOneByResetToken($token);

        if (!$user || $user->isResetTokenExpired()) {
            return false;
        }

        // Mettre à jour le mot de passe
        $user->setPlainPassword($newPassword);
        $hashedPassword = $this->passwordHasher->hashPassword($user, $user->getPlainPassword());
        $user->setPassword($hashedPassword);
        $user->eraseCredentials();

        // Invalider le token
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return true;
    }

    private function sendPasswordResetEmail(User $user, string $resetUrl): void
    {
        $email = (new Email())
            ->from($this->defaultFromEmail)
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->html($this->getEmailContent($user, $resetUrl));

        $this->mailer->send($email);
    }

    private function getEmailContent(User $user, string $resetUrl): string
    {
        return sprintf(
            '<h1>Réinitialisation de mot de passe</h1>'
            . '<p>Bonjour %s,</p>'
            . '<p>Vous avez demandé à réinitialiser votre mot de passe. Cliquez sur le lien ci-dessous pour en créer un nouveau :</p>'
            . '<p><a href="%s">Réinitialiser mon mot de passe</a></p>'
            . '<p>Ce lien expirera dans 1 heure.</p>'
            . '<p>Si vous n\'avez pas demandé de réinitialisation de mot de passe, vous pouvez ignorer cet email en toute sécurité.</p>',
            htmlspecialchars($user->getFirstName() ?? ''),
            $resetUrl
        );
    }
}
