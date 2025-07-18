<?php

namespace App\Service;

use App\Dto\ContactDto;
use App\Entity\Contact;
use App\Repository\ContactRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class ContactService
{
    public function __construct(
        private readonly ContactRepository $contactRepository,
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack
    ) {
    }

    public function process(ContactDto $dto): void
    {
        $contact = new Contact();
        $contact->setName($dto->name);
        $contact->setEmail($dto->email);
        $contact->setPhone($dto->phone ?? null); // Le téléphone est optionnel
        $contact->setSubject($dto->subject);
        $contact->setMessage($dto->message);
        $contact->setConsent($dto->consent ?? false); // Consentement par défaut à false

        $this->contactRepository->save($contact, true);

        /** TODO : Dispatch d'événement pour les notifications
         * $event = new ContactSubmittedEvent($contact);
         * $this->eventDispatcher->dispatch($event);
         */
        
        $context = [
            'id' => $contact->getId(),
            'subject' => $contact->getSubject()
        ];

        // Ajout du user-agent si la requête est disponible
        $request = $this->requestStack->getCurrentRequest();
        if ($request !== null) {
            $context['user_agent'] = $request->headers->get('User-Agent');
        }

        $this->logger->info('New contact form submission', $context);
    }
}
