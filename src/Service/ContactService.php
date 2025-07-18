<?php

namespace App\Service;

use App\Dto\ContactDto;
use App\Entity\Contact;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class ContactService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ContactRepository $contactRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack // [+] Injection de RequestStack

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
        
        $this->logger->info('New contact form submission', [
            'id' => $contact->getId(),
            'subject' => $contact->getSubject(),
            'user_agent' => $this->requestStack->getCurrentRequest()->headers->get('User-Agent')
        ]);
    }
}
