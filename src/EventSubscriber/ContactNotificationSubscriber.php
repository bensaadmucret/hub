<?php

namespace App\EventSubscriber;

use App\Event\ContactSubmittedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class ContactNotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly LoggerInterface $logger,
        private readonly string $adminEmail,
        private readonly string $mailerFrom
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ContactSubmittedEvent::NAME => 'onContactSubmitted',
        ];
    }

    public function onContactSubmitted(ContactSubmittedEvent $event): void
    {
        $contact = $event->getContact();

        try {
            $html = $this->twig->render('emails/contact_notification.html.twig', [
                'contact' => $contact,
            ]);

            $email = (new Email())
                ->from($this->mailerFrom)
                ->to($this->adminEmail)
                ->subject('Nouveau message de contact: ' . $contact->getSubject())
                ->html($html);

            // Option: aide au diagnostic en cas de doublon
            $email->getHeaders()->addTextHeader('Message-Id', sprintf('<contact-%d@app>', $contact->getId() ?? 0));

            $this->mailer->send($email);

            $this->logger->info('Contact notification email sent', [
                'contact_id' => $contact->getId(),
                'to' => $this->adminEmail,
                'subject' => $contact->getSubject(),
            ]);
        } catch (\Throwable $e) {
            // Ne bloque pas le flux utilisateur; log l'erreur pour suivi
            $this->logger->error('Failed to send contact notification email', [
                'contact_id' => $contact->getId(),
                'to' => $this->adminEmail,
                'subject' => $contact->getSubject(),
                'exception' => $e::class,
                'message' => $e->getMessage(), // Éviter de logger des PII
            ]);
        }
    }
}
