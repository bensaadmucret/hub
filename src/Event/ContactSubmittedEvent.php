<?php

namespace App\Event;

use App\Entity\Contact;
use Symfony\Contracts\EventDispatcher\Event;

class ContactSubmittedEvent extends Event
{
    public const NAME = 'contact.submitted';

    public function __construct(
        private readonly Contact $contact
    ) {
    }

    public function getContact(): Contact
    {
        return $this->contact;
    }
}
